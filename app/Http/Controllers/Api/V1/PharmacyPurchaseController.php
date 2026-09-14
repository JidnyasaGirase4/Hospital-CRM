<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\StorePharmacyPurchaseRequest;
use App\Http\Resources\PharmacyPurchaseResource;
use App\Models\PharmacyPurchase;
use App\Services\PharmacyService;
use Illuminate\Http\Request;

class PharmacyPurchaseController extends Controller
{
    public function __construct(private readonly PharmacyService $pharmacyService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacyPurchase::class);

        $purchases = PharmacyPurchase::query()
            ->with('supplier')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->latest('purchase_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($purchases, PharmacyPurchaseResource::class, 'Pharmacy purchases retrieved successfully');
    }

    public function store(StorePharmacyPurchaseRequest $request)
    {
        $purchase = $this->pharmacyService->purchase($request->validated());

        return $this->success(new PharmacyPurchaseResource($purchase), 'Purchase recorded and stock updated successfully', 201);
    }

    public function show(PharmacyPurchase $pharmacyPurchase)
    {
        $this->authorize('view', $pharmacyPurchase);

        return $this->success(new PharmacyPurchaseResource($pharmacyPurchase->load(['supplier', 'items.medicine'])));
    }
}
