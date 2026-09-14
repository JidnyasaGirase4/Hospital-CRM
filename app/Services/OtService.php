<?php

namespace App\Services;

use App\Models\OtSchedule;
use Illuminate\Validation\ValidationException;

class OtService
{
    public function schedule(array $data): OtSchedule
    {
        return OtSchedule::create([
            ...$data,
            'status' => 'scheduled',
        ]);
    }

    public function start(OtSchedule $schedule): OtSchedule
    {
        $this->assertStatus($schedule, 'scheduled', 'in-progress');

        $schedule->update(['status' => 'in-progress']);

        return $schedule->fresh();
    }

    public function complete(OtSchedule $schedule, ?string $notes = null): OtSchedule
    {
        $this->assertStatus($schedule, 'in-progress', 'completed');

        $schedule->update([
            'status' => 'completed',
            'notes' => $notes ?? $schedule->notes,
        ]);

        return $schedule->fresh();
    }

    public function cancel(OtSchedule $schedule): OtSchedule
    {
        if (in_array($schedule->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot cancel a schedule with status '{$schedule->status}'."],
            ]);
        }

        $schedule->update(['status' => 'cancelled']);

        return $schedule->fresh();
    }

    private function assertStatus(OtSchedule $schedule, string $expected, string $target): void
    {
        if ($schedule->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => ["Cannot move to '{$target}' from status '{$schedule->status}'; expected '{$expected}'."],
            ]);
        }
    }
}
