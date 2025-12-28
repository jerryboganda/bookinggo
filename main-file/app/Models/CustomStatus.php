<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'title','business_id','created_by'
    ];

    public static function icon(){

        $icon = [
            'ti ti-loader',
            'ti ti-shield-check',
            'ti ti-check',
            'ti ti-circle-x	',
            'ti ti-star',
            'ti ti-home',
            "ti ti-alert-circle",
            "ti ti-calendar-event",
            "ti ti-check",
            "ti ti-truck-delivery",
            "ti ti-ban",
            "ti ti-thumb-up",
        ];

        return $icon;
    }
}
