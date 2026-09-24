<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\StorePharmacyReturnRequest;
use App\Http\Requests\Pharmacy\StorePharmacySaleRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\PharmacySaleResource;
use App\Models\Bill;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Services\BillingService;
use App\Services\PharmacyService;
use Illuminate\Http\Request;

class PharmacySaleController extends Controller
{
    public function __construct(
        private readonly PharmacyService $pharmacyService,
        private readonly BillingService $billingService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacySale::class);

        $sales = PharmacySale::query()
            ->with('patient')
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('prescription_id'), fn ($q) => $q->where('prescription_id', $request->integer('prescription_id')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($sales, PharmacySaleResource::class, 'Pharmacy sales retrieved successfully');
    }

    public function store(StorePharmacySaleRequest $request)
    {
        $sale = $this->pharmacyService->createSale($request->validated());

        return $this->success(new PharmacySaleResource($sale), 'Sale completed and stock updated successfully', 201);
    }

    public function show(PharmacySale $sale)
    {
        $this->authorize('view', $sale);

        return $this->success(new PharmacySaleResource($sale->load(['patient', 'items.batch.medicine'])));
    }

    public function storeReturn(StorePharmacyReturnRequest $request, PharmacySale $sale)
    {
        $saleItem = PharmacySaleItem::where('pharmacy_sale_id', $sale->id)
            ->findOrFail($request->validated('pharmacy_sale_item_id'));

        $return = $this->pharmacyService->returnItem(
            $saleItem,
            $request->validated('quantity'),
            $request->validated('reason')
        );

        return $this->success($return, 'Return processed and stock restored successfully', 201);
    }

    public function generateBill(PharmacySale $sale)
    {
        $this->authorize('view', $sale);

        // Billing staff can bill any sale; whoever can dispense may also bill
        // the sale they just made, so the pharmacy counter isn't blocked on
        // a permission (billing.create) that pharmacists don't hold.
        if (! request()->user()->can('create', Bill::class)) {
            $this->authorize('create', PharmacySale::class);
        }

        $bill = $this->billingService->createFromPharmacySale($sale);

        return $this->success(new BillResource($bill->load(['patient', 'items'])), 'Pharmacy bill generated successfully', 201);
    }
}
