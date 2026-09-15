<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    use Auditable, HasFactory, SoftDeletes;

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

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->latest('scheduled_at');
    }

    public function opdVisits(): HasMany
    {
        return $this->hasMany(OpdVisit::class)->latest('visit_date');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class)->latest();
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class)->latest('diagnosed_at');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class)->latest();
    }

    public function pharmacySales(): HasMany
    {
        return $this->hasMany(PharmacySale::class)->latest();
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class)->latest('ordered_at');
    }

    public function radiologyOrders(): HasMany
    {
        return $this->hasMany(RadiologyOrder::class)->latest('ordered_at');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class)->latest('admission_date');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest();
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
