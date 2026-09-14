<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nursing\StoreVitalsRequest;
use App\Http\Resources\PatientVitalResource;
use App\Models\PatientVital;
use App\Services\NursingService;
use Illuminate\Http\Request;

class PatientVitalController extends Controller
{
    public function __construct(private readonly NursingService $nursingService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PatientVital::class);

        $vitals = PatientVital::query()
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('admission_id'), fn ($q) => $q->where('admission_id', $request->integer('admission_id')))
            ->latest('recorded_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($vitals, PatientVitalResource::class, 'Vitals retrieved successfully');
    }

    public function store(StoreVitalsRequest $request)
    {
        $vital = $this->nursingService->recordVitals([
            ...$request->validated(),
            'recorded_by' => $request->user()->id,
        ]);

        return $this->success(new PatientVitalResource($vital), 'Vitals recorded successfully', 201);
    }
}
