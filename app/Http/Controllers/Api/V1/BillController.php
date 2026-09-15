<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\AddBillItemRequest;
use App\Http\Requests\Billing\AddDiscountRequest;
use App\Http\Requests\Billing\StoreBillRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function __construct(private readonly BillingService $billingService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Bill::class);

        $bills = Bill::query()
            ->with('patient')
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($bills, BillResource::class, 'Bills retrieved successfully');
    }

    public function store(StoreBillRequest $request)
    {
        $bill = $this->billingService->create($request->validated());

        return $this->success(new BillResource($bill->load('patient')), 'Bill created successfully', 201);
    }

    public function show(Bill $bill)
    {
        $this->authorize('view', $bill);

        return $this->success(new BillResource($bill->load(['patient', 'items', 'discounts'])));
    }

    public function addItem(AddBillItemRequest $request, Bill $bill)
    {
        $bill = $this->billingService->addItem($bill, $request->validated());

        return $this->success(new BillResource($bill), 'Bill item added successfully', 201);
    }

    public function addDiscount(AddDiscountRequest $request, Bill $bill)
    {
        $bill = $this->billingService->addDiscount($bill, $request->validated());

        return $this->success(new BillResource($bill), 'Discount applied successfully', 201);
    }

    public function cancel(Bill $bill)
    {
        $this->authorize('update', $bill);

        $bill = $this->billingService->cancel($bill);

        return $this->success(new BillResource($bill), 'Bill cancelled successfully');
    }
}
