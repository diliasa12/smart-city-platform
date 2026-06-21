<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $table = 'env_rooms';

    protected $fillable = [
        'zone_id',
        'room_name',
        'capacity',
        'device_token',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}