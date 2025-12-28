<?php

/**
 * FIX SETTINGS - One-time script to fix all settings issues
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "===========================================\n";
echo "FIX ALL SETTINGS ISSUES\n";
echo "===========================================\n\n";

// Get super admin
$superAdmin = User::where('type', 'super admin')->first();
if (!$superAdmin) {
    echo "❌ No super admin found!\n";
    exit(1);
}

echo "Super Admin: {$superAdmin->name} (ID: {$superAdmin->id})\n\n";

// Step 1: Fix all settings with wrong business ID
echo "STEP 1: Fixing business IDs...\n";
$incorrectBusinessSettings = Setting::where('created_by', $superAdmin->id)
    ->where('business', '!=', 0)
    ->get();

echo "Found {$incorrectBusinessSettings->count()} settings with wrong business ID\n";

if ($incorrectBusinessSettings->count() > 0) {
    DB::beginTransaction();
    try {
        foreach ($incorrectBusinessSettings as $setting) {
            echo "  Fixing: {$setting->key} (business={$setting->business} -> 0)\n";
            $setting->business = 0;
            $setting->save();
        }
        DB::commit();
        echo "✓ Fixed {$incorrectBusinessSettings->count()} settings\n\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "✓ No settings to fix\n\n";
}

// Step 2: Check for duplicate settings
echo "STEP 2: Checking for duplicates...\n";
$duplicates = DB::table('settings')
    ->select('key', 'business', 'created_by', DB::raw('COUNT(*) as count'))
    ->where('created_by', $superAdmin->id)
    ->where('business', 0)
    ->groupBy('key', 'business', 'created_by')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->count() > 0) {
    echo "Found {$duplicates->count()} duplicate keys\n";
    
    DB::beginTransaction();
    try {
        foreach ($duplicates as $dup) {
            echo "  Removing duplicates for: {$dup->key}\n";
            
            // Keep the most recent one
            $settings = Setting::where([
                'key' => $dup->key,
                'business' => 0,
                'created_by' => $superAdmin->id
            ])->orderBy('updated_at', 'desc')->get();
            
            $keepFirst = true;
            foreach ($settings as $setting) {
                if ($keepFirst) {
                    $keepFirst = false;
                    continue;
                }
                echo "    Deleting duplicate ID: {$setting->id}\n";
                $setting->delete();
            }
        }
        DB::commit();
        echo "✓ Removed all duplicates\n\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "✓ No duplicates found\n\n";
}

// Step 3: Clear cache
echo "STEP 3: Clearing cache...\n";
Cache::forget('admin_settings');
Cache::flush();
echo "✓ Cache cleared\n\n";

// Step 4: Verify
echo "STEP 4: Verification...\n";
$totalSettings = Setting::where('created_by', $superAdmin->id)
    ->where('business', 0)
    ->count();
    
$wrongBusinessCount = Setting::where('created_by', $superAdmin->id)
    ->where('business', '!=', 0)
    ->count();

echo "Total settings with business=0: $totalSettings\n";
echo "Settings with wrong business ID: $wrongBusinessCount\n";

if ($wrongBusinessCount == 0) {
    echo "\n✓✓✓ SUCCESS! All settings fixed!\n";
    echo "\nYou can now:\n";
    echo "1. Go to your super admin settings page\n";
    echo "2. Make any changes\n";
    echo "3. Click Save\n";
    echo "4. Changes should now persist!\n";
} else {
    echo "\n⚠️ There are still $wrongBusinessCount settings with wrong business ID\n";
}

echo "\n===========================================\n";
echo "DONE\n";
echo "===========================================\n";
