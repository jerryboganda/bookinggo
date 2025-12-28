<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 HOLISTIC LOGO FIX\n";
echo "====================\n\n";

// Step 1: Verify files exist
echo "Step 1: Checking if logo files exist...\n";
$settings = \App\Models\Setting::whereIn('key', ['logo_dark', 'logo_light', 'favicon'])
    ->where('business', 0)
    ->get();

$missingFiles = [];
$existingFiles = [];

foreach ($settings as $setting) {
    $fullPath = base_path($setting->value);
    if (file_exists($fullPath)) {
        $existingFiles[] = $setting->key;
        echo "  ✅ {$setting->key}: {$setting->value} EXISTS\n";
    } else {
        $missingFiles[] = $setting->key;
        echo "  ❌ {$setting->key}: {$setting->value} MISSING\n";
        
        // Try to find the file
        $logoDir = base_path('uploads/logo');
        if (is_dir($logoDir)) {
            $files = glob($logoDir . '/' . $setting->key . '*');
            if (!empty($files)) {
                $latestFile = end($files);
                $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $latestFile);
                $relativePath = str_replace('\\', '/', $relativePath);
                echo "    → Found alternative: {$relativePath}\n";
                echo "    → Updating database...\n";
                $setting->value = $relativePath;
                $setting->save();
                echo "    ✅ Database updated!\n";
                $existingFiles[] = $setting->key;
            }
        }
    }
}

// Step 2: Check for storage setting
echo "\nStep 2: Checking storage configuration...\n";
$storageSetting = \App\Models\Setting::where('key', 'storage_setting')
    ->where('business', 0)
    ->first();

if ($storageSetting) {
    echo "  Storage setting: {$storageSetting->value}\n";
    if ($storageSetting->value !== 'local') {
        echo "  ⚠️  Storage is set to {$storageSetting->value}, changing to 'local'...\n";
        $storageSetting->value = 'local';
        $storageSetting->save();
        echo "  ✅ Changed to local storage\n";
    } else {
        echo "  ✅ Already using local storage\n";
    }
} else {
    echo "  ⚠️  No storage setting found, creating one...\n";
    \App\Models\Setting::create([
        'key' => 'storage_setting',
        'value' => 'local',
        'business' => 0,
        'created_by' => 1
    ]);
    echo "  ✅ Created local storage setting\n";
}

// Step 3: Clear caches
echo "\nStep 3: Clearing caches...\n";
try {
    Artisan::call('cache:clear');
    echo "  ✅ Cache cleared\n";
} catch (\Exception $e) {
    echo "  ⚠️  Cache clear error: " . $e->getMessage() . "\n";
}

try {
    Artisan::call('config:clear');
    echo "  ✅ Config cache cleared\n";
} catch (\Exception $e) {
    echo "  ⚠️  Config clear error: " . $e->getMessage() . "\n";
}

try {
    Artisan::call('view:clear');
    echo "  ✅ View cache cleared\n";
} catch (\Exception $e) {
    echo "  ⚠️  View clear error: " . $e->getMessage() . "\n";
}

// Step 4: Test helpers
echo "\nStep 4: Testing helper functions...\n";
$adminSettings = getAdminAllSetting();

foreach (['logo_dark', 'logo_light', 'favicon'] as $key) {
    if (isset($adminSettings[$key])) {
        $value = $adminSettings[$key];
        $fullPath = base_path($value);
        $fileExists = file_exists($fullPath);
        $checkFile = check_file($value);
        $url = get_file($value);
        
        echo "  {$key}:\n";
        echo "    Value: {$value}\n";
        echo "    Full Path: {$fullPath}\n";
        echo "    file_exists(): " . ($fileExists ? "✅ YES" : "❌ NO") . "\n";
        echo "    check_file(): " . ($checkFile ? "✅ YES" : "❌ NO") . "\n";
        echo "    get_file(): {$url}\n";
    }
}

// Step 5: Summary
echo "\n====================\n";
echo "SUMMARY:\n";
echo "  Files found: " . count($existingFiles) . "/3\n";
echo "  Storage: local\n";
echo "  Caches: cleared\n";
echo "\n";

if (count($existingFiles) == 3) {
    echo "✅ ALL LOGOS ARE WORKING!\n";
    echo "\nPlease refresh your browser (Ctrl+Shift+R) to see the logos.\n";
} else {
    echo "⚠️  Some logos are still missing. Please re-upload them.\n";
}

echo "\n====================\n";
