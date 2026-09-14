<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $supplierService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($suppliers, SupplierResource::class, 'Suppliers retrieved successfully');
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = $this->supplierService->create($request->validated());

        return $this->success(new SupplierResource($supplier), 'Supplier created successfully', 201);
    }

    public function show(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        return $this->success(new SupplierResource($supplier));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier = $this->supplierService->update($supplier, $request->validated());

        return $this->success(new SupplierResource($supplier), 'Supplier updated successfully');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        $supplier->update(['is_active' => false]);

        return $this->success(null, 'Supplier deactivated successfully');
    }
}
