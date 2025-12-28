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