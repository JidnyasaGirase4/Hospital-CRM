<?php

namespace App\Services;

use App\Models\RadiologyOrder;
use App\Models\RadiologyReport;
use App\Notifications\ReportReadyNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RadiologyService
{
    public function createOrder(array $data): RadiologyOrder
    {
        return RadiologyOrder::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'radiology_test_id' => $data['radiology_test_id'],
            'consultation_id' => $data['consultation_id'] ?? null,
            'status' => 'ordered',
            'ordered_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function schedule(RadiologyOrder $order): RadiologyOrder
    {
        if ($order->status !== 'ordered') {
            throw ValidationException::withMessages([
                'status' => ["Cannot schedule an order with status '{$order->status}'."],
            ]);
        }

        $order->update(['status' => 'scheduled']);

        return $order->fresh();
    }

    public function submitReport(RadiologyOrder $order, array $data, int $reportedBy): RadiologyReport
    {
        return DB::transaction(function () use ($order, $data, $reportedBy) {
            $report = RadiologyReport::updateOrCreate(
                ['radiology_order_id' => $order->id],
                [
                    'findings' => $data['findings'] ?? null,
                    'impression' => $data['impression'] ?? null,
                    'reported_by' => $reportedBy,
                    'reported_at' => now(),
                ]
            );

            return $report;
        });
    }

    public function approveReport(RadiologyReport $report, int $approverId): RadiologyReport
    {
        return DB::transaction(function () use ($report, $approverId) {
            $report->update([
                'is_approved' => true,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            $order = $report->order()->with(['doctor', 'patient'])->first();
            $order->update(['status' => 'completed']);

            if ($order->doctor) {
                Notification::send($order->doctor, new ReportReadyNotification(
                    'Radiology',
                    $order->patient->fullName(),
                    $order->patient_id
                ));
            }

            return $report->fresh();
        });
    }
}
