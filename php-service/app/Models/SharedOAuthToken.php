<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedOAuthToken extends Model
{
    protected $table = 'shared_oauth_tokens';
    
    // Matikan timestamps bawaan Laravel karena tabelmu hanya punya created_at
    public $timestamps = false; 

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Relasi ke tabel users
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}