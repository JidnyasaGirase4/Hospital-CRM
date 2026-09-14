<?php

namespace App\Services;

use App\Models\Prescription;
use Illuminate\Support\Facades\DB;

class PrescriptionService
{
    public function create(array $data): Prescription
    {
        return DB::transaction(function () use ($data) {
            $prescription = Prescription::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'consultation_id' => $data['consultation_id'] ?? null,
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $prescription->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'route' => $item['route'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'timing' => $item['timing'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'dispensed_quantity' => 0,
                ]);
            }

            return $prescription->load('items.medicine');
        });
    }

    public function cancel(Prescription $prescription): Prescription
    {
        $prescription->update(['status' => 'cancelled']);

        return $prescription->fresh('items');
    }
}
