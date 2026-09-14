<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyReport extends Model
{
    protected $fillable = [
        'radiology_order_id',
        'findings',
        'impression',
        'reported_by',
        'reported_at',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
