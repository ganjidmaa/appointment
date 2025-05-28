<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;
 
    public function users() 
    {
        return $this->hasMany(User::class)->where('status', 'active');
    }

    public function beauticians() 
    {
        return $this->hasMany(User::class)->where('status', 'active')->where('role_id', 3);
    }
}
