<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🧪 TESTING CURRENCY DISPLAY\n";
echo "===========================\n\n";

// Clear caches programmatically
echo "Clearing function caches...\n";
if (function_exists('comapnySettingCacheForget')) {
    comapnySettingCacheForget(0);
    comapnySettingCacheForget(2);
    echo "✅ Company setting cache cleared\n";
}
if (function_exists('AdminSettingCacheForget')) {
    AdminSettingCacheForget();
    echo "✅ Admin setting cache cleared\n";
}

echo "\nTesting currency_format_with_sym():\n";
echo "-----------------------------------\n";

// Test without company
$result1 = currency_format_with_sym(2000);
echo "2000 (no company): {$result1}\n";

// Test with company 2
$result2 = currency_format_with_sym(2000, 3, 2);
echo "2000 (company 3, business 2): {$result2}\n";

// Check what getCompanyAllSetting returns
echo "\nChecking getCompanyAllSetting():\n";
echo "--------------------------------\n";
$settings = getCompanyAllSetting();
echo "defult_currancy_symbol: " . ($settings['defult_currancy_symbol'] ?? 'NOT SET') . "\n";
echo "defult_currancy: " . ($settings['defult_currancy'] ?? 'NOT SET') . "\n";
echo "site_currency_symbol_name: " . ($settings['site_currency_symbol_name'] ?? 'NOT SET') . "\n";
echo "site_currency_symbol_position: " . ($settings['site_currency_symbol_position'] ?? 'NOT SET') . "\n";
echo "currency_space: " . ($settings['currency_space'] ?? 'NOT SET') . "\n";

echo "\n===========================\n";
