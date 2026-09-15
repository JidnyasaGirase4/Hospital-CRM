<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\MedicineResource;
use App\Services\InventoryService;
use App\Services\MedicineService;
use App\Services\ReportService;
use Illuminate\Http\Request;

/**
 * All report endpoints are gated on the single 'reports.view' permission
 * (granted to Accountant, Hospital Admin, Super Admin) rather than each
 * underlying module's own permission - a report is a read-only summary
 * view, not the same access as operating that module day to day.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly MedicineService $medicineService,
        private readonly InventoryService $inventoryService
    ) {}

    public function revenue(Request $request)
    {
        $this->authorizeReportAccess($request);

        return $this->success($this->reportService->revenue(
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null
        ));
    }

    public function bedOccupancy(Request $request)
    {
        $this->authorizeReportAccess($request);

        return $this->success($this->reportService->bedOccupancy());
    }

    public function pharmacyStock(Request $request)
    {
        $this->authorizeReportAccess($request);

        return $this->success([
            'low_stock_medicines' => MedicineResource::collection($this->medicineService->lowStock()->load('category')),
            'low_stock_inventory_items' => InventoryItemResource::collection($this->inventoryService->lowStock()),
        ]);
    }

    public function labTurnaround(Request $request)
    {
        $this->authorizeReportAccess($request);

        return $this->success($this->reportService->labTurnaround(
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null
        ));
    }

    private function authorizeReportAccess(Request $request): void
    {
        if (! $request->user()->hasPermission('reports.view')) {
            abort(403, 'This action is unauthorized');
        }
    }
}
