<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService
{
    public function create(array $data): Supplier
    {
        return Supplier::create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh();
    }
}
