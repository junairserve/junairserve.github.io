<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialPool extends Model
{
    use HasFactory;

    protected $table = 'serial_pool';

    protected $fillable = [
        'type',
        'sn',
        'status',
        'lot_name',
    ];
}
