<?php

namespace App\Services;

use App\Models\MedicationAdministration;
use App\Models\NursingNote;
use App\Models\PatientVital;

class NursingService
{
    public function addNote(array $data): NursingNote
    {
        return NursingNote::create([
            ...$data,
            'type' => $data['type'] ?? 'general',
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);
    }

    public function recordVitals(array $data): PatientVital
    {
        return PatientVital::create([
            ...$data,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);
    }

    public function recordMedicationAdministration(array $data): MedicationAdministration
    {
        return MedicationAdministration::create([
            ...$data,
            'administered_at' => $data['administered_at'] ?? now(),
        ]);
    }
}
