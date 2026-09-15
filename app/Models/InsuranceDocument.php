<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceDocument extends Model
{
    protected $fillable = [
        'insurance_claim_id',
        'title',
        'file_path',
        'uploaded_by',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
