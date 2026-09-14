<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function create(array $data): Appointment
    {
        $this->guardAgainstDoubleBooking($data['doctor_id'], $data['scheduled_at']);

        $data['created_by'] = $data['created_by'] ?? request()->user()?->id;
        $data['status'] = 'scheduled';
        $data['duration_minutes'] = $data['duration_minutes'] ?? 15;
        $data['type'] = $data['type'] ?? 'new';

        return Appointment::create($data);
    }

    public function checkIn(Appointment $appointment): Appointment
    {
        if ($appointment->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'status' => ["Cannot check in an appointment with status '{$appointment->status}'."],
            ]);
        }

        $appointment->update(['status' => 'checked-in', 'checked_in_at' => now()]);

        return $appointment->fresh();
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        if (in_array($appointment->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot cancel an appointment with status '{$appointment->status}'."],
            ]);
        }

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $appointment->fresh();
    }

    public function reschedule(Appointment $appointment, string $newScheduledAt): Appointment
    {
        if (in_array($appointment->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot reschedule an appointment with status '{$appointment->status}'."],
            ]);
        }

        $this->guardAgainstDoubleBooking($appointment->doctor_id, $newScheduledAt);

        return DB::transaction(function () use ($appointment, $newScheduledAt) {
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Rescheduled',
            ]);

            return Appointment::create([
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'department_id' => $appointment->department_id,
                'scheduled_at' => $newScheduledAt,
                'duration_minutes' => $appointment->duration_minutes,
                'type' => $appointment->type,
                'status' => 'scheduled',
                'rescheduled_from_id' => $appointment->id,
                'created_by' => request()->user()?->id,
            ]);
        });
    }

    public function complete(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => 'completed']);

        return $appointment->fresh();
    }

    private function guardAgainstDoubleBooking(int $doctorId, string $scheduledAt): void
    {
        $exists = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('scheduled_at', $scheduledAt)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['This doctor already has an appointment at the selected time.'],
            ]);
        }
    }
}
