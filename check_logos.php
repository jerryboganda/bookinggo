<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 COMPREHENSIVE LOGO DIAGNOSTICS\n";
echo "=================================\n\n";

// 1. Check database settings
echo "1. DATABASE SETTINGS:\n";
echo "---------------------\n";
$settings = \App\Models\Setting::whereIn('key', ['logo_dark', 'logo_light', 'favicon'])
    ->orderBy('key')
    ->get(['id', 'key', 'value', 'business', 'created_by']);

if ($settings->isEmpty()) {
    echo "❌ NO logo settings found in database!\n";
} else {
    foreach ($settings as $setting) {
        echo "  {$setting->key}:\n";
        echo "    ID: {$setting->id}\n";
        echo "    Value: {$setting->value}\n";
        echo "    Business: {$setting->business}\n";
        echo "    Created By: {$setting->created_by}\n";
        
        // Check if file exists
        if (!empty($setting->value)) {
            $fullPath = storage_path('app/public/' . $setting->value);
            $publicPath = public_path($setting->value);
            
            echo "    Storage Path: {$fullPath}\n";
            echo "    Storage Exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "\n";
            echo "    Public Path: {$publicPath}\n";
            echo "    Public Exists: " . (file_exists($publicPath) ? "✅ YES" : "❌ NO") . "\n";
        }
        echo "\n";
    }
}

// 2. Check for duplicates
echo "\n2. DUPLICATE CHECK:\n";
echo "-------------------\n";
$allSettings = \App\Models\Setting::where('business', 0)
    ->whereIn('key', ['logo_dark', 'logo_light', 'favicon'])
    ->get(['id', 'key', 'value', 'created_by']);

$grouped = $allSettings->groupBy('key');
foreach ($grouped as $key => $group) {
    if ($group->count() > 1) {
        echo "⚠️  DUPLICATE: {$key} has {$group->count()} entries:\n";
        foreach ($group as $item) {
            echo "    ID: {$item->id}, Value: {$item->value}, Created By: {$item->created_by}\n";
        }
    } else {
        echo "✅ {$key}: No duplicates\n";
    }
}

// 3. Check uploads directory
echo "\n3. FILE SYSTEM CHECK:\n";
echo "---------------------\n";
$uploadDirs = [
    'storage/app/public/uploads/logo' => storage_path('app/public/uploads/logo'),
    'public/uploads/logo' => public_path('uploads/logo'),
    'uploads/logo' => __DIR__ . '/uploads/logo',
];

foreach ($uploadDirs as $label => $path) {
    echo "{$label}:\n";
    echo "  Path: {$path}\n";
    echo "  Exists: " . (is_dir($path) ? "✅ YES" : "❌ NO") . "\n";
    
    if (is_dir($path)) {
        $files = scandir($path);
        $files = array_diff($files, ['.', '..']);
        echo "  Files: " . count($files) . " files\n";
        if (count($files) > 0) {
            foreach ($files as $file) {
                echo "    - {$file}\n";
            }
        }
    }
    echo "\n";
}

// 4. Check helper functions
echo "\n4. HELPER FUNCTION TEST:\n";
echo "------------------------\n";
try {
    $adminSettings = getAdminAllSetting();
    echo "getAdminAllSetting() returned " . count($adminSettings) . " settings\n";
    echo "  logo_dark: " . ($adminSettings['logo_dark'] ?? 'NOT SET') . "\n";
    echo "  logo_light: " . ($adminSettings['logo_light'] ?? 'NOT SET') . "\n";
    echo "  favicon: " . ($adminSettings['favicon'] ?? 'NOT SET') . "\n";
} catch (\Exception $e) {
    echo "❌ Error calling getAdminAllSetting(): " . $e->getMessage() . "\n";
}

// 5. Check storage link
echo "\n5. STORAGE LINK CHECK:\n";
echo "----------------------\n";
$storageLink = public_path('storage');
echo "Public storage link: {$storageLink}\n";
echo "Is Link: " . (is_link($storageLink) ? "✅ YES" : "❌ NO") . "\n";
echo "Exists: " . (file_exists($storageLink) ? "✅ YES" : "❌ NO") . "\n";
if (is_link($storageLink)) {
    echo "Points to: " . readlink($storageLink) . "\n";
}

echo "\n=================================\n";
echo "Diagnostic complete!\n";
