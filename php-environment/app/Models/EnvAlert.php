<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvAlert extends Model
{
    protected $table = 'env_alerts';

    protected $fillable = [
        'zone_id', 'alert_type', 'severity', 'value', 
        'threshold', 'message', 'resolved_at'
    ];
}