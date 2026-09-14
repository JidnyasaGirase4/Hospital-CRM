<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patients\StorePatientRequest;
use App\Http\Requests\Patients\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\Patient360Service;
use App\Services\PatientService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService,
        private readonly Patient360Service $patient360Service
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        $patients = Patient::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = "%{$request->string('search')}%";
                $q->where(function ($q) use ($term) {
                    $q->where('mrn', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('mobile', 'like', $term);
                });
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($patients, PatientResource::class, 'Patients retrieved successfully');
    }

    public function store(StorePatientRequest $request)
    {
        $patient = $this->patientService->create($request->validated());

        return $this->success(new PatientResource($patient), 'Patient created successfully', 201);
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);

        return $this->success(new PatientResource($patient->load('registeredBy')));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient = $this->patientService->update($patient, $request->validated());

        return $this->success(new PatientResource($patient), 'Patient updated successfully');
    }

    public function destroy(Patient $patient)
    {
        $this->authorize('delete', $patient);

        $this->patientService->delete($patient);

        return $this->success(null, 'Patient deleted successfully');
    }

    public function show360(Patient $patient)
    {
        $this->authorize('view360', $patient);

        $sections = $this->patient360Service->build($patient);
        $sections['profile'] = new PatientResource($sections['profile']);

        return $this->success($sections, 'Patient 360 retrieved successfully');
    }
}
