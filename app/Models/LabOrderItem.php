<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrderItem extends Model
{
    protected $fillable = [
        'lab_order_id',
        'lab_test_id',
        'status',
        'sample_collected_at',
    ];

    protected function casts(): array
    {
        return [
            'sample_collected_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }
}
