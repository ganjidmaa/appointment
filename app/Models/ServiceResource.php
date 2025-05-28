<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'resource_id',
    ];
}
