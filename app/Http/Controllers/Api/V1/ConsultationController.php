<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\StoreConsultationRequest;
use App\Http\Requests\Consultations\UpdateConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Services\ConsultationService;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function __construct(private readonly ConsultationService $consultationService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Consultation::class);

        $consultations = Consultation::query()
            ->with(['patient', 'doctor'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($consultations, ConsultationResource::class, 'Consultations retrieved successfully');
    }

    public function store(StoreConsultationRequest $request)
    {
        $consultation = $this->consultationService->create($request->validated());

        return $this->success(new ConsultationResource($consultation->load(['patient', 'doctor'])), 'Consultation created successfully', 201);
    }

    public function show(Consultation $consultation)
    {
        $this->authorize('view', $consultation);

        return $this->success(new ConsultationResource($consultation->load(['patient', 'doctor'])));
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation)
    {
        $consultation = $this->consultationService->update($consultation, $request->validated());

        return $this->success(new ConsultationResource($consultation->load(['patient', 'doctor'])), 'Consultation updated successfully');
    }

    public function destroy(Consultation $consultation)
    {
        $this->authorize('delete', $consultation);

        $consultation->delete();

        return $this->success(null, 'Consultation deleted successfully');
    }

    public function complete(Consultation $consultation)
    {
        $this->authorize('update', $consultation);

        $consultation = $this->consultationService->complete($consultation);

        return $this->success(new ConsultationResource($consultation->load(['patient', 'doctor'])), 'Consultation marked completed');
    }
}
