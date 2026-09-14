<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyPurchaseItem extends Model
{
    protected $fillable = [
        'pharmacy_purchase_id',
        'medicine_id',
        'medicine_batch_id',
        'batch_number',
        'quantity',
        'unit_cost',
        'total_cost',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PharmacyPurchase::class, 'pharmacy_purchase_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }
}
