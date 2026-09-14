<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id',
        'supplier_id',
        'batch_number',
        'quantity',
        'purchase_price',
        'selling_price',
        'mrp',
        'manufactured_date',
        'expiry_date',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'manufactured_date' => 'date',
            'expiry_date' => 'date',
            'received_at' => 'date',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'mrp' => 'decimal:2',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}
