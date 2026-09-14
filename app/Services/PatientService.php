<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class PatientService
{
    private const MRN_SEQUENCE_KEY = 'patient_mrn';

    private const MRN_PREFIX = 'MRN';

    public function __construct(private readonly SequenceGeneratorService $sequences) {}

    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $data['mrn'] = $this->sequences->next(self::MRN_SEQUENCE_KEY, self::MRN_PREFIX);
            $data['registered_by'] = $data['registered_by'] ?? request()->user()?->id;

            return Patient::create($data);
        });
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient->fresh();
    }

    public function delete(Patient $patient): void
    {
        $patient->delete();
    }
}
