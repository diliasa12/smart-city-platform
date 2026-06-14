<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Citizen extends Model
{
  use HasFactory;

  protected $table = 'citizen_citizens';

  protected $fillable = [
    'nik',
    'name',
    'email',
    'phone',
    'zone_id',
    'role',
    'password',
    'is_active',
  ];

  protected $hidden = [
    'password',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'zone_id'   => 'integer',
  ];

  public function reports()
  {
    return $this->hasMany(Report::class, 'citizen_id');
  }

  public function notifications()
  {
    return $this->hasMany(Notification::class, 'citizen_id');
  }
}
