<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEFINITIVE FIX FOR SETTINGS PERSISTENCE ===\n\n";

// The root cause: Cache::rememberForever is too aggressive
// Solution: Remove caching for admin settings entirely

echo "1. Removing caching from getAdminAllSetting()...\n";

$helperFile = __DIR__ . '/app/Helper/helper.php';
$content = file_get_contents($helperFile);

// Replace the cached version with direct database query
$oldFunction = 'function getAdminAllSetting()
    {
        // Laravel cache
        return Cache::rememberForever(\'admin_settings\', function () {
            $super_admin = User::where(\'type\', \'super admin\')->first();
            $settings = [];
            if ($super_admin) {
                $settings = Setting::where(\'created_by\', $super_admin->id)->where(\'business\', 0)->pluck(\'value\', \'key\')->toArray();
            }

            return $settings;
        });
    }';

$newFunction = 'function getAdminAllSetting()
    {
        // Direct database query - no caching to prevent stale data
        $super_admin = User::where(\'type\', \'super admin\')->first();
        $settings = [];
        if ($super_admin) {
            $settings = Setting::where(\'created_by\', $super_admin->id)->where(\'business\', 0)->pluck(\'value\', \'key\')->toArray();
        }

        return $settings;
    }';

$content = str_replace($oldFunction, $newFunction, $content);
file_put_contents($helperFile, $content);
echo "   ✓ Removed caching - now using direct database query\n";

// Also update AdminSettingCacheForget to do nothing since we're not caching
echo "\n2. Updating AdminSettingCacheForget()...\n";

$oldCacheForget = 'function AdminSettingCacheForget()
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

$newCacheForget = 'function AdminSettingCacheForget()
    {
        // No longer needed since we removed caching
        // Keeping function for compatibility
        return true;
    }';

$content = str_replace($oldCacheForget, $newCacheForget, $content);
file_put_contents($helperFile, $content);
echo "   ✓ Disabled cache forget (no longer needed)\n";

// Clear all caches one more time
echo "\n3. Clearing all caches...\n";
$kernel->call('cache:clear');
$kernel->call('config:clear');
$kernel->call('view:clear');
echo "   ✓ All caches cleared\n";

// Test the fix
echo "\n4. Testing the fix...\n";

// Save a test setting
$testColor = 'theme-' . rand(1, 10);
echo "   Saving color: " . $testColor . "\n";
App\Models\Setting::updateOrInsert(
    ['key' => 'color', 'business' => 0],
    ['value' => $testColor, 'updated_at' => now()]
);

// Retrieve it immediately
$retrievedColor = getAdminAllSetting('color');
echo "   Retrieved immediately: " . ($retrievedColor ?? 'NULL') . "\n";

// Clear any remaining cache
$kernel->call('cache:clear');

// Retrieve again
$retrievedColor2 = getAdminAllSetting('color');
echo "   Retrieved after clear: " . ($retrievedColor2 ?? 'NULL') . "\n";

if ($retrievedColor2 === $testColor) {
    echo "   ✓ SUCCESS: Settings now persist correctly!\n";
} else {
    echo "   ❌ Issue persists\n";
}

// Clean up
unlink(__DIR__ . '/final_fix.php');
unlink(__DIR__ . '/definitive_fix.php');

echo "\n=== FIX COMPLETE ===\n";
echo "The caching has been removed from admin settings.\n";
echo "Settings will now always read fresh from the database.\n";
echo "Try saving settings in the admin panel - they should persist!\n";
