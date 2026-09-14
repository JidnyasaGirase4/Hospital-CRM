<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admissions\AllocateBedRequest;
use App\Http\Requests\Admissions\DischargeAdmissionRequest;
use App\Http\Requests\Admissions\StoreAdmissionRequest;
use App\Http\Resources\AdmissionResource;
use App\Models\Admission;
use App\Services\AdmissionService;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function __construct(private readonly AdmissionService $admissionService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Admission::class);

        $admissions = Admission::query()
            ->with(['patient', 'doctor', 'currentBedAllocation.bed'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('admission_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($admissions, AdmissionResource::class, 'Admissions retrieved successfully');
    }

    public function store(StoreAdmissionRequest $request)
    {
        $admission = $this->admissionService->admit($request->validated());

        return $this->success(new AdmissionResource($admission->load(['patient', 'doctor'])), 'Patient admitted successfully', 201);
    }

    public function show(Admission $admission)
    {
        $this->authorize('view', $admission);

        return $this->success(new AdmissionResource($admission->load(['patient', 'doctor', 'currentBedAllocation.bed'])));
    }

    public function discharge(DischargeAdmissionRequest $request, Admission $admission)
    {
        $admission = $this->admissionService->discharge($admission, $request->validated(), $request->user()->id);

        return $this->success(new AdmissionResource($admission), 'Patient discharged successfully');
    }

    public function transferBed(AllocateBedRequest $request, Admission $admission)
    {
        $admission = $this->admissionService->transferBed($admission, $request->validated('bed_id'));

        return $this->success(new AdmissionResource($admission->load('currentBedAllocation.bed')), 'Bed transferred successfully');
    }
}
