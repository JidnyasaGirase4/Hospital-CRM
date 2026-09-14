<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescriptions\StorePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(private readonly PrescriptionService $prescriptionService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Prescription::class);

        $prescriptions = Prescription::query()
            ->with(['patient', 'doctor'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($prescriptions, PrescriptionResource::class, 'Prescriptions retrieved successfully');
    }

    public function store(StorePrescriptionRequest $request)
    {
        $prescription = $this->prescriptionService->create($request->validated());

        return $this->success(new PrescriptionResource($prescription->load(['patient', 'doctor'])), 'Prescription created successfully', 201);
    }

    public function show(Prescription $prescription)
    {
        $this->authorize('view', $prescription);

        return $this->success(new PrescriptionResource($prescription->load(['patient', 'doctor', 'items.medicine'])));
    }

    public function destroy(Prescription $prescription)
    {
        $this->authorize('delete', $prescription);

        $prescription = $this->prescriptionService->cancel($prescription);

        return $this->success(new PrescriptionResource($prescription), 'Prescription cancelled successfully');
    }
}
