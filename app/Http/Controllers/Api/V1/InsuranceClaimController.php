<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\ApproveClaimRequest;
use App\Http\Requests\Insurance\RejectClaimRequest;
use App\Http\Requests\Insurance\SettleClaimRequest;
use App\Http\Requests\Insurance\StoreInsuranceClaimRequest;
use App\Http\Resources\InsuranceClaimResource;
use App\Models\InsuranceClaim;
use App\Services\InsuranceService;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
    public function __construct(private readonly InsuranceService $insuranceService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', InsuranceClaim::class);

        $claims = InsuranceClaim::query()
            ->with('policy')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($claims, InsuranceClaimResource::class, 'Insurance claims retrieved successfully');
    }

    public function store(StoreInsuranceClaimRequest $request)
    {
        $claim = $this->insuranceService->submitClaim($request->validated());

        return $this->success(new InsuranceClaimResource($claim->load('policy')), 'Insurance claim submitted successfully', 201);
    }

    public function show(InsuranceClaim $insuranceClaim)
    {
        $this->authorize('view', $insuranceClaim);

        return $this->success(new InsuranceClaimResource($insuranceClaim->load(['policy', 'documents'])));
    }

    public function approve(ApproveClaimRequest $request, InsuranceClaim $insuranceClaim)
    {
        $claim = $this->insuranceService->approve($insuranceClaim, (float) $request->validated('approved_amount'));

        return $this->success(new InsuranceClaimResource($claim), 'Claim approved successfully');
    }

    public function reject(RejectClaimRequest $request, InsuranceClaim $insuranceClaim)
    {
        $claim = $this->insuranceService->reject($insuranceClaim, $request->validated('rejection_reason'));

        return $this->success(new InsuranceClaimResource($claim), 'Claim rejected');
    }

    public function settle(SettleClaimRequest $request, InsuranceClaim $insuranceClaim)
    {
        $claim = $this->insuranceService->settle($insuranceClaim, (float) $request->validated('settled_amount'));

        return $this->success(new InsuranceClaimResource($claim), 'Claim settled successfully');
    }
}
