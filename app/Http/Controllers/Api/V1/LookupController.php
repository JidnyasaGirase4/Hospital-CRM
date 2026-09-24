<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Lean, read-only pick-list endpoints used to populate form dropdowns.
 *
 * Any signed-in user may call these: a receptionist booking an appointment or
 * a doctor writing a prescription must be able to pick a doctor or a medicine
 * without holding the full users.view / pharmacy.view permission, which also
 * exposes emails, stock levels and management screens.
 */
class LookupController extends Controller
{
    private const LIMIT = 50;

    public function staff(Request $request)
    {
        $staff = User::query()
            ->where('is_active', true)
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('name', 'like', $term)->orWhere('employee_code', 'like', $term);
            }))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('roles.slug', $request->string('role'))))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'employee_code']);

        return $this->success($staff, 'Staff retrieved successfully');
    }

    public function medicines(Request $request)
    {
        $medicines = Medicine::query()
            ->where('is_active', true)
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'generic_name', 'strength', 'form', 'unit']);

        return $this->success($medicines, 'Medicines retrieved successfully');
    }

    public function beds(Request $request)
    {
        $beds = Bed::query()
            ->where('status', 'available')
            ->with('room.ward')
            ->orderBy('room_id')
            ->orderBy('bed_number')
            ->get()
            ->map(fn (Bed $bed) => [
                'id' => $bed->id,
                'bed_number' => $bed->bed_number,
                'room_number' => $bed->room?->room_number,
                'ward' => $bed->room?->ward?->name,
            ]);

        return $this->success($beds, 'Available beds retrieved successfully');
    }
}
