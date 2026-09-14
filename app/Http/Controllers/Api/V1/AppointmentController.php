<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointments\CancelAppointmentRequest;
use App\Http\Requests\Appointments\RescheduleAppointmentRequest;
use App\Http\Requests\Appointments\StoreAppointmentRequest;
use App\Http\Requests\Appointments\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = Appointment::query()
            ->with(['patient', 'doctor', 'department'])
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_at', $request->date('date')))
            ->orderBy('scheduled_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($appointments, AppointmentResource::class, 'Appointments retrieved successfully');
    }

    public function store(StoreAppointmentRequest $request)
    {
        $appointment = $this->appointmentService->create($request->validated());

        return $this->success(new AppointmentResource($appointment->load(['patient', 'doctor', 'department'])), 'Appointment booked successfully', 201);
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        return $this->success(new AppointmentResource($appointment->load(['patient', 'doctor', 'department'])));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->validated());

        return $this->success(new AppointmentResource($appointment->fresh(['patient', 'doctor', 'department'])), 'Appointment updated successfully');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return $this->success(null, 'Appointment deleted successfully');
    }

    public function checkIn(Appointment $appointment)
    {
        $this->authorize('checkIn', $appointment);

        $appointment = $this->appointmentService->checkIn($appointment);

        return $this->success(new AppointmentResource($appointment), 'Patient checked in successfully');
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment)
    {
        $appointment = $this->appointmentService->cancel($appointment, $request->validated('reason'));

        return $this->success(new AppointmentResource($appointment), 'Appointment cancelled successfully');
    }

    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment)
    {
        $newAppointment = $this->appointmentService->reschedule($appointment, $request->validated('scheduled_at'));

        return $this->success(new AppointmentResource($newAppointment->load(['patient', 'doctor', 'department'])), 'Appointment rescheduled successfully');
    }
}
