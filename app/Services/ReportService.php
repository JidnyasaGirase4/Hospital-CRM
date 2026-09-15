<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\Bill;
use App\Models\LabOrderItem;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class ReportService
{
    public function revenue(?string $from, ?string $to): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $bills = Bill::query()->whereBetween('created_at', [$from, $to])->where('status', '!=', 'cancelled');

        $byType = (clone $bills)
            ->selectRaw('type, COUNT(*) as bill_count, SUM(total_amount) as billed, SUM(paid_amount) as collected')
            ->groupBy('type')
            ->get();

        $collected = (string) Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total_billed' => number_format((float) $bills->sum('total_amount'), 2, '.', ''),
            'total_collected' => number_format((float) $collected, 2, '.', ''),
            'by_type' => $byType,
        ];
    }

    public function bedOccupancy(): array
    {
        $total = Bed::count();
        $occupied = Bed::where('status', 'occupied')->count();

        $byWard = Bed::query()
            ->join('rooms', 'beds.room_id', '=', 'rooms.id')
            ->join('wards', 'rooms.ward_id', '=', 'wards.id')
            ->selectRaw('wards.name as ward_name, COUNT(*) as total_beds, SUM(CASE WHEN beds.status = "occupied" THEN 1 ELSE 0 END) as occupied_beds')
            ->groupBy('wards.id', 'wards.name')
            ->get();

        return [
            'total_beds' => $total,
            'occupied_beds' => $occupied,
            'occupancy_rate' => $total > 0 ? round($occupied / $total * 100, 1) : 0,
            'by_ward' => $byWard,
        ];
    }

    public function labTurnaround(?string $from, ?string $to): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $items = LabOrderItem::query()
            ->where('status', 'approved')
            ->whereBetween('created_at', [$from, $to])
            ->with('results')
            ->get();

        $turnaroundHours = $items
            ->map(function (LabOrderItem $item) {
                $approvedAt = $item->results->max('approved_at');

                return $approvedAt ? $item->created_at->diffInHours($approvedAt) : null;
            })
            ->filter(fn ($hours) => $hours !== null);

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'completed_items' => $items->count(),
            'average_turnaround_hours' => $turnaroundHours->isNotEmpty() ? round($turnaroundHours->avg(), 1) : null,
        ];
    }
}
