<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_category_id',
        'name',
        'generic_name',
        'manufacturer',
        'form',
        'strength',
        'unit',
        'reorder_level',
        'allow_negative_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allow_negative_stock' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MedicineCategory::class, 'medicine_category_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    public function stockOnHand(): int
    {
        return (int) $this->batches()->sum('quantity');
    }

    public function isLowStock(): bool
    {
        return $this->stockOnHand() <= $this->reorder_level;
    }
}
