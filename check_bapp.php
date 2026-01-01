<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$latestBapp = DB::table('bapps')->latest('id')->first();
if (!$latestBapp) {
    echo "Tidak ada BAPP\n";
    exit;
}

echo "BAPP Terbaru:\n";
echo "  ID: {$latestBapp->id}\n";
echo "  Proyek ID: {$latestBapp->proyek_id}\n";
echo "  Penawaran ID: {$latestBapp->penawaran_id}\n";
echo "  Minggu: {$latestBapp->minggu_ke}\n";
echo "  Created: {$latestBapp->created_at}\n\n";

// Cek total dari schedule
echo "Cek total bobot dari schedule_detail:\n";
$totalSchedule = DB::table('rab_schedule_detail as sd')
    ->join('rab_penawaran_items as pi', 'pi.id', '=', 'sd.rab_penawaran_item_id')
    ->where('sd.proyek_id', $latestBapp->proyek_id)
    ->where('sd.penawaran_id', $latestBapp->penawaran_id)
    ->selectRaw('SUM(sd.bobot_mingguan) as total')
    ->value('total');
echo "  Total dari schedule: {$totalSchedule}\n\n";

$details = DB::table('bapp_details')
    ->where('bapp_id', $latestBapp->id)
    ->select('id', 'kode', 'bobot_item')
    ->get();

echo "Detail Items:\n";
$total = 0;
foreach ($details as $d) {
    echo "  {$d->kode}: {$d->bobot_item}\n";
    $total += (float)$d->bobot_item;
}

echo "\nTotal (SUM dari DB): " . DB::table('bapp_details')->where('bapp_id', $latestBapp->id)->sum('bobot_item') . "\n";
echo "Total (manual): {$total}\n";
