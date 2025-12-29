<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== APPOINTMENT BOOKING DIAGNOSTICS ===\n\n";

// Check database connection
try {
    DB::connection()->getPdo();
    echo "✓ Database Connection: OK\n";
} catch (\Exception $e) {
    echo "✗ Database Connection: FAILED - " . $e->getMessage() . "\n";
}

// Check required tables
$tables = ['appointments', 'customers', 'users', 'businesses', 'services', 'staff', 'locations', 'roles'];
echo "\n--- Checking Tables ---\n";
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "✓ $table: EXISTS ($count records)\n";
    } else {
        echo "✗ $table: MISSING\n";
    }
}

// Check appointments table structure
echo "\n--- Checking 'appointments' Table Columns ---\n";
if (Schema::hasTable('appointments')) {
    $columns = Schema::getColumnListing('appointments');
    $requiredColumns = ['id', 'customer_id', 'location_id', 'service_id', 'staff_id', 'date', 'time', 'business_id', 'created_by'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✓ Column '$col': EXISTS\n";
        } else {
            echo "✗ Column '$col': MISSING\n";
        }
    }
}

// Check storage permissions
echo "\n--- Checking Storage Permissions ---\n";
$dirs = ['storage/logs', 'storage/framework/sessions', 'storage/framework/cache'];
foreach ($dirs as $dir) {
    if (is_writable(__DIR__ . '/' . $dir)) {
        echo "✓ $dir: Writable\n";
    } else {
        echo "✗ $dir: NOT Writable\n";
    }
}

// Check recent errors
echo "\n--- Last 20 Lines of Error Log ---\n";
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        echo $line;
    }
} else {
    echo "No log file found\n";
}

echo "\n=== END DIAGNOSTICS ===\n";
