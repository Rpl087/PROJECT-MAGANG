<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Cek apakah user adalah admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user adalah internship (user biasa).
     */
    public function isInternship(): bool
    {
        return $this->role === 'internship';
    }

    /**
     * Cek apakah user adalah akun maintenance.
     * Akun maintenance memiliki akses penuh ke semua fitur.
     */
    public function isMaintenance(): bool
    {
        return $this->role === 'maintenance';
    }

    /**
     * Cek apakah user memiliki akses level admin atau lebih tinggi.
     * Maintenance account dianggap sebagai superadmin.
     */
    public function hasAdminAccess(): bool
    {
        return $this->isAdmin() || $this->isMaintenance();
    }
}
