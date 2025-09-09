<?php

namespace App\Services;

use App\Models\RabDetail;
use App\Models\RabHeader;

class BobotItemService
{
    /**
     * Hitung bobot (%) per item (rab_detail) untuk 1 proyek.
     * Rumus nilai_item = COALESCE(nilai_total, harga_satuan*volume).
     * Return: [rab_detail_id => bobot_pct]
     */
    public static function getItemWeightsByProyek(int $proyekId): array
    {
        // Ambil semua item anak dalam proyek (rab_detail join rab_header untuk filter proyek)
        $items = RabDetail::query()
            ->select('rab_detail.*')
            ->join('rab_header', 'rab_header.id', '=', 'rab_detail.rab_header_id')
            ->where('rab_header.proyek_id', $proyekId)
            ->get();

        // Hitung total nilai proyek (hanya dari level item/anak)
        $totalProyek = 0.0;
        $nilaiPerItem = [];

        foreach ($items as $it) {
            // fleksibel: kalau ada kolom nilai_total, pakai itu; jika tidak, pakai harga_satuan*volume; kalau tidak ada juga, 0
            $nilai = null;
            if (property_exists($it, 'nilai_total') && $it->nilai_total !== null) {
                $nilai = (float) $it->nilai_total;
            } elseif (property_exists($it, 'harga_satuan') && $it->harga_satuan !== null && $it->volume !== null) {
                $nilai = (float) $it->harga_satuan * (float) $it->volume;
            } else {
                $nilai = 0.0;
            }
            $nilaiPerItem[$it->id] = $nilai;
            $totalProyek += $nilai;
        }

        if ($totalProyek <= 0) {
            // hindari bagi 0: kembalikan 0 semua
            return array_fill_keys(array_keys($nilaiPerItem), 0.0);
        }

        $bobot = [];
        foreach ($nilaiPerItem as $detailId => $nilai) {
            $bobot[$detailId] = ($nilai / $totalProyek) * 100.0;
        }
        return $bobot;
    }

    /**
     * Bobot rencana per sub-induk (rab_header) = sum bobot anak-anaknya (rab_detail).
     */
    public static function getHeaderWeight(int $rabHeaderId): float
    {
        $header = RabHeader::find($rabHeaderId);
        if (!$header) return 0.0;

        $proyekId = (int) $header->proyek_id;
        $bobotPerItem = self::getItemWeightsByProyek($proyekId);

        $detailIds = RabDetail::query()
            ->where('rab_header_id', $rabHeaderId)
            ->pluck('id')
            ->all();

        $sum = 0.0;
        foreach ($detailIds as $id) {
            $sum += $bobotPerItem[$id] ?? 0.0;
        }
        return $sum;
    }
}
