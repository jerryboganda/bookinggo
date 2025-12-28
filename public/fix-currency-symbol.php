<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔧 FIXING CURRENCY SYMBOLS\n";
echo "==========================\n\n";

// Pakistani Rupee symbol
$correctSymbol = '₨'; // Unicode: U+20A8
// Alternative: 'Rs'

echo "1. Checking current symbols:\n";
$settings = DB::table('settings')->where('key', 'defult_currancy_symbol')->get();
foreach ($settings as $setting) {
    echo "  Business {$setting->business}: '{$setting->value}' (Hex: " . bin2hex($setting->value) . ")\n";
}

echo "\n2. Updating to correct symbol: {$correctSymbol}\n";
echo "   UTF-8 Hex: " . bin2hex($correctSymbol) . "\n\n";

// Update all currency symbols to correct Pakistani Rupee symbol
$updated = DB::table('settings')
    ->where('key', 'defult_currancy_symbol')
    ->update(['value' => $correctSymbol]);

echo "✅ Updated {$updated} records\n\n";

// Verify the update
echo "3. Verifying update:\n";
$settings = DB::table('settings')->where('key', 'defult_currancy_symbol')->get();
foreach ($settings as $setting) {
    echo "  Business {$setting->business}: '{$setting->value}' (Hex: " . bin2hex($setting->value) . ")\n";
}

// Clear caches
echo "\n4. Clearing caches...\n";
Artisan::call('cache:clear');
Artisan::call('config:clear');
echo "✅ Caches cleared\n";

// Test the display
echo "\n5. Testing currency display:\n";
$formatted = currency_format_with_sym(2000);
echo "   2000 formatted: {$formatted}\n";

echo "\n==========================\n";
echo "✅ Currency symbol fixed!\n";
echo "\nThe symbol '{$correctSymbol}' (Pakistani Rupee) is now set.\n";
echo "If you prefer 'Rs' instead, you can change it in settings.\n";
