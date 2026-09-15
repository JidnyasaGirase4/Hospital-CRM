<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Discount;
use App\Models\PharmacySale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All money math here uses bcmath on the string values Eloquent's
 * decimal:2 cast returns - never native float arithmetic - per the
 * spec's "never use floating-point types for financial calculations".
 */
class BillingService
{
    public function __construct(private readonly SequenceGeneratorService $sequences) {}

    public function create(array $data): Bill
    {
        return DB::transaction(function () use ($data) {
            $bill = Bill::create([
                'bill_number' => $this->sequences->next('bill', 'INV'),
                'patient_id' => $data['patient_id'],
                'type' => $data['type'],
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'status' => 'unpaid',
                'created_by' => request()->user()?->id,
            ]);

            foreach ($data['items'] as $item) {
                $this->addItemWithoutRecalculating($bill, $item);
            }

            $this->recalculateTotals($bill);

            return $bill->fresh(['items', 'discounts']);
        });
    }

    public function addItem(Bill $bill, array $item): Bill
    {
        $this->assertEditable($bill);

        return DB::transaction(function () use ($bill, $item) {
            $this->addItemWithoutRecalculating($bill, $item);
            $this->recalculateTotals($bill);

            return $bill->fresh(['items', 'discounts']);
        });
    }

    public function addDiscount(Bill $bill, array $data): Bill
    {
        $this->assertEditable($bill);

        return DB::transaction(function () use ($bill, $data) {
            $amount = $data['type'] === 'percentage'
                ? bcdiv(bcmul((string) $bill->subtotal, (string) $data['value'], 4), '100', 2)
                : number_format((float) $data['value'], 2, '.', '');

            Discount::create([
                'bill_id' => $bill->id,
                'description' => $data['description'],
                'type' => $data['type'],
                'value' => $data['value'],
                'amount' => $amount,
                'approved_by' => request()->user()?->id,
            ]);

            $this->recalculateTotals($bill);

            return $bill->fresh(['items', 'discounts']);
        });
    }

    public function cancel(Bill $bill): Bill
    {
        if (bccomp((string) $bill->paid_amount, '0', 2) > 0) {
            throw ValidationException::withMessages([
                'status' => ['Cannot cancel a bill that already has payments recorded; refund first.'],
            ]);
        }

        $bill->update(['status' => 'cancelled']);

        return $bill->fresh();
    }

    public function createFromPharmacySale(PharmacySale $sale): Bill
    {
        $sale->loadMissing('items.batch.medicine');

        return $this->create([
            'patient_id' => $sale->patient_id,
            'type' => 'pharmacy',
            'source_type' => PharmacySale::class,
            'source_id' => $sale->id,
            'items' => $sale->items->map(fn ($item) => [
                'category' => 'medicine',
                'description' => $item->batch->medicine->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->all(),
        ]);
    }

    private function addItemWithoutRecalculating(Bill $bill, array $item): BillItem
    {
        $quantity = $item['quantity'] ?? 1;
        $unitPrice = (string) $item['unit_price'];
        $discount = (string) ($item['discount_amount'] ?? 0);
        $tax = (string) ($item['tax_amount'] ?? 0);

        $lineSubtotal = bcmul($unitPrice, (string) $quantity, 2);
        $total = bcadd(bcsub($lineSubtotal, $discount, 2), $tax, 2);

        return BillItem::create([
            'bill_id' => $bill->id,
            'category' => $item['category'] ?? 'other',
            'description' => $item['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $total,
        ]);
    }

    public function recalculateTotals(Bill $bill): void
    {
        $items = $bill->items()->get();

        $subtotal = $items->reduce(
            fn (string $carry, BillItem $item) => bcadd($carry, bcmul((string) $item->unit_price, (string) $item->quantity, 2), 2),
            '0'
        );

        $itemDiscounts = $items->reduce(fn (string $carry, BillItem $item) => bcadd($carry, (string) $item->discount_amount, 2), '0');
        $billDiscounts = (string) $bill->discounts()->sum('amount');
        $discountTotal = bcadd($itemDiscounts, $billDiscounts, 2);

        $taxTotal = $items->reduce(fn (string $carry, BillItem $item) => bcadd($carry, (string) $item->tax_amount, 2), '0');

        $totalAmount = bcadd(bcsub($subtotal, $discountTotal, 2), $taxTotal, 2);

        $status = match (true) {
            $bill->status === 'cancelled' => 'cancelled',
            bccomp((string) $bill->paid_amount, $totalAmount, 2) >= 0 && bccomp($totalAmount, '0', 2) > 0 => 'paid',
            bccomp((string) $bill->paid_amount, '0', 2) > 0 => 'partially-paid',
            default => 'unpaid',
        };

        $bill->update([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total_amount' => $totalAmount,
            'status' => $status,
        ]);
    }

    private function assertEditable(Bill $bill): void
    {
        if ($bill->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => ['Cannot modify a cancelled bill.'],
            ]);
        }
    }
}
