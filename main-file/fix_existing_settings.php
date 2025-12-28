<?php

/**
 * Fix Existing Super Admin Settings
 * 
 * This script migrates any super admin settings that were saved with
 * incorrect business IDs to business=0
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix Super Admin Settings Migration\n";
echo "========================================\n\n";

// Get super admin
$superAdmin = User::where('type', 'super admin')->first();
if (!$superAdmin) {
    echo "❌ ERROR: No super admin found!\n";
    exit(1);
}

echo "Super Admin: {$superAdmin->name} (ID: {$superAdmin->id})\n\n";

// Find settings with incorrect business ID
echo "1. Analyzing existing settings...\n";
$incorrectSettings = Setting::where('created_by', $superAdmin->id)
    ->where('business', '!=', 0)
    ->get();

$incorrectCount = $incorrectSettings->count();
echo "   Found $incorrectCount settings with business != 0\n\n";

if ($incorrectCount === 0) {
    echo "✓ No incorrect settings found. Nothing to fix!\n";
    exit(0);
}

echo "2. Incorrect settings found:\n";
foreach ($incorrectSettings->groupBy('business') as $businessId => $settings) {
    echo "   - Business ID $businessId: " . $settings->count() . " settings\n";
}

echo "\n3. Checking for conflicts...\n";
$conflicts = [];
foreach ($incorrectSettings as $setting) {
    $existingCorrect = Setting::where('key', $setting->key)
        ->where('business', 0)
        ->where('created_by', $superAdmin->id)
        ->first();
    
    if ($existingCorrect) {
        $conflicts[] = [
            'key' => $setting->key,
            'incorrect_value' => $setting->value,
            'correct_value' => $existingCorrect->value,
            'incorrect_business' => $setting->business,
        ];
    }
}

if (count($conflicts) > 0) {
    echo "   ⚠️  Found " . count($conflicts) . " conflicts (keys exist in both business=0 and business!=0)\n";
    echo "   These will be resolved by keeping business=0 version and deleting others.\n\n";
    
    foreach ($conflicts as $conflict) {
        echo "   Conflict: {$conflict['key']}\n";
        echo "     - Business 0 value: {$conflict['correct_value']}\n";
        echo "     - Business {$conflict['incorrect_business']} value: {$conflict['incorrect_value']}\n";
    }
} else {
    echo "   ✓ No conflicts found\n";
}

echo "\n4. Starting migration...\n";
echo "   This will:\n";
echo "   a) Delete settings where business=0 version already exists\n";
echo "   b) Update remaining settings to business=0\n\n";

// Confirm action
if (php_sapi_name() === 'cli') {
    echo "Do you want to proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'yes') {
        echo "Migration cancelled.\n";
        exit(0);
    }
}

DB::beginTransaction();
try {
    $deleted = 0;
    $updated = 0;
    
    foreach ($incorrectSettings as $setting) {
        $existingCorrect = Setting::where('key', $setting->key)
            ->where('business', 0)
            ->where('created_by', $superAdmin->id)
            ->first();
        
        if ($existingCorrect) {
            // Delete the incorrect version (keep business=0 version)
            $setting->delete();
            $deleted++;
            echo "   ✓ Deleted duplicate: {$setting->key} (business={$setting->business})\n";
        } else {
            // Update to business=0
            $setting->business = 0;
            $setting->save();
            $updated++;
            echo "   ✓ Updated: {$setting->key} -> business=0\n";
        }
    }
    
    DB::commit();
    
    echo "\n5. Migration complete!\n";
    echo "   - Settings updated: $updated\n";
    echo "   - Duplicate settings deleted: $deleted\n";
    echo "   - Total fixed: " . ($updated + $deleted) . "\n";
    
    // Clear cache
    echo "\n6. Clearing cache...\n";
    Cache::forget('admin_settings');
    AdminSettingCacheForget();
    echo "   ✓ Cache cleared\n";
    
    echo "\n========================================\n";
    echo "✓ MIGRATION SUCCESSFUL\n";
    echo "========================================\n\n";
    
    echo "All super admin settings are now correctly saved with business=0.\n";
    echo "The settings save functionality should work properly now.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR during migration: " . $e->getMessage() . "\n";
    echo "No changes were made to the database.\n";
    exit(1);
}
