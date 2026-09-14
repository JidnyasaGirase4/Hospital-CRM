<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Beds\StoreBedRequest;
use App\Http\Resources\BedResource;
use App\Models\Bed;
use App\Services\BedAllocationService;
use Illuminate\Http\Request;

class BedController extends Controller
{
    public function __construct(private readonly BedAllocationService $bedAllocationService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Bed::class);

        $beds = Bed::query()
            ->with('room.ward')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('room_id'), fn ($q) => $q->where('room_id', $request->integer('room_id')))
            ->get();

        return $this->success(BedResource::collection($beds));
    }

    public function store(StoreBedRequest $request)
    {
        $bed = Bed::create($request->validated());

        return $this->success(new BedResource($bed), 'Bed created successfully', 201);
    }

    public function markCleaned(Bed $bed)
    {
        $this->authorize('update', $bed);

        $bed = $this->bedAllocationService->markCleaned($bed);

        return $this->success(new BedResource($bed), 'Bed marked available after cleaning');
    }
}
