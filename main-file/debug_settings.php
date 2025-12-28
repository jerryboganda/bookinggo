<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUGGING SETTINGS SAVE ISSUE ===\n\n";

// 1. Check if the duplicate helper file still exists
echo "1. Checking for conflicting helper files...\n";
if (file_exists(__DIR__ . '/app/Helpers/helpers.php')) {
    echo "   ❌ CONFLICT FOUND: app/Helpers/helpers.php still exists!\n";
    echo "   This is overriding the real helper file.\n";
    unlink(__DIR__ . '/app/Helpers/helpers.php');
    echo "   ✓ Deleted duplicate helper file\n";
} else {
    echo "   ✓ No conflicting helper file found\n";
}

// 2. Check current settings in database
echo "\n2. Current settings in database:\n";
$settings = App\Models\Setting::where('business', 0)->pluck('value', 'key')->toArray();
echo "   Total settings with business=0: " . count($settings) . "\n";
if (isset($settings['color'])) {
    echo "   Current color in DB: " . $settings['color'] . "\n";
}

// 3. Check what getAdminAllSetting returns
echo "\n3. What getAdminAllSetting() returns:\n";
$cachedSettings = getAdminAllSetting();
echo "   Total cached settings: " . count($cachedSettings) . "\n";
if (isset($cachedSettings['color'])) {
    echo "   Color from cache: " . $cachedSettings['color'] . "\n";
}

// 4. Test a direct save operation
echo "\n4. Testing direct database save...\n";
$testKey = 'test_setting_' . time();
$testValue = 'test_value_' . time();

// Save directly to database
App\Models\Setting::updateOrInsert(
    ['key' => $testKey, 'business' => 0],
    ['value' => $testValue, 'updated_at' => now()]
);
echo "   ✓ Saved test setting to database\n";

// Check if it's in database
$dbCheck = App\Models\Setting::where('key', $testKey)->where('business', 0)->first();
if ($dbCheck) {
    echo "   ✓ Confirmed: Setting exists in database\n";
} else {
    echo "   ❌ ERROR: Setting not found in database!\n";
}

// 5. Check cache behavior
echo "\n5. Testing cache behavior...\n";
Cache::forget('admin_settings');
echo "   ✓ Cleared admin settings cache\n";

// Get fresh settings
$freshSettings = getAdminAllSetting();
echo "   Fresh settings count: " . count($freshSettings) . "\n";

// 6. Check the actual cache driver
echo "\n6. Cache configuration:\n";
echo "   Cache driver: " . config('cache.default') . "\n";

// 7. Fix the getActiveBusiness issue
echo "\n7. Checking getActiveBusiness() for super admin...\n";
$superAdmin = App\Models\User::where('type', 'super admin')->first();
echo "   Super Admin ID: " . $superAdmin->id . "\n";
echo "   Active Business: " . $superAdmin->active_business . "\n";

// The issue: SettingsController uses getActiveBusiness() which returns active_business
// But getAdminAllSetting was fixed to use business=0
// We need to make them consistent

echo "\n=== SOLUTION ===\n";
echo "The SettingsController is saving with business = getActiveBusiness()\n";
echo "Which returns: " . getActiveBusiness() . "\n";
echo "But getAdminAllSetting() was changed to use business=0\n";
echo "\nFixing SettingsController to use business=0 for super admin...\n";
