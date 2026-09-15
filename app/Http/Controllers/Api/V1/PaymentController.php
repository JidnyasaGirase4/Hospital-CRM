<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\RefundPaymentRequest;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\RefundResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('bill_id'), fn ($q) => $q->where('bill_id', $request->integer('bill_id')))
            ->latest('paid_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($payments, PaymentResource::class, 'Payments retrieved successfully');
    }

    public function store(StorePaymentRequest $request)
    {
        $payment = $this->paymentService->recordPayment($request->validated());

        return $this->success(new PaymentResource($payment), 'Payment recorded successfully', 201);
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        return $this->success(new PaymentResource($payment));
    }

    public function refund(RefundPaymentRequest $request, Payment $payment)
    {
        $refund = $this->paymentService->refund($payment, $request->validated());

        return $this->success(new RefundResource($refund), 'Refund processed successfully', 201);
    }
}
