<?php

namespace App\Services;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Notifications\ReportReadyNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Owns the lab workflow: Doctor Order -> Lab Order -> Sample Collection ->
 * Processing -> Result -> Approval -> Report.
 */
class LabService
{
    public function createOrder(array $data): LabOrder
    {
        return DB::transaction(function () use ($data) {
            $order = LabOrder::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'consultation_id' => $data['consultation_id'] ?? null,
                'status' => 'ordered',
                'ordered_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['test_ids'] as $testId) {
                $order->items()->create([
                    'lab_test_id' => $testId,
                    'status' => 'pending',
                ]);
            }

            return $order->load('items.test');
        });
    }

    public function collectSample(LabOrderItem $item): LabOrderItem
    {
        $this->assertStatus($item, 'pending', 'sample-collected');

        $item->update(['status' => 'sample-collected', 'sample_collected_at' => now()]);
        $this->refreshOrderStatus($item->order);

        return $item->fresh();
    }

    public function startProcessing(LabOrderItem $item): LabOrderItem
    {
        $this->assertStatus($item, 'sample-collected', 'processing');

        $item->update(['status' => 'processing']);
        $this->refreshOrderStatus($item->order);

        return $item->fresh();
    }

    public function recordResults(LabOrderItem $item, array $results): LabOrderItem
    {
        $this->assertStatus($item, 'processing', 'resulted');

        return DB::transaction(function () use ($item, $results) {
            foreach ($results as $result) {
                LabResult::create([
                    'lab_order_item_id' => $item->id,
                    'parameter_name' => $result['parameter_name'],
                    'result_value' => $result['result_value'],
                    'unit' => $result['unit'] ?? null,
                    'reference_range' => $result['reference_range'] ?? null,
                    'flag' => $result['flag'] ?? null,
                ]);
            }

            $item->update(['status' => 'resulted']);
            $this->refreshOrderStatus($item->order);

            return $item->fresh('results');
        });
    }

    public function approveResults(LabOrderItem $item, int $approverId): LabOrderItem
    {
        $this->assertStatus($item, 'resulted', 'approved');

        return DB::transaction(function () use ($item, $approverId) {
            $item->results()->update([
                'is_approved' => true,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            $item->update(['status' => 'approved']);
            $this->refreshOrderStatus($item->order);

            $order = $item->order()->with(['doctor', 'patient'])->first();

            if ($order->doctor) {
                Notification::send($order->doctor, new ReportReadyNotification(
                    'Laboratory',
                    $order->patient->fullName(),
                    $order->patient_id
                ));
            }

            return $item->fresh('results');
        });
    }

    private function assertStatus(LabOrderItem $item, string $expected, string $target): void
    {
        if ($item->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => ["Cannot move to '{$target}' from status '{$item->status}'; expected '{$expected}'."],
            ]);
        }
    }

    private function refreshOrderStatus(LabOrder $order): void
    {
        $items = $order->items()->get();

        $status = match (true) {
            $items->every(fn ($i) => $i->status === 'approved') => 'completed',
            $items->contains(fn ($i) => in_array($i->status, ['processing', 'resulted', 'approved'], true)) => 'processing',
            $items->contains(fn ($i) => $i->status === 'sample-collected') => 'sample-collected',
            default => 'ordered',
        };

        $order->update(['status' => $status]);
    }
}
