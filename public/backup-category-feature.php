<?php
/**
 * BACKUP SCRIPT FOR CATEGORY BOOKING FEATURE
 * Creates timestamped backup of all files that will be modified
 * Includes restore script for instant rollback
 */

$backupDir = __DIR__ . '/../backups/category-feature-' . date('Ymd_His');
$filesToBackup = [
    'resources/views/web_layouts/appointment-form.blade.php',
    'resources/views/web_layouts/app.blade.php',
    'app/Http/Controllers/AppointmentController.php',
    'routes/web.php'
];

echo "🔧 CATEGORY BOOKING FEATURE - BACKUP SYSTEM\n";
echo "============================================\n\n";

// Create backup directory
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "✅ Created backup directory: {$backupDir}\n\n";
}

echo "📦 Backing up files...\n";
echo "---------------------\n";

$backupLog = [];

foreach ($filesToBackup as $file) {
    $sourcePath = __DIR__ . '/../' . $file;
    $backupPath = $backupDir . '/' . str_replace('/', '_', $file);
    
    if (file_exists($sourcePath)) {
        $backupDir_file = dirname($backupPath);
        if (!file_exists($backupDir_file)) {
            mkdir($backupDir_file, 0755, true);
        }
        
        if (copy($sourcePath, $backupPath)) {
            $size = filesize($sourcePath);
            echo "✅ {$file} (" . number_format($size) . " bytes)\n";
            $backupLog[] = [
                'file' => $file,
                'backup' => $backupPath,
                'size' => $size,
                'hash' => md5_file($sourcePath)
            ];
        } else {
            echo "❌ FAILED: {$file}\n";
        }
    } else {
        echo "⚠️  NOT FOUND: {$file}\n";
    }
}

// Save backup log
file_put_contents(
    $backupDir . '/backup-log.json',
    json_encode($backupLog, JSON_PRETTY_PRINT)
);

// Create restore script
$restoreScript = <<<'PHP'
<?php
/**
 * RESTORE SCRIPT - ROLLBACK CATEGORY FEATURE CHANGES
 * Run this file to restore all backed up files
 */

$backupDir = __DIR__;
$projectRoot = dirname(dirname(__DIR__));

echo "🔄 RESTORING BACKUP\n";
echo "===================\n\n";

$logFile = $backupDir . '/backup-log.json';
if (!file_exists($logFile)) {
    die("❌ Backup log not found!\n");
}

$backupLog = json_decode(file_get_contents($logFile), true);

echo "Found " . count($backupLog) . " files to restore\n\n";

$restored = 0;
$failed = 0;

foreach ($backupLog as $entry) {
    $targetPath = $projectRoot . '/' . $entry['file'];
    $backupPath = $entry['backup'];
    
    if (file_exists($backupPath)) {
        // Verify backup integrity
        if (md5_file($backupPath) === $entry['hash']) {
            if (copy($backupPath, $targetPath)) {
                echo "✅ Restored: {$entry['file']}\n";
                $restored++;
            } else {
                echo "❌ FAILED: {$entry['file']}\n";
                $failed++;
            }
        } else {
            echo "⚠️  CHECKSUM MISMATCH: {$entry['file']}\n";
            $failed++;
        }
    } else {
        echo "❌ BACKUP MISSING: {$entry['file']}\n";
        $failed++;
    }
}

echo "\n===================\n";
echo "✅ Restored: {$restored}\n";
echo "❌ Failed: {$failed}\n\n";

if ($failed === 0) {
    echo "🎉 ALL FILES RESTORED SUCCESSFULLY!\n";
    echo "\nNext steps:\n";
    echo "1. Clear Laravel caches: php artisan cache:clear\n";
    echo "2. Clear view cache: php artisan view:clear\n";
    echo "3. Test the booking form\n";
} else {
    echo "⚠️  SOME FILES FAILED TO RESTORE!\n";
    echo "Please check manually and restore from:\n";
    echo $backupDir . "\n";
}
PHP;

file_put_contents($backupDir . '/RESTORE.php', $restoreScript);

echo "\n============================================\n";
echo "✅ BACKUP COMPLETE!\n\n";
echo "Backup location: {$backupDir}\n";
echo "Files backed up: " . count($backupLog) . "\n\n";
echo "🔄 TO ROLLBACK (if needed):\n";
echo "   Run: php " . $backupDir . "/RESTORE.php\n\n";
echo "============================================\n";
