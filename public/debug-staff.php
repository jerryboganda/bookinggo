<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$business = \App\Models\Business::where('slug', 'amad-diagnostic-centre-gujranwala')->first();

echo "🔍 STAFF DROPDOWN DEBUG\n";
echo "=======================\n\n";

// Check staff records
$staff = \App\Models\Staff::where('business_id', $business->id)->get();

echo "Total staff in business: " . $staff->count() . "\n\n";

foreach ($staff as $s) {
    echo "Staff ID: {$s->id}\n";
    echo "Name: {$s->name}\n";
    echo "User ID: {$s->user_id}\n";
    
    if ($s->user_id) {
        $user = \App\Models\User::find($s->user_id);
        if ($user) {
            echo "User Name: {$user->name}\n";
            echo "User Email: {$user->email}\n";
        } else {
            echo "⚠️ User record not found!\n";
        }
    } else {
        echo "⚠️ No user_id assigned!\n";
    }
    echo "\n";
}

// Check a sample service
$service = \App\Models\Service::where('business_id', $business->id)->first();
if ($service) {
    echo "Sample Service: {$service->name} (ID: {$service->id})\n";
    echo "Category ID: {$service->category_id}\n\n";
}

// Check locations
$locations = \App\Models\Location::where('business_id', $business->id)->get();
echo "Total locations: {$locations->count()}\n";
foreach ($locations as $loc) {
    echo "  - {$loc->name} (ID: {$loc->id})\n";
}
