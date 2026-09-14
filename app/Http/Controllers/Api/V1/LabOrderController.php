<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Laboratory\RecordLabResultsRequest;
use App\Http\Requests\Laboratory\StoreLabOrderRequest;
use App\Http\Resources\LabOrderItemResource;
use App\Http\Resources\LabOrderResource;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Services\LabService;
use Illuminate\Http\Request;

class LabOrderController extends Controller
{
    public function __construct(private readonly LabService $labService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabOrder::class);

        $orders = LabOrder::query()
            ->with(['patient', 'doctor'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('ordered_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($orders, LabOrderResource::class, 'Lab orders retrieved successfully');
    }

    public function store(StoreLabOrderRequest $request)
    {
        $order = $this->labService->createOrder($request->validated());

        return $this->success(new LabOrderResource($order->load(['patient', 'doctor'])), 'Lab order created successfully', 201);
    }

    public function show(LabOrder $labOrder)
    {
        $this->authorize('view', $labOrder);

        return $this->success(new LabOrderResource($labOrder->load(['patient', 'doctor', 'items.test', 'items.results'])));
    }

    public function collectSample(LabOrderItem $item)
    {
        $this->authorize('collectSample', LabOrder::class);

        $item = $this->labService->collectSample($item);

        return $this->success(new LabOrderItemResource($item->load('test')), 'Sample collected successfully');
    }

    public function startProcessing(LabOrderItem $item)
    {
        $this->authorize('process', LabOrder::class);

        $item = $this->labService->startProcessing($item);

        return $this->success(new LabOrderItemResource($item->load('test')), 'Item moved to processing');
    }

    public function recordResults(RecordLabResultsRequest $request, LabOrderItem $item)
    {
        $item = $this->labService->recordResults($item, $request->validated('results'));

        return $this->success(new LabOrderItemResource($item->load(['test', 'results'])), 'Results recorded successfully');
    }

    public function approveResults(LabOrderItem $item)
    {
        $this->authorize('approveResults', LabOrder::class);

        $item = $this->labService->approveResults($item, request()->user()->id);

        return $this->success(new LabOrderItemResource($item->load(['test', 'results'])), 'Results approved successfully');
    }
}
