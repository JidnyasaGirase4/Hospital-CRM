<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyReturn extends Model
{
    protected $fillable = [
        'pharmacy_sale_item_id',
        'quantity',
        'reason',
        'returned_by',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
        ];
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(PharmacySaleItem::class, 'pharmacy_sale_item_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
