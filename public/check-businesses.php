<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🏢 BUSINESSES WITH CATEGORIES & SERVICES\n";
echo "=========================================\n\n";

$businesses = \App\Models\Business::all();

if ($businesses->isEmpty()) {
    echo "❌ No businesses found!\n";
} else {
    foreach ($businesses as $business) {
        echo "Business: {$business->name}\n";
        echo "  ID: {$business->id}\n";
        echo "  Slug: {$business->slug}\n";
        echo "  Booking URL: https://bookinggo.test/appointments/{$business->slug}\n";
        
        $categories = \App\Models\category::where('business_id', $business->id)->count();
        $services = \App\Models\Service::where('business_id', $business->id)->count();
        
        echo "  Categories: {$categories}\n";
        echo "  Services: {$services}\n";
        
        if ($categories > 0) {
            echo "  ✅ Has categories - READY TO TEST\n";
            
            // Show category details
            $cats = \App\Models\category::where('business_id', $business->id)->get();
            foreach ($cats as $cat) {
                $catServices = \App\Models\Service::where('category_id', $cat->id)->count();
                echo "    - {$cat->name}: {$catServices} services\n";
            }
        } else {
            echo "  ⚠️  No categories - Create in admin panel\n";
        }
        
        echo "\n";
    }
}
