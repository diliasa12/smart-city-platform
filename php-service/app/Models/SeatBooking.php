<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatBooking extends Model
{
    protected $table = 'seat_bookings';
    protected $fillable = ['user_id', 'room_id', 'seat_number', 'booking_date', 'start_time', 'end_time', 'status'];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room(): BelongsTo {
        return $this->belongsTo(EnvRoom::class, 'room_id');
    }
}