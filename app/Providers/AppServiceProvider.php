<?php

namespace App\Providers;

use App\Models\Admission;
use App\Models\Bill;
use App\Models\Consultation;
use App\Models\InsuranceClaim;
use App\Models\LabOrder;
use App\Models\OtSchedule;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\RadiologyOrder;
use App\Models\Refund;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Note: there is deliberately no Gate::before() bypass for Super Admin.
     * The Super Admin role is seeded with every permission (RolePermissionSeeder),
     * which satisfies ordinary permission checks. A blanket Gate::before bypass
     * would also skip business-rule checks inside policies (e.g. "system roles
     * can't be deleted") for Super Admin, which is not what we want.
     */
    public function boot(): void
    {
        // Short, stable aliases for polymorphic columns (audit_logs.auditable_type,
        // documents.documentable_type) instead of raw FQCNs, so stored rows survive
        // a future namespace/class rename. Deliberately NOT enforceMorphMap(): the
        // audit log passes many kinds of models (User, MedicineBatch, InventoryItem,
        // etc.) that aren't practical to enumerate exhaustively here, and
        // enforceMorphMap() throws ClassMorphViolationException for anything left
        // out. Plain morphMap() aliases the models listed and safely falls back to
        // the raw class name for everything else.
        Relation::morphMap([
            'patient' => Patient::class,
            'admission' => Admission::class,
            'consultation' => Consultation::class,
            'prescription' => Prescription::class,
            'bill' => Bill::class,
            'payment' => Payment::class,
            'refund' => Refund::class,
            'lab_order' => LabOrder::class,
            'radiology_order' => RadiologyOrder::class,
            'ot_schedule' => OtSchedule::class,
            'insurance_claim' => InsuranceClaim::class,
            'role' => Role::class,
        ]);
    }
}
