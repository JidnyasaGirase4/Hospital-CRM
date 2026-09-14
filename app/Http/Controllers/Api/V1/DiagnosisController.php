<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Diagnoses\StoreDiagnosisRequest;
use App\Http\Requests\Diagnoses\UpdateDiagnosisRequest;
use App\Http\Resources\DiagnosisResource;
use App\Models\Diagnosis;
use App\Services\DiagnosisService;
use Illuminate\Http\Request;

class DiagnosisController extends Controller
{
    public function __construct(private readonly DiagnosisService $diagnosisService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Diagnosis::class);

        $diagnoses = Diagnosis::query()
            ->with('doctor')
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->latest('diagnosed_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($diagnoses, DiagnosisResource::class, 'Diagnoses retrieved successfully');
    }

    public function store(StoreDiagnosisRequest $request)
    {
        $diagnosis = $this->diagnosisService->create($request->validated());

        return $this->success(new DiagnosisResource($diagnosis->load('doctor')), 'Diagnosis recorded successfully', 201);
    }

    public function show(Diagnosis $diagnosis)
    {
        $this->authorize('view', $diagnosis);

        return $this->success(new DiagnosisResource($diagnosis->load('doctor')));
    }

    public function update(UpdateDiagnosisRequest $request, Diagnosis $diagnosis)
    {
        $diagnosis = $this->diagnosisService->update($diagnosis, $request->validated());

        return $this->success(new DiagnosisResource($diagnosis->load('doctor')), 'Diagnosis updated successfully');
    }

    public function destroy(Diagnosis $diagnosis)
    {
        $this->authorize('delete', $diagnosis);

        $diagnosis->delete();

        return $this->success(null, 'Diagnosis deleted successfully');
    }
}
