<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\StoreMedicineRequest;
use App\Http\Requests\Pharmacy\UpdateMedicineRequest;
use App\Http\Resources\MedicineResource;
use App\Models\Medicine;
use App\Services\MedicineService;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function __construct(private readonly MedicineService $medicineService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Medicine::class);

        $medicines = Medicine::query()
            ->with('category')
            ->withSum('batches', 'quantity')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($medicines, MedicineResource::class, 'Medicines retrieved successfully');
    }

    public function store(StoreMedicineRequest $request)
    {
        $medicine = $this->medicineService->create($request->validated());

        return $this->success(new MedicineResource($medicine), 'Medicine created successfully', 201);
    }

    public function show(Medicine $medicine)
    {
        $this->authorize('view', $medicine);

        return $this->success(new MedicineResource($medicine->load(['category', 'batches' => fn ($q) => $q->orderBy('expiry_date')])));
    }

    public function update(UpdateMedicineRequest $request, Medicine $medicine)
    {
        $medicine = $this->medicineService->update($medicine, $request->validated());

        return $this->success(new MedicineResource($medicine), 'Medicine updated successfully');
    }

    public function destroy(Medicine $medicine)
    {
        $this->authorize('delete', $medicine);

        $medicine->update(['is_active' => false]);

        return $this->success(null, 'Medicine deactivated successfully');
    }

    public function lowStock()
    {
        $this->authorize('viewAny', Medicine::class);

        return $this->success(MedicineResource::collection($this->medicineService->lowStock()->load('category')));
    }
}
