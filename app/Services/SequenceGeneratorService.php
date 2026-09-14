<?php

namespace App\Services;

use App\Models\SequenceCounter;
use Illuminate\Support\Facades\DB;

/**
 * Generates gapless, atomically-incrementing formatted identifiers (MRNs,
 * invoice numbers, etc.) shared across modules via a single counters table.
 * Safe under concurrent requests: the counter row is locked for the
 * duration of the increment.
 */
class SequenceGeneratorService
{
    public function next(string $key, string $prefix, int $padLength = 6): string
    {
        return DB::transaction(function () use ($key, $prefix, $padLength) {
            // Atomic upsert (INSERT ... ON DUPLICATE KEY UPDATE) so a brand
            // new key never races two concurrent "create if missing" calls.
            SequenceCounter::query()->upsert(
                ['key' => $key, 'value' => 0],
                ['key'],
                []
            );

            $counter = SequenceCounter::query()->lockForUpdate()->findOrFail($key);
            $counter->increment('value');

            return $prefix.str_pad((string) $counter->value, $padLength, '0', STR_PAD_LEFT);
        });
    }
}
