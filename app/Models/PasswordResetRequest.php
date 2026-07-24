<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetRequest extends Model
{
    protected $fillable = [
        'username',
        'ip_address',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Scope: permintaan yang belum diselesaikan.
     */
    public function scopePending($query)
    {
        return $query->where('is_resolved', false)->latest();
    }

    /**
     * Tandai permintaan sebagai sudah diselesaikan.
     */
    public function resolve(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Relasi: permintaan ini milik user berdasarkan username.
     * Digunakan untuk eager loading nama user di tampilan admin.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'name');
    }
}
