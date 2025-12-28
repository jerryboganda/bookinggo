<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING SETTINGS created_by ===\n\n";

// Update settings to use correct created_by
DB::table('settings')
    ->where('key', 'currency')
    ->where('business', 2)
    ->update(['created_by' => 3]);

DB::table('settings')
    ->where('key', 'currency_symbol')
    ->where('business', 2)
    ->update(['created_by' => 3]);

echo "✅ Updated settings to use created_by=3\n\n";

// Verify
$found = DB::table('settings')
    ->where('created_by', 3)
    ->where('business', 2)
    ->get();

echo "Verification - Found " . count($found) . " records:\n";
foreach ($found as $f) {
    echo "  {$f->key} => {$f->value}\n";
}

// Clear cache
Cache::forget('company_settings_2_3');

echo "\n✅ Cache cleared\n";
