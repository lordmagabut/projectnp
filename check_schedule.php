<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Kolom rab_schedule_detail:\n";
$cols = Schema::getColumnListing('rab_schedule_detail');
foreach ($cols as $col) {
    echo "  - {$col}\n";
}

echo "\nSample data (5 rows):\n";
$rows = DB::table('rab_schedule_detail')->limit(5)->get();
foreach ($rows as $r) {
    print_r($r);
    echo "\n";
}
