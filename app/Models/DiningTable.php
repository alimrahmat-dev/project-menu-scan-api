<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'capacity',
        'qr_token',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DiningTable $table) {
            $table->qr_token ??= (string) Str::uuid();
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getMenuUrlAttribute(): string
    {
        return config('menuscan.frontend_url').'/t/'.$this->qr_token;
    }
}
