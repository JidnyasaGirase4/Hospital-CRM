<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nursing\StoreNursingNoteRequest;
use App\Http\Resources\NursingNoteResource;
use App\Models\NursingNote;
use App\Services\NursingService;
use Illuminate\Http\Request;

class NursingNoteController extends Controller
{
    public function __construct(private readonly NursingService $nursingService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingNote::class);

        $notes = NursingNote::query()
            ->with('nurse')
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('admission_id'), fn ($q) => $q->where('admission_id', $request->integer('admission_id')))
            ->latest('recorded_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($notes, NursingNoteResource::class, 'Nursing notes retrieved successfully');
    }

    public function store(StoreNursingNoteRequest $request)
    {
        $note = $this->nursingService->addNote([
            ...$request->validated(),
            'nurse_id' => $request->user()->id,
        ]);

        return $this->success(new NursingNoteResource($note->load('nurse')), 'Nursing note recorded successfully', 201);
    }
}
