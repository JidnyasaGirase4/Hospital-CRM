<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StorePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $purchaseOrderService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->latest('order_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($orders, PurchaseOrderResource::class, 'Purchase orders retrieved successfully');
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $po = $this->purchaseOrderService->create($request->validated());

        return $this->success(new PurchaseOrderResource($po), 'Purchase order created successfully', 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        return $this->success(new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.item'])));
    }

    public function markOrdered(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $po = $this->purchaseOrderService->markOrdered($purchaseOrder);

        return $this->success(new PurchaseOrderResource($po), 'Purchase order marked as ordered');
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $po = $this->purchaseOrderService->receive($purchaseOrder);

        return $this->success(new PurchaseOrderResource($po->load('items.item')), 'Purchase order received and stock updated');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $po = $this->purchaseOrderService->cancel($purchaseOrder);

        return $this->success(new PurchaseOrderResource($po), 'Purchase order cancelled');
    }
}
