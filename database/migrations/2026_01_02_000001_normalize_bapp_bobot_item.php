<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalisasi bobot_item di semua BAPP yang sudah ada
        // agar total tepat 100 (koreksi drift dari schedule)
        
        $bapps = DB::table('bapps')->select('id')->get();
        
        foreach ($bapps as $bapp) {
            $details = DB::table('bapp_details')
                ->where('bapp_id', $bapp->id)
                ->select('id', 'bobot_item')
                ->get();
            
            if ($details->isEmpty()) continue;
            
            // Hitung total bobot saat ini
            $total = $details->sum('bobot_item');
            
            // Jika total tidak tepat 100, normalisasi
            if ($total > 0 && abs($total - 100) > 0.0001) {
                $factor = 100 / $total;
                $normalized = [];
                
                foreach ($details as $d) {
                    $normalized[$d->id] = round((float)$d->bobot_item * $factor, 2); // 2 desimal sesuai DB
                }
                
                // Koreksi item pertama jika masih ada sisa
                $newTotal = array_sum($normalized);
                if ($newTotal != 100 && count($normalized) > 0) {
                    $firstId = array_key_first($normalized);
                    $normalized[$firstId] = round($normalized[$firstId] + (100 - $newTotal), 2);
                }
                
                // Update database
                foreach ($normalized as $id => $value) {
                    DB::table('bapp_details')
                        ->where('id', $id)
                        ->update(['bobot_item' => $value]);
                }
                
                // Update total di header BAPP juga
                $totPrev = DB::table('bapp_details')->where('bapp_id', $bapp->id)->sum('prev_pct');
                $totDelta = DB::table('bapp_details')->where('bapp_id', $bapp->id)->sum('delta_pct');
                $totNow = DB::table('bapp_details')->where('bapp_id', $bapp->id)->sum('now_pct');
                
                DB::table('bapps')
                    ->where('id', $bapp->id)
                    ->update([
                        'total_prev_pct' => round($totPrev, 2),
                        'total_delta_pct' => round($totDelta, 2),
                        'total_now_pct' => round($totNow, 2),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak bisa di-rollback karena kita tidak tahu nilai aslinya
    }
};
