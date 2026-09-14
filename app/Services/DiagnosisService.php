<?php

namespace App\Services;

use App\Models\Diagnosis;

class DiagnosisService
{
    public function create(array $data): Diagnosis
    {
        return Diagnosis::create([
            ...$data,
            'diagnosed_at' => $data['diagnosed_at'] ?? now(),
        ]);
    }

    public function update(Diagnosis $diagnosis, array $data): Diagnosis
    {
        $diagnosis->update($data);

        return $diagnosis->fresh();
    }
}
