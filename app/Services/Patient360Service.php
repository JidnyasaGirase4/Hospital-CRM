<?php

namespace App\Services;

use App\Http\Resources\DiagnosisResource;
use App\Http\Resources\PrescriptionResource;
use App\Models\Patient;
use Illuminate\Support\Str;

/**
 * Builds the aggregated Patient 360° view (GET /patients/{id}/360).
 *
 * SELF-EXTENDING BY DESIGN: as each module lands (appointments, OPD visits,
 * consultations, prescriptions, lab/radiology orders, admissions, bills,
 * payments, documents, insurance...) it adds a relation method to the
 * Patient model — e.g. `appointments()`. The moment that relation method
 * exists, this service picks it up automatically via RELATIONS below; there
 * is nothing else to wire up. Add the new relation name to RELATIONS when
 * you add the method to the model.
 *
 * All relations are eager-loaded in a single `loadMissing()` call to avoid
 * N+1 queries no matter how many sections Patient 360° grows to include.
 */
class Patient360Service
{
    private const RELATIONS = [
        'appointments',
        'opdVisits',
        'consultations',
        'diagnoses',
        'prescriptions',
        'pharmacySales',
        'labOrders',
        'radiologyOrders',
        'admissions',
        'bills',
        'payments',
        'insurancePolicies',
        'documents',
    ];

    /**
     * relation name => API Resource class. A later phase adds an entry here
     * once its Resource class exists; until then the raw collection is
     * returned for that section so nothing breaks in the meantime.
     */
    private const RESOURCE_MAP = [
        'diagnoses' => DiagnosisResource::class,
        'prescriptions' => PrescriptionResource::class,
    ];

    public function build(Patient $patient): array
    {
        $available = array_values(array_filter(
            self::RELATIONS,
            fn (string $relation) => method_exists($patient, $relation)
        ));

        $patient->loadMissing($available);

        $sections = collect($available)
            ->mapWithKeys(function (string $relation) use ($patient) {
                $data = $patient->getRelation($relation);
                $resourceClass = self::RESOURCE_MAP[$relation] ?? null;

                return [
                    Str::snake($relation) => $resourceClass ? $resourceClass::collection($data) : $data,
                ];
            })
            ->all();

        return [
            'profile' => $patient,
            ...$sections,
        ];
    }
}
