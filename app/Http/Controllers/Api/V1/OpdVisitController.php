<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpdVisits\StoreOpdVisitRequest;
use App\Http\Requests\OpdVisits\UpdateOpdVisitRequest;
use App\Http\Resources\OpdVisitResource;
use App\Models\OpdVisit;
use App\Services\OpdVisitService;
use Illuminate\Http\Request;

class OpdVisitController extends Controller
{
    public function __construct(private readonly OpdVisitService $opdVisitService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', OpdVisit::class);

        $visits = OpdVisit::query()
            ->with(['patient', 'doctor'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('visit_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($visits, OpdVisitResource::class, 'OPD visits retrieved successfully');
    }

    public function store(StoreOpdVisitRequest $request)
    {
        $visit = $this->opdVisitService->create($request->validated());

        return $this->success(new OpdVisitResource($visit->load(['patient', 'doctor'])), 'OPD visit recorded successfully', 201);
    }

    public function show(OpdVisit $opdVisit)
    {
        $this->authorize('view', $opdVisit);

        return $this->success(new OpdVisitResource($opdVisit->load(['patient', 'doctor'])));
    }

    public function update(UpdateOpdVisitRequest $request, OpdVisit $opdVisit)
    {
        $opdVisit->update($request->validated());

        return $this->success(new OpdVisitResource($opdVisit->fresh(['patient', 'doctor'])), 'OPD visit updated successfully');
    }

    public function destroy(OpdVisit $opdVisit)
    {
        $this->authorize('delete', $opdVisit);

        $opdVisit->delete();

        return $this->success(null, 'OPD visit deleted successfully');
    }

    public function close(OpdVisit $opdVisit)
    {
        $this->authorize('update', $opdVisit);

        $visit = $this->opdVisitService->close($opdVisit);

        return $this->success(new OpdVisitResource($visit), 'OPD visit closed successfully');
    }
}
