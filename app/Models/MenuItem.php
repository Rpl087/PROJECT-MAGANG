<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'label',
        'route_name',
        'icon',
        'visible_to',
        'sort_order',
        'is_active',
        'confirm_title',
        'confirm_desc',
    ];

    protected $casts = [
        'visible_to' => 'array',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'visible_to' => '["admin","internship","maintenance"]',
    ];

    /**
     * Scope: hanya item aktif, diurutkan berdasarkan sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope: item yang visible untuk role tertentu.
     */
    public function scopeForRole($query, string $role)
    {
        return $query->whereJsonContains('visible_to', $role);
    }

    /**
     * Cek apakah menu ini visible untuk role tertentu.
     */
    public function isVisibleTo(string $role): bool
    {
        return in_array($role, $this->visible_to ?? []);
    }

    /**
     * Cek apakah route nama masih valid di aplikasi.
     */
    public function hasValidRoute(): bool
    {
        if (!$this->route_name) return false;
        try {
            route($this->route_name);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
