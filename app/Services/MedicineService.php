<?php

namespace App\Services;

use App\Models\Medicine;

class MedicineService
{
    public function create(array $data): Medicine
    {
        return Medicine::create([
            ...$data,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'allow_negative_stock' => $data['allow_negative_stock'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Medicine $medicine, array $data): Medicine
    {
        $medicine->update($data);

        return $medicine->fresh();
    }

    public function lowStock()
    {
        return Medicine::query()
            ->where('is_active', true)
            ->withSum('batches', 'quantity')
            ->get()
            ->filter(fn (Medicine $medicine) => (int) $medicine->batches_sum_quantity <= $medicine->reorder_level)
            ->values();
    }
}
