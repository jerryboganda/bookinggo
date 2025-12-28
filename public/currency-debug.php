<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔍 CURRENCY ENCODING DIAGNOSTIC\n";
echo "================================\n\n";

// 1. Check database charset
echo "1. DATABASE CHARSET:\n";
echo "--------------------\n";
$dbConfig = DB::connection()->getConfig();
echo "Database: {$dbConfig['database']}\n";
echo "Charset: " . (isset($dbConfig['charset']) ? $dbConfig['charset'] : 'NOT SET') . "\n";
echo "Collation: " . (isset($dbConfig['collation']) ? $dbConfig['collation'] : 'NOT SET') . "\n";

// Check actual database charset
$charset = DB::select("SHOW VARIABLES LIKE 'character_set_database'");
$collation = DB::select("SHOW VARIABLES LIKE 'collation_database'");
echo "DB Charset (actual): " . ($charset[0]->Value ?? 'UNKNOWN') . "\n";
echo "DB Collation (actual): " . ($collation[0]->Value ?? 'UNKNOWN') . "\n";

// 2. Check currency settings
echo "\n2. CURRENCY SETTINGS:\n";
echo "---------------------\n";
$currencySettings = DB::table('settings')
    ->whereIn('key', ['site_currency', 'site_currency_symbol', 'site_currency_symbol_position', 'defult_currancy'])
    ->get();

foreach ($currencySettings as $setting) {
    echo "{$setting->key}:\n";
    echo "  Value: {$setting->value}\n";
    echo "  Business: {$setting->business}\n";
    echo "  Hex: " . bin2hex($setting->value) . "\n";
    echo "  UTF-8: " . (mb_check_encoding($setting->value, 'UTF-8') ? '✅ YES' : '❌ NO') . "\n";
    echo "\n";
}

// 3. Check settings table charset
echo "3. SETTINGS TABLE CHARSET:\n";
echo "--------------------------\n";
$tableCharset = DB::select("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'settings'", [$dbConfig['database']]);
if (!empty($tableCharset)) {
    echo "Table Collation: {$tableCharset[0]->TABLE_COLLATION}\n";
}

$columns = DB::select("SELECT COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'value'", [$dbConfig['database']]);
if (!empty($columns)) {
    echo "Column 'value' Charset: " . ($columns[0]->CHARACTER_SET_NAME ?? 'NULL') . "\n";
    echo "Column 'value' Collation: " . ($columns[0]->COLLATION_NAME ?? 'NULL') . "\n";
}

// 4. Test currency display
echo "\n4. CURRENCY DISPLAY TEST:\n";
echo "-------------------------\n";
$adminSettings = getAdminAllSetting();
echo "Currency: " . ($adminSettings['site_currency'] ?? 'NOT SET') . "\n";
echo "Symbol: " . ($adminSettings['site_currency_symbol'] ?? 'NOT SET') . "\n";
echo "Position: " . ($adminSettings['site_currency_symbol_position'] ?? 'NOT SET') . "\n";

// Test currency format
if (function_exists('currency_format_with_sym')) {
    echo "\nFormatting 2000:\n";
    echo "  Result: " . currency_format_with_sym(2000) . "\n";
}

// 5. Check PHP encoding
echo "\n5. PHP ENCODING:\n";
echo "----------------\n";
echo "mb_internal_encoding: " . mb_internal_encoding() . "\n";
echo "default_charset: " . ini_get('default_charset') . "\n";

echo "\n================================\n";
echo "Diagnostic complete!\n";
