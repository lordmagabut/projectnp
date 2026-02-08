<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\RABImport;
use App\Exports\RABExport;
use App\Models\RabHeader;
use App\Models\RabDetail;
use App\Models\RabKategori;
use App\Models\Proyek;
use App\Models\ProyekTaxProfile;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use iio\libmergepdf\Merger;

class RabController extends Controller
{
    public function input($proyek_id)
    {
        $proyek = Proyek::findOrFail($proyek_id);
        $kategoris = RabKategori::all();

        return view('rab.index', [
            'proyek_id' => $proyek_id,
            'proyek'    => $proyek,
            'kategoris' => $kategoris,
        ]);
    }

    public function index($proyek_id)
    {
        $proyek   = Proyek::findOrFail($proyek_id);
        $headers  = RabHeader::where('proyek_id', $proyek_id)->orderBy('kode_sort')->get();
        $details  = RabDetail::where('proyek_id', $proyek_id)->orderBy('kode_sort')->get();
        $kategoris= RabKategori::all();

        return view('rab.index', compact('proyek', 'headers', 'details', 'kategoris'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'      => 'required|mimes:xlsx,xls',
            'proyek_id' => 'required|exists:proyek,id',
        ]);

        // bersihkan dulu
        RabHeader::where('proyek_id', $request->proyek_id)->delete();
        RabDetail::where('proyek_id', $request->proyek_id)->delete();

        $import = new RABImport($request->proyek_id);
        Excel::import($import, $request->file('file'));
        
        // Cek apakah ada AHSP yang tidak valid
        $invalidAhsp = $import->ctx->invalidAhsp ?? [];

        // Berikan pesan sesuai kondisi
        if (!empty($invalidAhsp)) {
            $warningMsg = 'RAB berhasil diimport, namun ' . count($invalidAhsp) . ' item memiliki referensi AHSP yang tidak ditemukan dan dilewati (tidak di-link ke AHSP): ';
            $items = array_slice($invalidAhsp, 0, 5); // Tampilkan max 5 item
            $itemList = collect($items)->map(fn($i) => "{$i['item']} ({$i['ahsp']})")->implode(', ');
            if (count($invalidAhsp) > 5) {
                $itemList .= ' dan ' . (count($invalidAhsp) - 5) . ' lainnya';
            }
            return redirect()->route('proyek.show', $request->proyek_id)
                ->with('warning', $warningMsg . $itemList);
        }

        return redirect()->route('proyek.show', $request->proyek_id)->with('success', 'RAB berhasil diimport!');
    }

