<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\OpdVisit;
use Illuminate\Support\Facades\DB;

class OpdVisitService
{
    public function __construct(private readonly AppointmentService $appointmentService) {}

    public function create(array $data): OpdVisit
    {
        return DB::transaction(function () use ($data) {
            $visit = OpdVisit::create([...$data, 'status' => 'open']);

            if (! empty($data['appointment_id'])) {
                $appointment = Appointment::find($data['appointment_id']);

                if ($appointment && $appointment->status === 'scheduled') {
                    $this->appointmentService->checkIn($appointment);
                }
            }

            return $visit;
        });
    }

    public function close(OpdVisit $visit): OpdVisit
    {
        $visit->update(['status' => 'closed']);

        return $visit->fresh();
    }
}
