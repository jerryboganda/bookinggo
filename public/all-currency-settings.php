<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔍 ALL CURRENCY SETTINGS\n";
echo "========================\n\n";

$settings = DB::table('settings')->where('key', 'like', '%curr%')->get();

foreach ($settings as $s) {
    echo "{$s->key}:\n";
    echo "  Value: {$s->value}\n";
    echo "  Business: {$s->business}\n";
    echo "  Created By: {$s->created_by}\n";
    echo "  Hex: " . bin2hex($s->value) . "\n";
    echo "\n";
}

// Check the currency helper
echo "========================\n";
echo "CHECKING HELPER FUNCTION:\n";
echo "========================\n\n";

$file = file_get_contents(base_path('app/Helper/helper.php'));
if (strpos($file, 'currency_format_with_sym') !== false) {
    echo "✅ currency_format_with_sym function exists\n";
    
    // Extract the function
    preg_match('/function currency_format_with_sym.*?^}/ms', $file, $matches);
    if (!empty($matches)) {
        echo "\nFunction code:\n";
        echo substr($matches[0], 0, 500) . "...\n";
    }
}
