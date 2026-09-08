<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * ============================================================
 * MODEL: User
 * ============================================================
 *
 * Merepresentasikan pengguna aplikasi kearsipan.
 *
 * KOLOM DATABASE (tabel: users):
 *   - name         : nama lengkap user
 *   - email        : alamat email (digunakan sebagai username login)
 *   - password     : hash bcrypt (otomatis di-hash oleh Laravel)
 *   - role         : 'admin' atau 'internship'
 *   - last_seen_at : waktu terakhir user aktif (diperbarui oleh UpdateUserLastSeen middleware)
 *   - remember_token, email_verified_at : field standar Laravel
 *
 * ROLE YANG TERSEDIA:
 *   - 'admin'       : akses penuh (CRUD, dashboard, manajemen user)
 *   - 'internship'  : akses terbatas (hanya lihat & tambah arsip dari jaringan internal)
 *
 * UNTUK MENAMBAH ROLE BARU:
 *   1. Tambahkan kolom/nilai di migration atau seeder
 *   2. Tambahkan method cek baru di bawah (misal: isManager())
 *   3. Update middleware AdminOnly atau buat middleware baru
 *   4. Update method hasAdminAccess() jika role baru perlu akses admin
 *
 * CATATAN KEAMANAN:
 *   - Field 'password' dan 'remember_token' ada di $hidden (tidak tampil di JSON)
 *   - Password otomatis di-hash saat diisi (lihat casts())
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_seen_at'      => 'datetime',
        ];
    }

    /** Cek apakah user memiliki role 'admin' */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Cek apakah user memiliki role 'internship' (magang) */
    public function isInternship(): bool
    {
        return $this->role === 'internship';
    }

    /**
     * Cek apakah user memiliki hak akses admin.
     * Saat ini hanya role 'admin' yang memiliki akses ini.
     * Tambahkan kondisi di sini jika ada role lain yang perlu akses admin.
     */
    public function hasAdminAccess(): bool
    {
        return $this->isAdmin();
    }
}
