<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceClaim extends Model
{
    protected $fillable = [
        'claim_number',
        'insurance_policy_id',
        'bill_id',
        'status',
        'requested_amount',
        'approved_amount',
        'rejected_amount',
        'rejection_reason',
        'submitted_at',
        'settled_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'rejected_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InsuranceDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
