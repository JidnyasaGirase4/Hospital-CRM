<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Emergency\ReferEmergencyVisitRequest;
use App\Http\Requests\Emergency\RegisterEmergencyVisitRequest;
use App\Http\Requests\Emergency\TriageEmergencyVisitRequest;
use App\Http\Resources\EmergencyVisitResource;
use App\Models\EmergencyVisit;
use App\Services\EmergencyService;
use Illuminate\Http\Request;

class EmergencyVisitController extends Controller
{
    public function __construct(private readonly EmergencyService $emergencyService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', EmergencyVisit::class);

        $visits = EmergencyVisit::query()
            ->with(['patient', 'doctor'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('registered_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($visits, EmergencyVisitResource::class, 'Emergency visits retrieved successfully');
    }

    public function store(RegisterEmergencyVisitRequest $request)
    {
        $visit = $this->emergencyService->register($request->validated());

        return $this->success(new EmergencyVisitResource($visit->load('patient')), 'Emergency visit registered successfully', 201);
    }

    public function show(EmergencyVisit $emergencyVisit)
    {
        $this->authorize('view', $emergencyVisit);

        return $this->success(new EmergencyVisitResource($emergencyVisit->load(['patient', 'doctor'])));
    }

    public function triage(TriageEmergencyVisitRequest $request, EmergencyVisit $emergencyVisit)
    {
        $visit = $this->emergencyService->triage($emergencyVisit, $request->validated('triage_level'));

        return $this->success(new EmergencyVisitResource($visit), 'Patient triaged successfully');
    }

    public function startTreatment(Request $request, EmergencyVisit $emergencyVisit)
    {
        $this->authorize('update', $emergencyVisit);

        $data = $request->validate(['notes' => ['nullable', 'string']]);

        $visit = $this->emergencyService->startTreatment($emergencyVisit, $request->user()->id, $data['notes'] ?? null);

        return $this->success(new EmergencyVisitResource($visit), 'Treatment started');
    }

    public function admit(Request $request, EmergencyVisit $emergencyVisit)
    {
        $this->authorize('update', $emergencyVisit);

        $data = $request->validate([
            'doctor_id' => ['nullable', 'exists:users,id'],
            'reason' => ['nullable', 'string'],
            'bed_id' => ['nullable', 'exists:beds,id'],
        ]);

        $visit = $this->emergencyService->admit($emergencyVisit, $data);

        return $this->success(new EmergencyVisitResource($visit), 'Patient admitted from emergency');
    }

    public function discharge(EmergencyVisit $emergencyVisit)
    {
        $this->authorize('update', $emergencyVisit);

        $visit = $this->emergencyService->discharge($emergencyVisit);

        return $this->success(new EmergencyVisitResource($visit), 'Patient discharged from emergency');
    }

    public function refer(ReferEmergencyVisitRequest $request, EmergencyVisit $emergencyVisit)
    {
        $visit = $this->emergencyService->refer($emergencyVisit, $request->validated('referred_to'));

        return $this->success(new EmergencyVisitResource($visit), 'Patient referred successfully');
    }
}
