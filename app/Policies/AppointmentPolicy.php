<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.view')
            || ($user->id === $appointment->doctor_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('appointments.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.update');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.delete');
    }

    public function checkIn(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.check-in');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.cancel');
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.reschedule');
    }
}
