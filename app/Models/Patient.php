<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The hub of Patient 360°. Relations to other modules (appointments, OPD
 * visits, consultations, prescriptions, lab/radiology orders, admissions,
 * bills, payments, documents, insurance...) are added here as each phase's
 * module lands. Patient360Service picks up any relation defined on this
 * model automatically — see its class doc.
 */
class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mrn',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'mobile',
        'email',
        'address_line',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'blood_group',
        'allergies',
        'insurance_provider',
        'insurance_policy_number',
        'registered_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
