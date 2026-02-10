<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'body_sn',
        'cert_no',
        'date',
        'place',
        'responsible_user_id',
        'method',
        'result',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
