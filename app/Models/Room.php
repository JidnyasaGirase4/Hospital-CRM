<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id',
        'room_number',
        'room_type',
        'daily_rate',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
        ];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }
}
