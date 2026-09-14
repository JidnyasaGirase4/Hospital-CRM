<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'super-admin';

    public const HOSPITAL_ADMIN = 'hospital-admin';

    public const DOCTOR = 'doctor';

    public const NURSE = 'nurse';

    public const RECEPTIONIST = 'receptionist';

    public const BILLING_STAFF = 'billing-staff';

    public const PHARMACIST = 'pharmacist';

    public const LAB_TECHNICIAN = 'lab-technician';

    public const RADIOLOGY_STAFF = 'radiology-staff';

    public const OT_STAFF = 'ot-staff';

    public const ACCOUNTANT = 'accountant';

    public const INVENTORY_MANAGER = 'inventory-manager';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
