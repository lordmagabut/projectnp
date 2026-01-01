<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulasi query Wi dari rab_schedule_detail
$proyekId = 55; // sesuaikan dengan proyek Anda
$penawaranId = 50; // sesuaikan

$Wi = DB::table('rab_schedule_detail as sd')
    ->join('rab_penawaran_items as pi', 'pi.id', '=', 'sd.rab_penawaran_item_id')
    ->where('sd.proyek_id', $proyekId)
    ->where('sd.penawaran_id', $penawaranId)
    ->selectRaw('pi.rab_detail_id as id, SUM(sd.bobot_mingguan) as s')
    ->groupBy('pi.rab_detail_id')
    ->pluck('s', 'id')->toArray();

echo "Wi dari schedule (SEBELUM normalisasi):\n";
$totalBefore = array_sum($Wi);
echo "  Total: {$totalBefore}\n";
echo "  Count: " . count($Wi) . "\n";
echo "  Drift: " . abs($totalBefore - 100) . "\n\n";

// NORMALISASI
$totalWi = array_sum($Wi);
if ($totalWi > 0 && abs($totalWi - 100) > 0.0001) {
    echo "NORMALISASI TRIGGERED (drift > 0.0001)\n";
    $factor = 100 / $totalWi;
    echo "  Factor: {$factor}\n";
    
    foreach ($Wi as $id => $val) {
        $Wi[$id] = round($val * $factor, 4);
    }
    
    // koreksi item pertama jika masih ada sisa
    $newTotal = array_sum($Wi);
    echo "  Total setelah normalisasi: {$newTotal}\n";
    
    if ($newTotal != 100 && count($Wi) > 0) {
        echo "  Koreks item pertama karena sisa: " . (100 - $newTotal) . "\n";
        $firstId = array_key_first($Wi);
        $Wi[$firstId] = round($Wi[$firstId] + (100 - $newTotal), 4);
    }
} else {
    echo "NORMALISASI TIDAK DIPERLUKAN (drift <= 0.0001)\n";
}

echo "\nWi setelah normalisasi:\n";
$totalAfter = array_sum($Wi);
echo "  Total: {$totalAfter}\n";
echo "  Drift: " . abs($totalAfter - 100) . "\n";
