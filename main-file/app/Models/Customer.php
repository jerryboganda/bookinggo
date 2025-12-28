<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name','user_id','gender','dob','description','business_id','created_by'
    ];

    public function customer()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    
}
