<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvDeviceCommand extends Model
{
    protected $table = 'env_device_commands';
    
    
    public $timestamps = false; 

    protected $fillable = [
        'room_id',
        'command_type',
        'payload',
        'status',
        'executed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'executed_at' => 'datetime'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(EnvRoom::class, 'room_id');
    }
}