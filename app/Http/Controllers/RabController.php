<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\RABImport;
use App\Models\RabHeader;
use App\Models\RabDetail;
use App\Models\RabKategori;
use App\Models\Proyek;
use Maatwebsite\Excel\Facades\Excel;

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

    public function reset($proyek_id)
    {
        \App\Models\RabDetail::whereIn('rab_header_id', function($q) use ($proyek_id) {
            $q->select('id')->from('rab_header')->where('proyek_id', $proyek_id);
        })->delete();

        \App\Models\RabHeader::where('proyek_id', $proyek_id)->delete();

        return redirect()->back()->with('success', 'Data RAB berhasil direset.');
    }

    /** ====================== DOWNLOAD TEMPLATE & README ====================== */

    public function downloadTemplate()
    {
        // lokasi default file template jika kamu simpan statis
        $path = storage_path('app/templates/rab_import_template_v2.xlsx');

        if (!file_exists($path)) {
            // Fallback: generate XLSX on-the-fly
            try {
                $this->generateTemplateXlsx($path);
            } catch (\Throwable $e) {
                abort(500, 'Gagal membuat template: '.$e->getMessage());
            }
        }

        return response()->download($path, 'rab_import_template.xlsx');
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
            'TEMPLATE IMPOR RAB (tanpa pembulatan & tanpa bobot)',
            '',
            'STRUKTUR FILE:',
            '- Sheet RAB_Header: daftar header (induk/sub-induk).',
            '- Sheet RAB_Detail: daftar item detail yang terhubung ke header via "header_kode".',
            '',
            'KETENTUAN:',
            '1) Jangan ubah nama sheet & urutan kolom.',
            '2) proyek_id tidak diisi di file (diambil saat impor).',
            '3) parent_kode pada RAB_Header mengacu ke kode header induk (kosong untuk root).',
            '4) header_kode pada RAB_Detail mengacu ke RAB_Header.kode.',
            '5) kode pada RAB_Detail boleh kosong; sistem akan mengisi header_kode.N.',
            '6) harga_satuan = harga_material + harga_upah (tanpa pembulatan).',
            '7) Jika ahsp_id/ahsp_kode diisi & harga kosong, sistem akan ambil dari AHSP.',
            '8) Jika kolom total* kosong, sistem akan menghitung otomatis.',
        ];
        foreach ($lines as $i => $t) {
            $readme->setCellValue('A'.($i+1), $t);
        }
        $readme->getColumnDimension('A')->setWidth(120);
        $readme->getStyle('A1:A'.(count($lines)))->getAlignment()->setWrapText(true);

        // RAB_Header
        $headerSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'RAB_Header');
        $spreadsheet->addSheet($headerSheet, 1);
        $headerSheet->fromArray(['kategori_id','parent_kode','kode','deskripsi'], null, 'A1');
        $headerSheet->fromArray([1,'','1','PEKERJAAN PERSIAPAN'], null, 'A2');
        $headerSheet->fromArray([1,'1','1.1','PEKERJAAN PEMBERSIHAN'], null, 'A3');
        foreach (['A'=>12,'B'=>14,'C'=>14,'D'=>44] as $col => $w) $headerSheet->getColumnDimension($col)->setWidth($w);
        $headerSheet->freezePane('A2');

        // RAB_Detail
        $detailSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'RAB_Detail');
        $spreadsheet->addSheet($detailSheet, 2);
        $detailSheet->fromArray([
            'header_kode','kode','deskripsi','area','spesifikasi','satuan','volume',
            'harga_material','harga_upah','harga_satuan','total_material','total_upah','total','ahsp_id','ahsp_kode'
        ], null, 'A1');
        $detailSheet->fromArray([
            '1.1','1.1.1','Land Clearing','Lapangan Utama','PC 200 7 Hari','m2',53.56,0,19000,19000,0,1017640,1017640,'',''
        ], null, 'A2');
        $detailSheet->fromArray([
            '1.1','1.1.2','Pekerjaan Bekisting (pile cap 100 x 100)','Kamar Mandi','Pake Blencong','M2',1,602400,30650,633050,602400,30650,633050,'','A-001'
        ], null, 'A3');
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
    