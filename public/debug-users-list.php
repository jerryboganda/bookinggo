<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔍 DEBUGGING USERS LIST VIEW\n";
echo "==============================\n\n";

// Simulate authenticated super admin
$superAdmin = \App\Models\User::where('type', 'super admin')->first();
if (!$superAdmin) {
    echo "❌ No super admin found!\n";
    exit;
}

echo "Testing as: {$superAdmin->name} (type: {$superAdmin->type})\n\n";

// Check database directly
echo "1. DIRECT DATABASE QUERY:\n";
echo "-------------------------\n";
$companyUsers = \App\Models\User::where('type', 'company')->get();
echo "Found {$companyUsers->count()} company users:\n";
foreach ($companyUsers as $user) {
    echo "  - {$user->name} ({$user->email}) - ID: {$user->id}\n";
}

// Check DataTable query
echo "\n2. DATATABLE QUERY LOGIC:\n";
echo "-------------------------\n";
$model = new \App\Models\User();
if ($superAdmin->type == 'super admin') {
    $query = $model->where('type', 'company');
    echo "Query: User::where('type', 'company')\n";
} elseif ($superAdmin->isAbleTo('business manage')) {
    $query = $model->whereNotIn('type', ['customer', 'staff'])
        ->where('created_by', $superAdmin->id)
        ->where('business_id', getActiveBusiness());
    echo "Query: User::whereNotIn('type', ['customer', 'staff'])->where(...)\n";
} else {
    $query = $model->where('created_by', $superAdmin->id);
    echo "Query: User::where('created_by', {$superAdmin->id})\n";
}

$results = $query->get();
echo "Results: {$results->count()} users\n";
foreach ($results as $user) {
    echo "  - {$user->name} ({$user->email}) - Type: {$user->type}\n";
}

// Check table structure
echo "\n3. USERS TABLE STRUCTURE:\n";
echo "-------------------------\n";
$sampleUser = \App\Models\User::first();
if ($sampleUser) {
    echo "Sample user attributes:\n";
    echo "  id: {$sampleUser->id}\n";
    echo "  name: {$sampleUser->name}\n";
    echo "  email: {$sampleUser->email}\n";
    echo "  type: {$sampleUser->type}\n";
    echo "  avatar: {$sampleUser->avatar}\n";
    echo "  created_by: " . ($sampleUser->created_by ?? 'NULL') . "\n";
    echo "  business_id: " . ($sampleUser->business_id ?? 'NULL') . "\n";
}

// Check if DataTables package is loaded
echo "\n4. CHECKING DATATABLES:\n";
echo "-------------------------\n";
if (class_exists('\Yajra\DataTables\DataTablesServiceProvider')) {
    echo "✅ Yajra DataTables package is loaded\n";
} else {
    echo "❌ Yajra DataTables package NOT found!\n";
}

if (class_exists('\App\DataTables\UsersDataTable')) {
    echo "✅ UsersDataTable class exists\n";
    
    // Try to instantiate it
    try {
        $dataTable = new \App\DataTables\UsersDataTable();
        echo "✅ UsersDataTable can be instantiated\n";
    } catch (\Exception $e) {
        echo "❌ Error instantiating UsersDataTable: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ UsersDataTable class NOT found!\n";
}

echo "\n==============================\n";
echo "Debug complete!\n";
