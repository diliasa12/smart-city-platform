<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedOAuthToken extends Model
{
    protected $table = 'shared_oauth_tokens';


    public $timestamps = false;

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

   
    public function scopeActive($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

   
    public function scopeForAccessToken($query, string $token)
    {
        return $query->whereRaw('LEFT(access_token, 255) = ?', [mb_substr($token, 0, 255)]);
    }

  
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}