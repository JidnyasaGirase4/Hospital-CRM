<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\PharmacyPurchase;
use App\Models\PharmacyPurchaseItem;
use App\Models\PharmacyReturn;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the pharmacy stock ledger. The one rule every method here must
 * preserve: stock is only ever mutated inside a transaction, and never
 * allowed to go negative unless the specific medicine has
 * allow_negative_stock = true (hospital policy override).
 */
class PharmacyService
{
    public function __construct(private readonly SequenceGeneratorService $sequences) {}

    /**
     * Receiving stock from a supplier. Adds to (or opens) a batch per line
     * item; never touches an existing batch's identity, only its quantity
     * and reference prices.
     */
    public function purchase(array $data): PharmacyPurchase
    {
        return DB::transaction(function () use ($data) {
            $purchase = PharmacyPurchase::create([
                'purchase_number' => $this->sequences->next('pharmacy_purchase', 'PUR'),
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'status' => 'completed',
                'created_by' => request()->user()?->id,
                'total_amount' => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $batch = MedicineBatch::query()->firstOrNew([
                    'medicine_id' => $item['medicine_id'],
                    'batch_number' => $item['batch_number'],
                ]);

                $batch->fill([
                    'supplier_id' => $data['supplier_id'],
                    'expiry_date' => $item['expiry_date'],
                    'purchase_price' => $item['unit_cost'],
                    'selling_price' => $item['selling_price'] ?? $batch->selling_price,
                    'mrp' => $item['mrp'] ?? $batch->mrp,
                    'received_at' => $data['purchase_date'],
                ]);
                $batch->quantity = ($batch->quantity ?? 0) + $item['quantity'];
                $batch->save();

                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $total += $lineTotal;

                PharmacyPurchaseItem::create([
                    'pharmacy_purchase_id' => $purchase->id,
                    'medicine_id' => $item['medicine_id'],
                    'medicine_batch_id' => $batch->id,
                    'batch_number' => $item['batch_number'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $lineTotal,
                    'expiry_date' => $item['expiry_date'],
                ]);
            }

            $purchase->update(['total_amount' => $total]);

            return $purchase->fresh(['items', 'supplier']);
        });
    }

    /**
     * Sells/dispenses medicines, optionally against a prescription. Each
     * line item is allocated FEFO (first-expiring-first-out) across
     * whatever batches have stock, which may split one line across several
     * sale item rows if no single batch covers the full quantity.
     */
    public function createSale(array $data): PharmacySale
    {
        return DB::transaction(function () use ($data) {
            $discount = $data['discount_amount'] ?? 0;
            $tax = $data['tax_amount'] ?? 0;

            $sale = PharmacySale::create([
                'invoice_number' => $this->sequences->next('pharmacy_sale', 'PHR'),
                'patient_id' => $data['patient_id'] ?? null,
                'prescription_id' => $data['prescription_id'] ?? null,
                'sold_by' => request()->user()?->id,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'payment_status' => 'unpaid',
                'status' => 'completed',
                'total_amount' => 0,
                'net_amount' => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);
                $allocations = $this->allocateStock($medicine, (int) $item['quantity']);

                foreach ($allocations as $allocation) {
                    $unitPrice = $allocation['batch']->selling_price ?? $allocation['batch']->mrp ?? 0;
                    $lineTotal = round($unitPrice * $allocation['quantity'], 2);
                    $total += $lineTotal;

                    PharmacySaleItem::create([
                        'pharmacy_sale_id' => $sale->id,
                        'medicine_batch_id' => $allocation['batch']->id,
                        'prescription_item_id' => $item['prescription_item_id'] ?? null,
                        'quantity' => $allocation['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ]);
                }

                if (! empty($item['prescription_item_id'])) {
                    PrescriptionItem::where('id', $item['prescription_item_id'])
                        ->increment('dispensed_quantity', $item['quantity']);
                }
            }

            $sale->update([
                'total_amount' => $total,
                'net_amount' => round($total - $discount + $tax, 2),
            ]);

            if (! empty($data['prescription_id'])) {
                $this->refreshPrescriptionStatus(Prescription::findOrFail($data['prescription_id']));
            }

            return $sale->fresh(['items.batch.medicine', 'patient']);
        });
    }

    public function returnItem(PharmacySaleItem $saleItem, int $quantity, ?string $reason): PharmacyReturn
    {
        return DB::transaction(function () use ($saleItem, $quantity, $reason) {
            $alreadyReturned = $saleItem->returns()->sum('quantity');

            if ($alreadyReturned + $quantity > $saleItem->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Return quantity exceeds the quantity originally sold.'],
                ]);
            }

            $saleItem->batch()->lockForUpdate()->first()?->increment('quantity', $quantity);

            return PharmacyReturn::create([
                'pharmacy_sale_item_id' => $saleItem->id,
                'quantity' => $quantity,
                'reason' => $reason,
                'returned_by' => request()->user()?->id,
                'returned_at' => now(),
            ]);
        });
    }

    /**
     * @return array<int, array{batch: MedicineBatch, quantity: int}>
     */
    private function allocateStock(Medicine $medicine, int $quantity): array
    {
        $batches = MedicineBatch::query()
            ->where('medicine_id', $medicine->id)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>=', now()->toDateString())
            ->orderBy('expiry_date')
            ->lockForUpdate()
            ->get();

        $available = $batches->sum('quantity');
        $remaining = $quantity;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($batch->quantity, $remaining);
            $batch->decrement('quantity', $take);
            $allocations[] = ['batch' => $batch, 'quantity' => $take];
            $remaining -= $take;
        }

        if ($remaining > 0) {
            if (! $medicine->allow_negative_stock) {
                throw ValidationException::withMessages([
                    'items' => ["Insufficient stock for {$medicine->name}. Available: {$available}, requested: {$quantity}."],
                ]);
            }

            $fallbackBatch = MedicineBatch::query()
                ->where('medicine_id', $medicine->id)
                ->orderByDesc('expiry_date')
                ->lockForUpdate()
                ->first();

            if (! $fallbackBatch) {
                throw ValidationException::withMessages([
                    'items' => ["No batch exists for {$medicine->name}; cannot dispense."],
                ]);
            }

            $fallbackBatch->decrement('quantity', $remaining);
            $allocations[] = ['batch' => $fallbackBatch, 'quantity' => $remaining];
        }

        return $allocations;
    }

    private function refreshPrescriptionStatus(Prescription $prescription): void
    {
        $items = $prescription->items()->get();

        $allDispensed = $items->isNotEmpty() && $items->every(fn ($item) => $item->dispensed_quantity >= $item->quantity);
        $anyDispensed = $items->contains(fn ($item) => $item->dispensed_quantity > 0);

        $prescription->update([
            'status' => $allDispensed ? 'dispensed' : ($anyDispensed ? 'partially-dispensed' : 'active'),
        ]);
    }
}
