<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'insurance_company_id',
        'policy_number',
        'valid_from',
        'valid_till',
        'coverage_amount',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_till' => 'date',
            'coverage_amount' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }
}
