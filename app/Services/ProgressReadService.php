<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProgressReadService
{
    /**
     * Ambil progress S/D minggu (N-1) per item (rab_detail) dalam % bobot-proyek dan % terhadap bobot item.
     * - prev_bobot_pct_project : SUM(bobot_minggu_ini) (satuan: % bobot proyek)
     * - prev_pct_of_item       : (prev_bobot_pct_project / bobot_item_pct) * 100
     *
     * @param  int   $proyekId
     * @param  int   $mingguKe   Minggu yang sedang diinput (ambil < N)
     * @param  array $bobotItemPctMap [rab_detail_id => bobot_item_pct]
     * @param  bool  $finalOnly  true = hanya progress final; false = termasuk draft
     * @return array keyed by rab_detail_id:
     *         ['prev_bobot_pct_project' => float, 'prev_pct_of_item' => float]
     */
    public static function prevByDetail(
        int $proyekId,
        int $mingguKe,
        array $bobotItemPctMap,
        bool $finalOnly = true
    ): array {
        // Ambil total bobot minggu lalu per item (sum bobot_minggu_ini) untuk minggu < N
        $q = DB::table('rab_progress_detail as rpd')
            ->join('rab_progress as rp', 'rp.id', '=', 'rpd.rab_progress_id')
            ->where('rp.proyek_id', $proyekId)
            ->where('rp.minggu_ke', '<', $mingguKe);

        if ($finalOnly) {
            $q->where('rp.status', 'final'); // hanya final yang dihitung "sd minggu lalu"
        }

        $rows = $q->select('rpd.rab_detail_id', DB::raw('SUM(rpd.bobot_minggu_ini) as prev_bobot'))
                  ->groupBy('rpd.rab_detail_id')
                  ->get();

        $out = [];
        foreach ($rows as $r) {
            $detailId = (int) $r->rab_detail_id;
            $prevBobot = (float) $r->prev_bobot; // % bobot proyek
            $bobotItem = (float) ($bobotItemPctMap[$detailId] ?? 0.0);
            $prevPctOfItem = $bobotItem > 0 ? ($prevBobot / $bobotItem) * 100.0 : 0.0;

            $out[$detailId] = [
                'prev_bobot_pct_project' => $prevBobot,
                'prev_pct_of_item'       => $prevPctOfItem,
            ];
        }

        // Pastikan item tanpa progress tetap punya default 0
        foreach ($bobotItemPctMap as $detailId => $bobotItem) {
            if (!isset($out[$detailId])) {
                $out[$detailId] = [
                    'prev_bobot_pct_project' => 0.0,
                    'prev_pct_of_item'       => 0.0,
                ];
            }
        }

        return $out;
    }
}
