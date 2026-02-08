<?php

namespace App\Helpers;

use App\Models\Proyek;
use App\Models\RabHeader;
use App\Models\RabDetail;

class ProyekHelper
{
    /**
     * Hitung total nilai penawaran (jumlah total dari rab_detail) dan update ke proyek.
     */
    public static function updateNilaiPenawaran($proyekId)
    {
        // Total semua rab_detail berdasarkan proyek
        $totalBase = RabDetail::whereHas('header', function ($query) use ($proyekId) {
            $query->where('proyek_id', $proyekId);
        })->sum('total');

        // Update ke proyek
        $proyek = Proyek::find($proyekId);
        if ($proyek) {
            $kontigensi = (float)($proyek->kontingensi_persen ?? 0);
            $factor = 1 + ($kontigensi / 100);
            $total = $totalBase * $factor;

            $proyek->nilai_penawaran = $total;
            $proyek->nilai_kontrak = $total - ($proyek->diskon_rab ?? 0);
            $proyek->save();
        }

        return $total;
    }
}
