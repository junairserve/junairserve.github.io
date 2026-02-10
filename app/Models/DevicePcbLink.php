<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevicePcbLink extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'body_sn',
        'pcb_sn',
        'linked_at',
        'unlinked_at',
        'linked_by_user_id',
        'unlinked_by_user_id',
        'unlink_reason',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
        'unlinked_at' => 'datetime',
    ];
}
