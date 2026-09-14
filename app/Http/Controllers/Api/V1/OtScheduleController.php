<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ot\StoreOtScheduleRequest;
use App\Http\Resources\OtScheduleResource;
use App\Models\OtSchedule;
use App\Services\OtService;
use Illuminate\Http\Request;

class OtScheduleController extends Controller
{
    public function __construct(private readonly OtService $otService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', OtSchedule::class);

        $schedules = OtSchedule::query()
            ->with(['patient', 'surgeon'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('surgeon_id'), fn ($q) => $q->where('surgeon_id', $request->integer('surgeon_id')))
            ->orderBy('scheduled_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($schedules, OtScheduleResource::class, 'OT schedules retrieved successfully');
    }

    public function store(StoreOtScheduleRequest $request)
    {
        $schedule = $this->otService->schedule($request->validated());

        return $this->success(new OtScheduleResource($schedule->load(['patient', 'surgeon'])), 'Surgery scheduled successfully', 201);
    }

    public function show(OtSchedule $otSchedule)
    {
        $this->authorize('view', $otSchedule);

        return $this->success(new OtScheduleResource($otSchedule->load(['patient', 'surgeon'])));
    }

    public function start(OtSchedule $otSchedule)
    {
        $this->authorize('update', $otSchedule);

        $schedule = $this->otService->start($otSchedule);

        return $this->success(new OtScheduleResource($schedule), 'Surgery started');
    }

    public function complete(Request $request, OtSchedule $otSchedule)
    {
        $this->authorize('update', $otSchedule);

        $data = $request->validate(['notes' => ['nullable', 'string']]);

        $schedule = $this->otService->complete($otSchedule, $data['notes'] ?? null);

        return $this->success(new OtScheduleResource($schedule), 'Surgery completed');
    }

    public function cancel(OtSchedule $otSchedule)
    {
        $this->authorize('update', $otSchedule);

        $schedule = $this->otService->cancel($otSchedule);

        return $this->success(new OtScheduleResource($schedule), 'Surgery cancelled');
    }
}
