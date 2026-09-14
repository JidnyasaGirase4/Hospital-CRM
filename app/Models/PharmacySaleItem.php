<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacySaleItem extends Model
{
    protected $fillable = [
        'pharmacy_sale_id',
        'medicine_batch_id',
        'prescription_item_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacySale::class, 'pharmacy_sale_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PharmacyReturn::class);
    }
}
