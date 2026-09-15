<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsurancePolicyRequest;
use App\Http\Resources\InsurancePolicyResource;
use App\Models\InsurancePolicy;
use App\Services\InsuranceService;
use Illuminate\Http\Request;

class InsurancePolicyController extends Controller
{
    public function __construct(private readonly InsuranceService $insuranceService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', InsurancePolicy::class);

        $policies = InsurancePolicy::query()
            ->with('insuranceCompany')
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($policies, InsurancePolicyResource::class, 'Insurance policies retrieved successfully');
    }

    public function store(StoreInsurancePolicyRequest $request)
    {
        $policy = $this->insuranceService->createPolicy($request->validated());

        return $this->success(new InsurancePolicyResource($policy->load('insuranceCompany')), 'Insurance policy created successfully', 201);
    }

    public function show(InsurancePolicy $insurancePolicy)
    {
        $this->authorize('view', $insurancePolicy);

        return $this->success(new InsurancePolicyResource($insurancePolicy->load('insuranceCompany')));
    }
}
