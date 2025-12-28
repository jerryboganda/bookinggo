<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COMPREHENSIVE SETTINGS DEBUG ===\n\n";

// 1. Check Laravel logs for recent entries
echo "1. Checking recent Laravel log entries...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = substr($logs, -2000); // Last 2000 characters
    if (strpos($recentLogs, 'SettingsController::store called') !== false) {
        echo "   ✓ Controller is being reached (found in logs)\n";
    } else {
        echo "   ❌ Controller NOT being reached (no logs found)\n";
    }
} else {
    echo "   ❌ Log file not found\n";
}

// 2. Check settings table structure
echo "\n2. Checking settings table structure...\n";
$columns = \Schema::getColumnListing('settings');
echo "   Columns: " . implode(', ', $columns) . "\n";

// 3. Check existing settings data
echo "\n3. Checking existing settings data...\n";
$colorSettings = \App\Models\Setting::where('key', 'color')->get();
echo "   Color settings in DB:\n";
foreach ($colorSettings as $setting) {
    echo "   - ID: {$setting->id}, Business: {$setting->business}, Created By: {$setting->created_by}, Value: {$setting->value}\n";
}

// 4. Check super admin details
echo "\n4. Checking super admin details...\n";
$superAdmin = \App\Models\User::where('type', 'super admin')->first();
echo "   Super Admin ID: {$superAdmin->id}\n";
echo "   Active Business: {$superAdmin->active_business}\n";

// 5. Test creatorId() function
echo "\n5. Testing creatorId() function...\n";
auth()->loginUsingId($superAdmin->id);
$creatorId = creatorId();
echo "   creatorId() returns: {$creatorId}\n";

// 6. Test getActiveBusiness() function
echo "\n6. Testing getActiveBusiness() function...\n";
$activeBusiness = getActiveBusiness();
echo "   getActiveBusiness() returns: {$activeBusiness}\n";

// 7. Simulate a settings save
echo "\n7. Simulating settings save...\n";
$testKey = 'test_color_' . time();
$testValue = 'theme-test';

$data = [
    'key' => $testKey,
    'business' => auth()->user()->type == 'super admin' ? 0 : getActiveBusiness(),
    'created_by' => creatorId(),
];

echo "   Data to save:\n";
echo "   - key: {$data['key']}\n";
echo "   - business: {$data['business']}\n";
echo "   - created_by: {$data['created_by']}\n";

// Try to save
try {
    \App\Models\Setting::updateOrInsert($data, ['value' => $testValue]);
    echo "   ✓ Save operation completed\n";
    
    // Verify it was saved
    $saved = \App\Models\Setting::where($data)->first();
    if ($saved && $saved->value == $testValue) {
        echo "   ✓ Verified: Setting saved correctly\n";
    } else {
        echo "   ❌ ERROR: Setting not found or value mismatch\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// 8. Check retrieval
echo "\n8. Testing retrieval...\n";
$retrieved = getAdminAllSetting($testKey);
echo "   Retrieved value: " . ($retrieved ?? 'NULL') . "\n";

// 9. Check for duplicate helper files
echo "\n9. Checking for duplicate helper files...\n";
if (file_exists(__DIR__ . '/app/Helpers/helpers.php')) {
    echo "   ❌ CONFLICT: app/Helpers/helpers.php exists (should be deleted)\n";
} else {
    echo "   ✓ No conflicting helper file\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "Review the output above to identify the issue.\n";
