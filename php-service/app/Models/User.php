<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User
 *
 * PERBAIKAN:
 *  - Tambah `phone` dan `role` ke $fillable sesuai schema.sql
 *    (kolom yang ada di tabel: id, name, email, password, phone, role)
 *  - Tambah `role` ke $casts agar nilainya selalu string
 *  - Tambah $hidden eksplisit (gantikan attribute #[Hidden] yang tidak include phone)
 *
 * Kolom tabel `users` di schema.sql:
 *   id, name, email, password, phone, role, created_at, updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh di-mass assign.
     * Sesuai schema.sql tabel `users`.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi (JSON response).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Type casting untuk kolom-kolom model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => 'string',   // 'admin' | 'user'
        ];
    }

    /**
     * Cek apakah user adalah admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}