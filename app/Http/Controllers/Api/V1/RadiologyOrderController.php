<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Radiology\StoreRadiologyOrderRequest;
use App\Http\Requests\Radiology\SubmitRadiologyReportRequest;
use App\Http\Resources\RadiologyOrderResource;
use App\Models\RadiologyOrder;
use App\Services\RadiologyService;
use Illuminate\Http\Request;

class RadiologyOrderController extends Controller
{
    public function __construct(private readonly RadiologyService $radiologyService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyOrder::class);

        $orders = RadiologyOrder::query()
            ->with(['patient', 'doctor', 'test'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('ordered_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($orders, RadiologyOrderResource::class, 'Radiology orders retrieved successfully');
    }

    public function store(StoreRadiologyOrderRequest $request)
    {
        $order = $this->radiologyService->createOrder($request->validated());

        return $this->success(new RadiologyOrderResource($order->load(['patient', 'doctor', 'test'])), 'Radiology order created successfully', 201);
    }

    public function show(RadiologyOrder $radiologyOrder)
    {
        $this->authorize('view', $radiologyOrder);

        return $this->success(new RadiologyOrderResource($radiologyOrder->load(['patient', 'doctor', 'test', 'report'])));
    }

    public function schedule(RadiologyOrder $radiologyOrder)
    {
        $this->authorize('update', $radiologyOrder);

        $order = $this->radiologyService->schedule($radiologyOrder);

        return $this->success(new RadiologyOrderResource($order), 'Radiology order scheduled successfully');
    }

    public function submitReport(SubmitRadiologyReportRequest $request, RadiologyOrder $radiologyOrder)
    {
        $report = $this->radiologyService->submitReport($radiologyOrder, $request->validated(), $request->user()->id);

        return $this->success($report, 'Radiology report submitted successfully', 201);
    }

    public function approveReport(RadiologyOrder $radiologyOrder)
    {
        $this->authorize('update', $radiologyOrder);

        $report = $this->radiologyService->approveReport($radiologyOrder->report, request()->user()->id);

        return $this->success($report, 'Radiology report approved successfully');
    }
}
