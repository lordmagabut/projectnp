<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlanActualService
{
    /**
     * Plan kumulatif per minggu (1..N) dari rab_schedule_detail.
     * Return: [minggu_ke => kumulatif_pct]
     */
    public static function planCumulativeByWeek(int $proyekId): array
    {
        // ambil bobot_mingguan per minggu dari seluruh header/sub-induk (rab_schedule_detail)
        $rows = DB::table('rab_schedule_detail as rsd')
            ->join('rab_schedule as rs', 'rs.id', '=', 'rsd.rab_schedule_id')
            ->join('rab_header as rh', 'rh.id', '=', 'rs.rab_header_id')
            ->where('rh.proyek_id', $proyekId)
            ->select('rsd.minggu_ke', DB::raw('SUM(rsd.bobot_mingguan) as bobot'))
            ->groupBy('rsd.minggu_ke')
            ->orderBy('rsd.minggu_ke')
            ->get();

        $cum = 0.0;
        $out = [];
        foreach ($rows as $r) {
            $cum += (float) $r->bobot;
            $out[(int) $r->minggu_ke] = $cum;
        }
        return $out;
    }

    /**
     * Actual kumulatif per minggu (1..N) dari rab_progress_detail.
     * Return: [minggu_ke => kumulatif_pct]
     */
    public static function actualCumulativeByWeek(int $proyekId): array
    {
        $rows = DB::table('rab_progress_detail as rpd')
            ->join('rab_progress as rp', 'rp.id', '=', 'rpd.rab_progress_id')
            ->where('rp.proyek_id', $proyekId)
            ->select('rp.minggu_ke', DB::raw('SUM(rpd.bobot_minggu_ini) as bobot'))
            ->groupBy('rp.minggu_ke')
            ->orderBy('rp.minggu_ke')
            ->get();

        $cum = 0.0;
        $out = [];
        foreach ($rows as $r) {
            $cum += (float) $r->bobot;
            $out[(int) $r->minggu_ke] = $cum;
        }
        return $out;
    }
}
