<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING DATABASE TABLES ===\n\n";

// Check if appointments table exists
$tables = DB::select("SHOW TABLES LIKE 'appointments'");
if (count($tables) > 0) {
    echo "✓ appointments table EXISTS\n";
    
    // Check structure
    $columns = DB::select("DESCRIBE appointments");
    echo "\n=== Appointments Table Structure ===\n";
    foreach ($columns as $col) {
        echo "{$col->Field} ({$col->Type}) - Null: {$col->Null}, Key: {$col->Key}\n";
    }
    
    // Check count
    $count = DB::table('appointments')->count();
    echo "\n=== Appointments Count ===\n";
    echo "Total appointments: {$count}\n";
    
    // Check for business_id = 2
    $countBusiness2 = DB::table('appointments')->where('business_id', 2)->count();
    echo "Appointments for business_id = 2: {$countBusiness2}\n";
    
} else {
    echo "✗ appointments table DOES NOT EXIST\n";
    echo "Running migrations...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
}
?>
