<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsuranceCompanyRequest;
use App\Http\Resources\InsuranceCompanyResource;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;

class InsuranceCompanyController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', InsurancePolicy::class);

        return $this->success(InsuranceCompanyResource::collection(InsuranceCompany::where('is_active', true)->orderBy('name')->get()));
    }

    public function store(StoreInsuranceCompanyRequest $request)
    {
        $company = InsuranceCompany::create([...$request->validated(), 'is_active' => true]);

        return $this->success(new InsuranceCompanyResource($company), 'Insurance company created successfully', 201);
    }
}
