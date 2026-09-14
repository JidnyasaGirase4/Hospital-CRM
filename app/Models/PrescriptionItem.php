<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'dosage',
        'frequency',
        'route',
        'duration',
        'timing',
        'instructions',
        'quantity',
        'dispensed_quantity',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function remainingQuantity(): int
    {
        return max(0, $this->quantity - $this->dispensed_quantity);
    }
}
