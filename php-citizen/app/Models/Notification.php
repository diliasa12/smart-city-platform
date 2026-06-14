<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'citizen_notifications';

    public $timestamps = false;

    protected $fillable = [
        'citizen_id',
        'title',
        'body',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'citizen_id' => 'integer',
        'is_read'    => 'boolean',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }
}