<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $table = 'citizen_reports';

    protected $fillable = [
        'citizen_id',
        'category',
        'description',
        'zone_id',
        'status',
        'attachment_url',
        'resolved_at',
    ];

    protected $casts = [
        'citizen_id'  => 'integer',
        'zone_id'     => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }
}