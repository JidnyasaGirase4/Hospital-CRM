<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'surgeon_id',
        'admission_id',
        'procedure_name',
        'ot_room',
        'scheduled_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surgeon_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }
}
