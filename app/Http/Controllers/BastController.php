<?php

namespace App\Http\Controllers;

use App\Models\Bast;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class BastController extends Controller
{
    /**
     * Tampilkan detail BAST
     */
    public function show($id)
    {
        $bast = Bast::with(['proyek', 'sertifikatPembayaran.bapp', 'sertifikatPembayaran.penawaran'])
            ->findOrFail($id);

        // Set default ketentuan jika belum ada
        if (empty($bast->ketentuan)) {
            $bast->ketentuan = [
                'bast_1' => [
                    'a' => 'PIHAK KEDUA menyerahkan hasil pekerjaan kepada PIHAK PERTAMA dan PIHAK PERTAMA menyatakan menerima hasil pekerjaan tersebut dengan baik dan lengkap.',
                    'b' => 'Sebagai kelengkapan dari Berita Acara Serah Terima Pertama dilampirkan Berita Acara Penyelesaian Pekerjaan yang telah disetujui oleh PIHAK PERTAMA.',
                    'c' => 'Sesuai dengan Surat Kontrak, maka PIHAK KEDUA setelah Serah Terima Pekerjaan ini, PIHAK KEDUA tetap bertanggung jawab atas segala cacat Pekerjaan yang ada di RAB selama masa pemeliharaan yaitu 90 Hari terhitung sejak tanggal Serah Terima Pekerjaan, dan masa garansi pekerjaan 12 bulan sejak tanggal Serah Terima Pekerjaan.',
                ],
                'bast_2' => [
                    'a' => 'PIHAK PERTAMA telah menerima hasil pekerjaan dari PIHAK KEDUA sesuai BAST 1 dan menyatakan pekerjaan telah selesai dengan baik.',
                    'b' => 'Masa pemeliharaan telah berakhir dan PIHAK PERTAMA menyatakan tidak ada cacat atau kerusakan yang perlu diperbaiki.',
                    'c' => 'Dengan selesainya masa pemeliharaan, maka retensi yang ditahan akan dikembalikan kepada PIHAK KEDUA sesuai ketentuan yang berlaku.',
                ],
            ];
        }

        return view('bast.show', compact('bast'));
    }

    /**
     * Update ketentuan BAST
     */
    public function updateKetentuan(Request $request, $id)
    {
        $bast = Bast::findOrFail($id);

        $validated = $request->validate([
            'bast1_a' => 'required|string',
            'bast1_b' => 'required|string',
            'bast1_c' => 'required|string',
            'bast2_a' => 'required|string',
            'bast2_b' => 'required|string',
            'bast2_c' => 'required|string',
        ]);

        $bast->ketentuan = [
            'bast_1' => [
                'a' => $validated['bast1_a'],
                'b' => $validated['bast1_b'],
                'c' => $validated['bast1_c'],
            ],
            'bast_2' => [
                'a' => $validated['bast2_a'],
                'b' => $validated['bast2_b'],
                'c' => $validated['bast2_c'],
            ],
        ];
        $bast->save();

        return back()->with('success', 'Ketentuan BAST berhasil diperbarui.');
    }

    /**
     * Setujui BAST
     */
    public function approve(Request $request, $id)
    {
        $bast = Bast::findOrFail($id);

        if ($bast->status === 'approved' || $bast->status === 'done') {
            return back()->with('info', 'BAST sudah disetujui.');
        }

        $bast->status = 'approved';
        $bast->save();

        $proyekId = $bast->proyek_id;
        $penawaranId = optional($bast->sertifikatPembayaran)->penawaran_id;

        $redirect = route('proyek.show', $proyekId);
        if ($penawaranId) {
            $redirect .= '?tab=bast&penawaran_id=' . $penawaranId;
        } else {
            $redirect .= '?tab=bast';
        }

        return redirect($redirect)->with('success', 'BAST disetujui.');
    }

    /**
     * Download PDF BAST
     */
    public function pdf($id)
    {
        $bast = Bast::with(['proyek', 'sertifikatPembayaran.bapp', 'sertifikatPembayaran.penawaran'])
            ->findOrFail($id);

        // Set locale Indonesia
        \Carbon\Carbon::setLocale('id');
        @setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id');

        // Load view dan generate PDF
        $pdf = Pdf::loadView('bast.pdf', compact('bast'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'enable_php'           => true,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        // Sanitize filename - hapus karakter yang tidak diperbolehkan
        $fileName = preg_replace('/[\/\\:*?"<>|]/', '-', $bast->nomor ?? 'BAST');
        
        return $pdf->download('BAST-' . $fileName . '.pdf');
    }
}
