<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;

class ConsultationService
{
    public function create(array $data): Consultation
    {
        return Consultation::create([...$data, 'status' => 'in-progress']);
    }

    public function update(Consultation $consultation, array $data): Consultation
    {
        $consultation->update($data);

        return $consultation->fresh();
    }

    public function complete(Consultation $consultation): Consultation
    {
        return DB::transaction(function () use ($consultation) {
            $consultation->update(['status' => 'completed']);

            if ($consultation->appointment_id) {
                $appointment = Appointment::find($consultation->appointment_id);
                $appointment?->update(['status' => 'completed']);
            }

            return $consultation->fresh();
        });
    }
}
