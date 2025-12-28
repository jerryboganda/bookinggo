<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING SETTINGS STRUCTURE ===\n\n";

$settings = DB::table('settings')->limit(2)->get();

foreach ($settings as $s) {
    echo json_encode((array)$s, JSON_PRETTY_PRINT) . "\n\n";
}

echo "Now checking what getCompanyAllSetting would return:\n";
echo "Looking for created_by=3 and business=2...\n\n";

$found = DB::table('settings')
    ->where('created_by', 3)
    ->where('business', 2)
    ->get();

echo "Found: " . count($found) . " records\n";

if (count($found) > 0) {
    foreach ($found as $f) {
        echo "  {$f->key} => {$f->value}\n";
    }
}
