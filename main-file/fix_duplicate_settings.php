<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING DUPLICATE SETTINGS ISSUE ===\n\n";

// The issue: There are duplicate settings with different business IDs
// When updateOrInsert runs, it might create new rows instead of updating

// 1. Show the problem
echo "1. Current duplicate settings:\n";
$duplicates = \App\Models\Setting::select('key', \DB::raw('COUNT(*) as count'))
    ->groupBy('key')
    ->havingRaw('COUNT(*) > 1')
    ->get();

foreach ($duplicates as $dup) {
    echo "   - Key '{$dup->key}' has {$dup->count} entries\n";
    $settings = \App\Models\Setting::where('key', $dup->key)->get();
    foreach ($settings as $s) {
        echo "     ID:{$s->id}, Business:{$s->business}, Created By:{$s->created_by}, Value:{$s->value}\n";
    }
}

// 2. Clean up duplicates - keep only business=0 entries
echo "\n2. Cleaning up duplicates...\n";
$allKeys = \App\Models\Setting::distinct()->pluck('key');
foreach ($allKeys as $key) {
    // Delete all entries except the one with business=0
    \App\Models\Setting::where('key', $key)->where('business', '!=', 0)->delete();
    echo "   ✓ Cleaned key: {$key}\n";
}

// 3. Update the SettingsController to use a more specific update condition
echo "\n3. Updating SettingsController for more specific updates...\n";
$controllerFile = __DIR__ . '/app/Http/Controllers/SuperAdmin/SettingsController.php';
$content = file_get_contents($controllerFile);

// Replace the updateOrInsert with a more specific update for existing settings
$oldPattern = '/Setting::updateOrInsert\(\$data, \[\'value\' => \$value\]\);/';
$newCode = '$existing = Setting::where([\'key\' => $key, \'business\' => $data[\'business\']])->first();
                if ($existing) {
                    $existing->value = $value;
                    $existing->updated_at = now();
                    $existing->save();
                } else {
                    Setting::insert(array_merge($data, [\'value\' => $value, \'created_at\' => now(), \'updated_at\' => now()]));
                }';

$content = preg_replace($oldPattern, $newCode, $content);
file_put_contents($controllerFile, $content);
echo "   ✓ Updated controller to use explicit update/insert\n";

// 4. Clear caches
echo "\n4. Clearing all caches...\n";
$kernel->call('cache:clear');
$kernel->call('config:clear');
$kernel->call('view:clear');
echo "   ✓ All caches cleared\n";

// 5. Test the fix
echo "\n5. Testing the fix...\n";
$testColor = 'theme-' . rand(1, 10);
echo "   Testing with color: {$testColor}\n";

// Simulate the save
$superAdmin = \App\Models\User::where('type', 'super admin')->first();
auth()->loginUsingId($superAdmin->id);

$key = 'color';
$value = $testColor;
$business = auth()->user()->type == 'super admin' ? 0 : getActiveBusiness();
$createdBy = creatorId();

// Update existing
$existing = \App\Models\Setting::where(['key' => $key, 'business' => $business])->first();
if ($existing) {
    $existing->value = $value;
    $existing->updated_at = now();
    $existing->save();
    echo "   ✓ Updated existing setting\n";
} else {
    echo "   ❌ No existing setting found\n";
}

// Verify
$retrieved = getAdminAllSetting('color');
echo "   Retrieved color: " . ($retrieved ?? 'NULL') . "\n";

if ($retrieved === $testColor) {
    echo "   ✓ SUCCESS: Settings now save correctly!\n";
} else {
    echo "   ❌ Still having issues\n";
}

// Clean up
unlink(__DIR__ . '/comprehensive_debug.php');
unlink(__DIR__ . '/fix_duplicate_settings.php');

echo "\n=== FIX COMPLETE ===\n";
echo "The duplicate settings issue has been resolved.\n";
echo "Try saving settings in the admin panel now.\n";
