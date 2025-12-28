<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING CURRENCY SYMBOL RETRIEVAL ===\n\n";

$createdBy = 3; // support@amaddiagnosticcentre.com.pk
$businessId = 2; // Amad Diagnostic Centre

echo "Getting currency_symbol using company_setting helper:\n";
$sym = company_setting('currency_symbol', $createdBy, $businessId);
echo "Result: " . ($sym ?: 'NULL') . "\n";

echo "\nGetting currency using company_setting helper:\n";
$cur = company_setting('currency', $createdBy, $businessId);
echo "Result: " . ($cur ?: 'NULL') . "\n";

echo "\nDirect database query:\n";
use Illuminate\Support\Facades\DB;

$dbSym = DB::table('settings')
    ->where('business', $businessId)
    ->where('key', 'currency_symbol')
    ->first();

echo "Database currency_symbol: " . ($dbSym ? $dbSym->value : 'NULL') . "\n";

$dbCur = DB::table('settings')
    ->where('business', $businessId)
    ->where('key', 'currency')
    ->first();

echo "Database currency: " . ($dbCur ? $dbCur->value : 'NULL') . "\n";
