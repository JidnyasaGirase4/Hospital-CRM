<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The spec's "critical rule" for IPD: a bed can never hold two concurrent
 * active patients. Enforced twice - once here with an app-level
 * lockForUpdate + explicit check (so the caller gets a clean 422 instead of
 * a raw SQL error), and again at the database itself via the
 * bed_allocations.active_bed_id generated-column unique index, which is the
 * real backstop against race conditions this lock can't fully rule out
 * under concurrent transactions.
 */
class BedAllocationService
{
    public function allocate(Admission $admission, int $bedId): BedAllocation
    {
        return DB::transaction(function () use ($admission, $bedId) {
            $bed = Bed::query()->lockForUpdate()->findOrFail($bedId);

            if ($bed->status !== 'available') {
                throw ValidationException::withMessages([
                    'bed_id' => ["Bed {$bed->bed_number} is not available (status: {$bed->status})."],
                ]);
            }

            $hasActiveAllocation = BedAllocation::query()
                ->where('bed_id', $bedId)
                ->whereNull('released_at')
                ->lockForUpdate()
                ->exists();

            if ($hasActiveAllocation) {
                throw ValidationException::withMessages([
                    'bed_id' => ['This bed already has an active patient allocated.'],
                ]);
            }

            $allocation = BedAllocation::create([
                'admission_id' => $admission->id,
                'bed_id' => $bedId,
                'allocated_at' => now(),
            ]);

            $bed->update(['status' => 'occupied']);

            return $allocation;
        });
    }

    public function release(BedAllocation $allocation): BedAllocation
    {
        return DB::transaction(function () use ($allocation) {
            $allocation->update(['released_at' => now()]);

            // Bed needs housekeeping before it can be reused; see markCleaned().
            $allocation->bed()->update(['status' => 'cleaning']);

            return $allocation->fresh();
        });
    }

    public function transfer(Admission $admission, int $newBedId): BedAllocation
    {
        return DB::transaction(function () use ($admission, $newBedId) {
            $current = $admission->currentBedAllocation()->lockForUpdate()->first();

            if ($current) {
                $this->release($current);
            }

            return $this->allocate($admission->fresh(), $newBedId);
        });
    }

    public function markCleaned(Bed $bed): Bed
    {
        if ($bed->status !== 'cleaning') {
            throw ValidationException::withMessages([
                'status' => ["Bed {$bed->bed_number} is not awaiting cleaning (status: {$bed->status})."],
            ]);
        }

        $bed->update(['status' => 'available']);

        return $bed->fresh();
    }
}
