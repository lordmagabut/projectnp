<?php

namespace App\Services;

use Illuminate\Http\Request;

class ProyekService
{
    public static function validateRequest(Request $request)
    {
        return $request->validate([
            'nama_proyek' => 'required|string|max:255',
            'pemberi_kerja_id' => 'required|integer|exists:pemberi_kerja,id',
            'no_spk' => 'nullable|string|max:100',
            'nilai_spk' => 'nullable|numeric',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);
    }

    public static function validateUpdateRequest(Request $request)
    {
        return $request->validate([
            'nama_proyek' => 'required|string|max:255',
            'pemberi_kerja_id' => 'required|exists:pemberi_kerja,id',
            'no_spk' => 'required|string|max:100',
            'nilai_spk' => 'required|numeric',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'status' => 'required|in:perencanaan,berjalan,selesai',
            'lokasi' => 'required|string|max:255',
            'jenis_proyek' => 'required|in:kontraktor,cost and fee,office',
            'diskon_rab' => 'nullable|numeric|min:0',
            'file_spk' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }

    public static function hitungKontrak($proyek, $request)
    {
        $nilai_penawaran = $proyek->nilai_penawaran ?? 0;
        $diskon_rab = $request->diskon_rab ?? 0;
        $nilai_kontrak = $nilai_penawaran - $diskon_rab;

        return [
            'diskon_rab' => $diskon_rab,
            'nilai_kontrak' => $nilai_kontrak,
        ];
    }
}
