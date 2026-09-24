<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionService
{
    public function __construct(private readonly BedAllocationService $bedAllocationService) {}

    public function admit(array $data): Admission
    {
        return DB::transaction(function () use ($data) {
            // Lock the patient row so two concurrent admit requests serialise
            // and the active-admission check below can't be raced.
            Patient::query()->lockForUpdate()->findOrFail($data['patient_id']);

            $active = Admission::query()
                ->where('patient_id', $data['patient_id'])
                ->where('status', 'admitted')
                ->exists();

            if ($active) {
                throw ValidationException::withMessages([
                    'patient_id' => ['This patient is already admitted. Discharge the current admission before admitting again.'],
                ]);
            }

            $admission = Admission::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'admission_date' => now(),
                'admission_type' => $data['admission_type'] ?? null,
                'reason' => $data['reason'] ?? null,
                'status' => 'admitted',
            ]);

            if (! empty($data['bed_id'])) {
                $this->bedAllocationService->allocate($admission, $data['bed_id']);
            }

            return $admission->fresh(['currentBedAllocation.bed']);
        });
    }

    public function discharge(Admission $admission, array $data, int $dischargedBy): Admission
    {
        return DB::transaction(function () use ($admission, $data, $dischargedBy) {
            $currentAllocation = $admission->currentBedAllocation()->first();

            if ($currentAllocation) {
                $this->bedAllocationService->release($currentAllocation);
            }

            $admission->update([
                'discharge_date' => now(),
                'discharge_summary' => $data['discharge_summary'] ?? null,
                'status' => 'discharged',
                'discharged_by' => $dischargedBy,
            ]);

            return $admission->fresh();
        });
    }

    public function transferBed(Admission $admission, int $newBedId): Admission
    {
        $this->bedAllocationService->transfer($admission, $newBedId);

        return $admission->fresh(['currentBedAllocation.bed']);
    }
}