    public function export($proyek_id)
    {
        $proyek = Proyek::findOrFail($proyek_id);
        $fileName = 'RAB_' . str_replace(' ', '_', $proyek->nama_proyek) . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new RABExport($proyek_id), $fileName);
    }

    public function reset($proyek_id)
    {
        \App\Models\RabDetail::whereIn('rab_header_id', function($q) use ($proyek_id) {
            $q->select('id')->from('rab_header')->where('proyek_id', $proyek_id);
        })->delete();

        \App\Models\RabHeader::where('proyek_id', $proyek_id)->delete();

        return redirect()->back()->with('success', 'Data RAB berhasil direset.');
    }

    public function generatePdfMixed($proyek_id)
    {
        $proyek = Proyek::with(['pemberiKerja'])->findOrFail($proyek_id);
        $headers = RabHeader::where('proyek_id', $proyek_id)
            ->with(['rabDetails.ahsp.details'])
            ->orderBy('kode_sort')
            ->get();

        $tax = ProyekTaxProfile::where('proyek_id', $proyek_id)->where('aktif', 1)->first();

        $pdfSummary = Pdf::loadView('rab.pdf_summary', compact('proyek', 'headers', 'tax'))
            ->setPaper('A4', 'portrait');

        $pdfDetail = Pdf::loadView('rab.pdf_detail', compact('proyek', 'headers', 'tax'))
            ->setPaper('A4', 'landscape');

        $merger = new Merger;
        $merger->addRaw($pdfSummary->output());
        $merger->addRaw($pdfDetail->output());
        $final = $merger->merge();

        $filename = 'RAB_' . str_replace(' ', '_', $proyek->nama_proyek) . '_' . date('Ymd_His') . '.pdf';

        return response($final)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }

    /** ====================== DOWNLOAD TEMPLATE & README ====================== */

    public function downloadTemplate()
    {
        // Lokasi file template - FORCE 6-SHEET VERSION
        $path = storage_path('app/templates/rab_import_6sheet.xlsx');

        // Pastikan folder ada
        @mkdir(dirname($path), 0775, true);

        // SELALU delete & regenerate (no caching)
        if (file_exists($path)) {
            unlink($path);
        }
        
        // Juga hapus file lama jika ada
        $oldPath = storage_path('app/templates/rab_import_template.xlsx');
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
        $oldPathV2 = storage_path('app/templates/rab_import_template_v2.xlsx');
        if (file_exists($oldPathV2)) {
            unlink($oldPathV2);
        }

        try {
            $this->generateTemplateXlsx($path);
            // Log untuk verifikasi
            \Log::info('RAB template generated: ' . $path . ', file size: ' . filesize($path) . ' bytes');
        } catch (\Throwable $e) {
            \Log::error('RAB template generation error: ' . $e->getMessage());
            abort(500, 'Gagal membuat template: '.$e->getMessage());
        }

        return response()->download($path, 'rab_import_6sheet.xlsx');
    }

    public function downloadTemplateReadme()
    {
        $path = storage_path('app/templates/README_rab_template.txt');

        if (!file_exists($path)) {
            $readme = <<<TXT
TEMPLATE IMPOR RAB (tanpa pembulatan & tanpa bobot)

STRUKTUR:
- Sheet RAB_Header: kategori_id | parent_kode | kode | deskripsi
- Sheet RAB_Detail: header_kode | kode | deskripsi | area | spesifikasi | satuan | volume |
                    harga_material | harga_upah | harga_satuan |
                    total_material | total_upah | total | ahsp_id | ahsp_kode

KETENTUAN:
1) Jangan ubah nama sheet & urutan kolom.
2) proyek_id tidak diisi di file (diambil saat impor).
3) parent_kode pada RAB_Header mengacu ke kode induk (kosong untuk root).
4) header_kode pada RAB_Detail mengacu ke RAB_Header.kode.
5) kode pada RAB_Detail boleh kosong; sistem akan mengisi header_kode.N.
6) harga_satuan = harga_material + harga_upah (tanpa pembulatan).
7) Jika ahsp_id/ahsp_kode diisi dan harga kosong, sistem akan ambil dari AHSP.
8) Jika kolom total* kosong, sistem akan menghitung otomatis.
TXT;
            return response($readme, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="README_rab_template.txt"'
            ]);
        }

        return response()->download($path, 'README_rab_template.txt');
    }

    /** Generate template XLSX secara programatik (menggunakan PhpSpreadsheet dari Maatwebsite) */
    private function generateTemplateXlsx(string $path): void
    {
        // pastikan folder ada
        @mkdir(dirname($path), 0775, true);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // README
        $readme = $spreadsheet->getActiveSheet();
        $readme->setTitle('README');
        $lines = [
            'TEMPLATE IMPOR RAB + AHSP TERINTEGRASI',
            '',
            'STRUKTUR FILE (6 SHEET):',
            '1. HSD_Material: Daftar harga satuan material',
            '2. HSD_Upah: Daftar harga satuan upah/jasa',
            '3. AHSP_Header: Master AHSP (daftar pekerjaan analisa)',
            '4. AHSP_Detail: Komponen material/upah untuk setiap AHSP',
            '5. RAB_Header: Daftar header RAB (induk/sub-induk)',
            '6. RAB_Detail: Daftar item detail RAB dengan referensi ke AHSP',
            '',
            'URUTAN PROSES IMPORT:',
            '1) HSD_Material & HSD_Upah diimpor dulu (master harga)',
            '2) AHSP_Header & AHSP_Detail diimpor (menciptakan pekerjaan analisa)',
            '3) RAB_Header & RAB_Detail diimpor (menciptakan RAB dengan link ke AHSP)',
            '',
            'KETENTUAN UMUM:',
            '• Jangan ubah nama sheet & urutan kolom di setiap sheet',
            '• Jangan hapus baris header (baris pertama)',
            '• Isi data mulai dari baris ke-2',
            '',
            'HSD_MATERIAL:',
            '• kode_item: Kode unik material (misal: MAT.001)',
            '• nama_item: Nama material (misal: Pasir Malang)',
            '• satuan: Satuan harga (misal: m3, sak, kg)',
            '• harga_satuan: Harga per satuan (numerik, misal: 150000)',
            '• Jika kode_item sama di baris lain, data akan di-UPDATE bukan tambah baru',
            '',
            'HSD_UPAH:',
            '• kode_item: Kode unik upah (misal: UPH.001)',
            '• nama_item: Nama jenis pekerja (misal: Tukang Gali)',
            '• satuan: Satuan harga (misal: HOK, hari, jam)',
            '• harga_satuan: Harga per satuan (numerik, misal: 200000)',
            '• Jika kode_item sama di baris lain, data akan di-UPDATE bukan tambah baru',
            '',
            'AHSP_HEADER:',
            '• kode_pekerjaan: Kode unik AHSP (misal: A.1)',
            '• nama_pekerjaan: Nama pekerjaan/analisa (misal: Excavation 1m)',
            '• satuan: Satuan hasil analisa (misal: m3, jam, LS)',
            '• kategori_id: ID kategori AHSP (numerik, opsional - jika kosong kategori tidak di-set)',
            '• catatan: Keterangan tambahan (opsional)',
            '• Jika kode_pekerjaan sama, data akan di-UPDATE',
            '',
            'AHSP_DETAIL:',
            '• ahsp_kode: Harus match dengan AHSP_Header.kode_pekerjaan (misal: A.1)',
            '• tipe: Jenis ("material" atau "upah")',
            '• kode_item: Harus match dengan HSD_Material.kode_item atau HSD_Upah.kode_item',
            '• koefisien: Jumlah/proporsi penggunaan (numerik, misal: 1.2)',
            '• Harga & subtotal OTOMATIS dihitung dari HSD_Material/HSD_Upah',
            '',
            'RAB_HEADER:',
            '• kategori_id: ID kategori (biarkan kosong atau isi angka jika ada)',
            '• parent_kode: Kode header induk (kosong untuk header root, misal: 1 untuk induk level 1)',
            '• kode: Kode unik header (misal: 1, 1.1, 1.1.1) - HARUS UNIK',
            '• deskripsi: Deskripsi pekerjaan (misal: PEKERJAAN PERSIAPAN)',
            '',
            'RAB_DETAIL:',
            '• header_kode: HARUS MATCH dengan RAB_Header.kode (misal: 1.1)',
            '• kode: Kode detail item (boleh kosong, auto-generate: header_kode.N)',
            '• deskripsi: Deskripsi item',
            '• area: Lokasi/area pekerjaan (opsional)',
            '• spesifikasi: Spesifikasi teknis (opsional)',
            '• satuan: Satuan pengukuran (misal: m3, m2, jam)',
            '• volume: Jumlah pekerjaan (numerik)',
            '• harga_material: Boleh kosong → akan ambil dari AHSP',
            '• harga_upah: Boleh kosong → akan ambil dari AHSP',
            '• harga_satuan: Boleh kosong → akan dihitung otomatis',
            '• total_material: Boleh kosong → auto-hitung (harga_material × volume)',
            '• total_upah: Boleh kosong → auto-hitung (harga_upah × volume)',
            '• total: Boleh kosong → auto-hitung (harga_satuan × volume)',
            '• ahsp_id: Isi dengan ID AHSP jika ingin link, ATAU gunakan ahsp_kode',
            '• ahsp_kode: HARUS MATCH dengan AHSP_Header.kode_pekerjaan untuk AUTO-LINK',
            '',
            'TIPS PENTING:',
            '1) Isi HSD_Material & HSD_Upah DULU sebelum AHSP_Detail',
            '2) Isi AHSP_Header & AHSP_Detail SEBELUM mengisi RAB_Detail',
            '3) Pastikan ahsp_kode di RAB_Detail match dengan AHSP_Header.kode_pekerjaan',
            '4) Jika ada warning AHSP tidak ditemukan → check spelling kode AHSP',
            '5) Template hanya contoh - bisa tambah/ubah data sesuai kebutuhan',
        ];
        foreach ($lines as $i => $t) {
            $readme->setCellValue('A'.($i+1), $t);
        }
        $readme->getColumnDimension('A')->setWidth(150);
        $readme->getStyle('A1:A'.(count($lines)))->getAlignment()->setWrapText(true);
        $readme->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // HSD_Material
        $matSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'HSD_Material');
        $spreadsheet->addSheet($matSheet, 1);
        $matSheet->fromArray(['kode_item','nama_item','satuan','harga_satuan'], null, 'A1');
        $matSheet->fromArray(['MAT.001','Pasir Malang','m3',150000], null, 'A2');
        $matSheet->fromArray(['MAT.002','Semen','sak',50000], null, 'A3');
        $matSheet->fromArray(['MAT.003','Pasir Batu','m3',180000], null, 'A4');
        foreach (['A'=>14,'B'=>30,'C'=>10,'D'=>16] as $col => $w) $matSheet->getColumnDimension($col)->setWidth($w);
        $matSheet->freezePane('A2');

        // HSD_Upah
        $upahSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'HSD_Upah');
        $spreadsheet->addSheet($upahSheet, 2);
        $upahSheet->fromArray(['kode_item','nama_item','satuan','harga_satuan'], null, 'A1');
        $upahSheet->fromArray(['UPH.001','Tukang Gali','HOK',200000], null, 'A2');
        $upahSheet->fromArray(['UPH.002','Pembantu Tukang','HOK',150000], null, 'A3');
        $upahSheet->fromArray(['UPH.003','Tukang Gali Pro','HOK',250000], null, 'A4');
        foreach (['A'=>14,'B'=>30,'C'=>10,'D'=>16] as $col => $w) $upahSheet->getColumnDimension($col)->setWidth($w);
        $upahSheet->freezePane('A2');

        // AHSP_Header
        $ahspHdrSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'AHSP_Header');
        $spreadsheet->addSheet($ahspHdrSheet, 3);
        $ahspHdrSheet->fromArray(['kode_pekerjaan','nama_pekerjaan','satuan','kategori_id','catatan'], null, 'A1');
        $ahspHdrSheet->fromArray(['A.1','Excavation 1m','m3',1,'Tanah biasa'], null, 'A2');
        $ahspHdrSheet->fromArray(['A.2','Excavation 2m','m3',1,'Tanah batu'], null, 'A3');
        foreach (['A'=>16,'B'=>36,'C'=>10,'D'=>12,'E'=>30] as $col => $w) $ahspHdrSheet->getColumnDimension($col)->setWidth($w);
        $ahspHdrSheet->freezePane('A2');

        // AHSP_Detail
        $ahspDtlSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'AHSP_Detail');
        $spreadsheet->addSheet($ahspDtlSheet, 4);
        $ahspDtlSheet->fromArray(['ahsp_kode','tipe','kode_item','koefisien'], null, 'A1');
        $ahspDtlSheet->fromArray(['A.1','material','MAT.001',1.2], null, 'A2');
        $ahspDtlSheet->fromArray(['A.1','material','MAT.002',8], null, 'A3');
        $ahspDtlSheet->fromArray(['A.1','upah','UPH.001',5], null, 'A4');
        $ahspDtlSheet->fromArray(['A.2','material','MAT.003',1.5], null, 'A5');
        $ahspDtlSheet->fromArray(['A.2','upah','UPH.003',6], null, 'A6');
        foreach (['A'=>16,'B'=>12,'C'=>14,'D'=>12] as $col => $w) $ahspDtlSheet->getColumnDimension($col)->setWidth($w);
        $ahspDtlSheet->freezePane('A2');

        // RAB_Header
        $headerSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'RAB_Header');
        $spreadsheet->addSheet($headerSheet, 5);
        $headerSheet->fromArray(['kategori_id','parent_kode','kode','deskripsi'], null, 'A1');
        $headerSheet->fromArray([1,'','1','PEKERJAAN PERSIAPAN'], null, 'A2');
        $headerSheet->fromArray([1,'1','1.1','PEKERJAAN PEMBERSIHAN'], null, 'A3');
        $headerSheet->fromArray([1,'1','1.2','PEKERJAAN PENGGALIAN'], null, 'A4');
        $headerSheet->fromArray(['','','',''], null, 'A5'); // kosong untuk isi manual
        foreach (['A'=>12,'B'=>14,'C'=>14,'D'=>44] as $col => $w) $headerSheet->getColumnDimension($col)->setWidth($w);
        $headerSheet->freezePane('A2');

        // RAB_Detail
        $detailSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'RAB_Detail');
        $spreadsheet->addSheet($detailSheet, 6);
        $detailSheet->fromArray([
            'header_kode','kode','deskripsi','area','spesifikasi','satuan','volume',
            'harga_material','harga_upah','harga_satuan','total_material','total_upah','total','ahsp_id','ahsp_kode'
        ], null, 'A1');
        $detailSheet->fromArray([
            '1.1','1.1.1','Excavation','Lapangan Utama','Tanah biasa','m3',50,'','','','','','','','A.1'
        ], null, 'A2');
        $detailSheet->fromArray([
            '1.1','1.1.2','Excavation Pro','Kamar Mandi','Tanah batu','m3',75,'','','','','','','','A.2'
        ], null, 'A3');
        $detailSheet->fromArray([
            '1.2','','Penggalian Tambahan','','','m3',25,'','','','','','','','A.1'
        ], null, 'A4'); // sample: kode kosong (auto-generate)
        $detailSheet->fromArray([
            '','','','','','','','','','','','','','',''
        ], null, 'A5'); // kosong untuk isi manual
        foreach (['A'=>14,'B'=>12,'C'=>44,'D'=>22,'E'=>36,'F'=>10,'G'=>10,'H'=>16,'I'=>16,'J'=>16,'K'=>16,'L'=>16,'M'=>16,'N'=>10,'O'=>14] as $col => $w) {
            $detailSheet->getColumnDimension($col)->setWidth($w);
        }
        $detailSheet->freezePane('A2');

        // simpan
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);
    }
        public function recalcAhsp(Request $request, $proyek_id)
    {
        $proyek = Proyek::findOrFail($proyek_id);
        $rabDetails = \App\Models\RabDetail::where('proyek_id', $proyek_id)
            ->whereNotNull('ahsp_id')
            ->get();

        $updated = 0;
        foreach ($rabDetails as $detail) {
            $ahsp = \App\Models\AhspHeader::find($detail->ahsp_id);
            if ($ahsp) {
                $detail->harga_material = (float)($ahsp->total_material ?? 0);
                $detail->harga_upah = (float)($ahsp->total_upah ?? 0);
                $detail->harga_satuan = (float)($ahsp->total_harga_pembulatan ?? $ahsp->total_harga ?? ($detail->harga_material + $detail->harga_upah));
                $detail->total_material = $detail->harga_material * $detail->volume;
                $detail->total_upah = $detail->harga_upah * $detail->volume;
                $detail->total = $detail->harga_satuan * $detail->volume;
                $detail->save();
                $updated++;
            }
        }
        return redirect()->back()->with('success', "Kalkulasi ulang selesai. $updated item RAB diperbarui dari AHSP.");
    }
}
    