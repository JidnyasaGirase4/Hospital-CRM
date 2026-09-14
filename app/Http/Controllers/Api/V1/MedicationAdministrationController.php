<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nursing\StoreMedicationAdministrationRequest;
use App\Http\Resources\MedicationAdministrationResource;
use App\Models\MedicationAdministration;
use App\Services\NursingService;
use Illuminate\Http\Request;

class MedicationAdministrationController extends Controller
{
    public function __construct(private readonly NursingService $nursingService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', MedicationAdministration::class);

        $records = MedicationAdministration::query()
            ->with(['medicine', 'administeredBy'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('admission_id'), fn ($q) => $q->where('admission_id', $request->integer('admission_id')))
            ->latest('administered_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($records, MedicationAdministrationResource::class, 'Medication administrations retrieved successfully');
    }

    public function store(StoreMedicationAdministrationRequest $request)
    {
        $record = $this->nursingService->recordMedicationAdministration([
            ...$request->validated(),
            'administered_by' => $request->user()->id,
        ]);

        return $this->success(new MedicationAdministrationResource($record->load(['medicine', 'administeredBy'])), 'Medication administration recorded successfully', 201);
    }
}
