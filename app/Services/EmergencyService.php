<?php

namespace App\Services;

use App\Models\EmergencyVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registration -> Triage -> Vitals -> Doctor -> Investigation -> Treatment
 * -> Admission / Discharge / Referral. Vitals and investigations reuse the
 * Nursing/Lab/Radiology modules directly against the same patient_id, so
 * this service only owns the visit's own status progression plus the
 * three possible endings.
 */
class EmergencyService
{
    public function __construct(private readonly AdmissionService $admissionService) {}

    public function register(array $data): EmergencyVisit
    {
        return EmergencyVisit::create([
            'patient_id' => $data['patient_id'],
            'chief_complaint' => $data['chief_complaint'] ?? null,
            'status' => 'registered',
            'registered_at' => now(),
        ]);
    }

    public function triage(EmergencyVisit $visit, string $triageLevel): EmergencyVisit
    {
        $visit->update(['triage_level' => $triageLevel, 'status' => 'triaged']);

        return $visit->fresh();
    }

    public function startTreatment(EmergencyVisit $visit, int $doctorId, ?string $notes = null): EmergencyVisit
    {
        $visit->update([
            'doctor_id' => $doctorId,
            'status' => 'in-treatment',
            'treatment_notes' => $notes ?? $visit->treatment_notes,
        ]);

        return $visit->fresh();
    }

    public function admit(EmergencyVisit $visit, array $admissionData): EmergencyVisit
    {
        return DB::transaction(function () use ($visit, $admissionData) {
            // Re-read under a lock so a double-click / concurrent request can't
            // admit the same visit twice.
            $visit = EmergencyVisit::query()->lockForUpdate()->findOrFail($visit->id);
            $this->assertActionable($visit);

            $doctorId = $admissionData['doctor_id'] ?? $visit->doctor_id;

            if (! $doctorId) {
                throw ValidationException::withMessages([
                    'doctor_id' => ['A doctor is required to admit this patient.'],
                ]);
            }

            $admission = $this->admissionService->admit([
                'patient_id' => $visit->patient_id,
                'doctor_id' => $doctorId,
                'admission_type' => 'emergency',
                'reason' => $admissionData['reason'] ?? $visit->chief_complaint,
                'bed_id' => $admissionData['bed_id'] ?? null,
            ]);

            $visit->update(['status' => 'admitted', 'admission_id' => $admission->id]);

            return $visit->fresh('admission');
        });
    }

    public function discharge(EmergencyVisit $visit): EmergencyVisit
    {
        $this->assertActionable($visit);

        $visit->update(['status' => 'discharged']);

        return $visit->fresh();
    }

    public function refer(EmergencyVisit $visit, string $referredTo): EmergencyVisit
    {
        $this->assertActionable($visit);

        $visit->update(['status' => 'referred', 'referred_to' => $referredTo]);

        return $visit->fresh();
    }

    private function assertActionable(EmergencyVisit $visit): void
    {
        if (in_array($visit->status, ['admitted', 'discharged', 'referred'], true)) {
            throw ValidationException::withMessages([
                'status' => ["This visit has already been closed out (status: {$visit->status})."],
            ]);
        }
    }
}
