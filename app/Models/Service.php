<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    public function resources()
    {
        return $this->hasMany(ServiceResource::class);
    }

    public function typeTable()
    {
        return $this->belongsTo(ServiceType::class, 'type');
    }

    public function users()
    {
        return $this->hasMany(ServiceUser::class);
    }

    public function branches()
    {
        return $this->hasMany(ServiceBranch::class);
    }
}
