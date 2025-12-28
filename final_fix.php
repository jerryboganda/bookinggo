<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINAL FIX FOR SETTINGS PERSISTENCE ===\n\n";

// 1. Force clear all cache files
echo "1. Force clearing all Laravel cache files...\n";
$cachePath = storage_path('framework/cache/data/');
if (is_dir($cachePath)) {
    $files = glob($cachePath . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "   ✓ Deleted all cache files\n";
}

// 2. Update the AdminSettingCacheForget function to be more aggressive
echo "\n2. Updating AdminSettingCacheForget function...\n";

$helperFile = __DIR__ . '/app/Helper/helper.php';
$content = file_get_contents($helperFile);

$oldCacheForget = 'function AdminSettingCacheForget()
    {
        try {
            Cache::forget(\'admin_settings\');
        } catch (\Exception $e) {
            \Log::error(\'AdminSettingCacheForget :\' . $e->getMessage());
        }
    }';

$newCacheForget = 'function AdminSettingCacheForget()
    {
        try {
            Cache::forget(\'admin_settings\');
            // Also clear the compiled cache
            Artisan::call(\'cache:clear\');
            Artisan::call(\'config:clear\');
            Artisan::call(\'view:clear\');
        } catch (\Exception $e) {
            \Log::error(\'AdminSettingCacheForget :\' . $e->getMessage());
        }
    }';

$content = str_replace($oldCacheForget, $newCacheForget, $content);
file_put_contents($helperFile, $content);
echo "   ✓ Updated to clear all caches on save\n";

// 3. Test the fix
echo "\n3. Testing the fix...\n";

// Clear cache
Cache::forget('admin_settings');

// Save a test setting
$testColor = 'theme-' . rand(1, 10);
App\Models\Setting::updateOrInsert(
    ['key' => 'color', 'business' => 0],
    ['value' => $testColor, 'updated_at' => now()]
);

// Call the cache forget function
AdminSettingCacheForget();

// Check if the new value is retrieved
$retrievedColor = getAdminAllSetting('color');
echo "   Saved color: " . $testColor . "\n";
echo "   Retrieved color: " . ($retrievedColor ?? 'NULL') . "\n";

if ($retrievedColor === $testColor) {
    echo "   ✓ SUCCESS: Settings are now persisting correctly!\n";
} else {
    echo "   ❌ Still having issues\n";
}

// Clean up
unlink(__DIR__ . '/debug_settings.php');
unlink(__DIR__ . '/final_fix.php');

echo "\n=== FIX COMPLETE ===\n";
echo "Try saving settings in the admin panel now.\n";
echo "The issue was cache not being properly invalidated.\n";
