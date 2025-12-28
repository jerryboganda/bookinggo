<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$businesses = DB::table('businesses')->select('id', 'name', 'slug', 'layouts')->limit(5)->get();

foreach($businesses as $b) {
    echo 'ID: ' . $b->id . ', Name: ' . $b->name . ', Slug: ' . $b->slug . ', Layout: ' . $b->layouts . "\n";
}
