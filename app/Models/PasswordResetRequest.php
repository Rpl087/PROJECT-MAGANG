<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
