<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly SequenceGeneratorService $sequences,
        private readonly BillingService $billingService
    ) {}

    public function recordPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $bill = null;

            if (! empty($data['bill_id'])) {
                $bill = Bill::query()->lockForUpdate()->findOrFail($data['bill_id']);

                if ($bill->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'bill_id' => ['Cannot record a payment against a cancelled bill.'],
                    ]);
                }

                $outstanding = $bill->outstandingAmount();

                if (bccomp((string) $data['amount'], $outstanding, 2) > 0) {
                    throw ValidationException::withMessages([
                        'amount' => ["Payment of {$data['amount']} exceeds the outstanding balance of {$outstanding}."],
                    ]);
                }
            }

            $payment = Payment::create([
                'payment_number' => $this->sequences->next('payment', 'PAY'),
                'bill_id' => $bill?->id,
                'patient_id' => $data['patient_id'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference_number' => $data['reference_number'] ?? null,
                'purpose' => $bill ? 'payment' : 'advance',
                'status' => 'completed',
                'received_by' => request()->user()?->id,
                'paid_at' => now(),
            ]);

            if ($bill) {
                $bill->update(['paid_amount' => bcadd((string) $bill->paid_amount, (string) $data['amount'], 2)]);
                $this->billingService->recalculateTotals($bill);
            }

            return $payment->fresh();
        });
    }

    public function refund(Payment $payment, array $data): Refund
    {
        return DB::transaction(function () use ($payment, $data) {
            $alreadyRefunded = (string) $payment->refunds()->sum('amount');
            $refundable = bcsub((string) $payment->amount, $alreadyRefunded, 2);

            if (bccomp((string) $data['amount'], $refundable, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => ["Refund of {$data['amount']} exceeds the refundable balance of {$refundable}."],
                ]);
            }

            $refund = Refund::create([
                'payment_id' => $payment->id,
                'amount' => $data['amount'],
                'reason' => $data['reason'] ?? null,
                'refunded_by' => request()->user()?->id,
                'refunded_at' => now(),
            ]);

            if ($payment->bill_id) {
                $bill = Bill::query()->lockForUpdate()->findOrFail($payment->bill_id);
                $bill->update(['paid_amount' => bcsub((string) $bill->paid_amount, (string) $data['amount'], 2)]);
                $this->billingService->recalculateTotals($bill);
            }

            return $refund->fresh();
        });
    }
}
