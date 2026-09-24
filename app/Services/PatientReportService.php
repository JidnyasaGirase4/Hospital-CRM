<?php

namespace App\Services;

use App\Http\Resources\BillResource;
use App\Http\Resources\PaymentResource;
use App\Models\Admission;
use App\Models\BedAllocation;
use App\Models\Bill;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\EmergencyVisit;
use App\Models\InsuranceClaim;
use App\Models\LabOrder;
use App\Models\OtSchedule;
use App\Models\Patient;
use App\Models\PharmacySale;
use App\Models\Prescription;
use App\Models\RadiologyOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Builds the "complete patient report": one document covering everything the
 * hospital did for and charged to one patient - ICU/ward stays, medicines,
 * lab, radiology, surgery, emergency, diagnoses, prescriptions and the full
 * bill / payment / insurance picture.
 *
 * Each section is only included when the requesting user holds that module's
 * `view` permission; a section they cannot see comes back as null so the
 * report never leaks data the user couldn't open elsewhere.
 */
class PatientReportService
{
    public function build(Patient $patient, User $user, Request $request): array
    {
        $can = fn (string $module) => $user->hasPermission("{$module}.view");

        $bills = $can('billing') ? $this->bills($patient, $user, $request) : null;

        return [
            'patient' => [
                'id' => $patient->id,
                'mrn' => $patient->mrn,
                'name' => $patient->fullName(),
                'gender' => $patient->gender,
                'dob' => $patient->dob?->toDateString(),
                'mobile' => $patient->mobile,
                'blood_group' => $patient->blood_group,
                'allergies' => $patient->allergies,
            ],
            'generated_at' => now()->toIso8601String(),
            'admissions' => $can('ipd') ? $this->admissions($patient) : null,
            'emergency_visits' => $can('emergency') ? $this->emergencyVisits($patient) : null,
            'ot_schedules' => $can('ot') ? $this->otSchedules($patient) : null,
            'pharmacy_sales' => $can('pharmacy') ? $this->pharmacySales($patient) : null,
            'lab_orders' => $can('laboratory') ? $this->labOrders($patient) : null,
            'radiology_orders' => $can('radiology') ? $this->radiologyOrders($patient) : null,
            'consultations' => $can('consultations') ? $this->consultations($patient) : null,
            'diagnoses' => $can('diagnoses') ? $this->diagnoses($patient) : null,
            'prescriptions' => $can('prescriptions') ? $this->prescriptions($patient) : null,
            'bills' => $bills['bills'] ?? null,
            'totals' => $bills['totals'] ?? null,
            'charges_by_category' => $bills['charges_by_category'] ?? null,
            'insurance_claims' => $can('insurance') ? $this->insuranceClaims($patient) : null,
        ];
    }

    private function admissions(Patient $patient): array
    {
        return $patient->admissions()
            ->with(['doctor', 'bedAllocations.bed.room.ward'])
            ->reorder('admission_date')
            ->get()
            ->map(function (Admission $admission) {
                $stays = $admission->bedAllocations->sortBy('allocated_at')->map(fn (BedAllocation $a) => $this->stay($a))->values();

                return [
                    'id' => $admission->id,
                    'admission_date' => $admission->admission_date,
                    'discharge_date' => $admission->discharge_date,
                    'admission_type' => $admission->admission_type,
                    'status' => $admission->status,
                    'reason' => $admission->reason,
                    'doctor' => $admission->doctor?->name,
                    'discharge_summary' => $admission->discharge_summary,
                    'is_icu' => $stays->contains('is_icu', true),
                    'stays' => $stays,
                    'room_total' => $stays->reduce(fn (string $c, array $s) => bcadd($c, $s['charge'], 2), '0.00'),
                ];
            })
            ->all();
    }

    private function stay(BedAllocation $allocation): array
    {
        $room = $allocation->bed->room;
        $ward = $room->ward;
        $end = $allocation->released_at ?? now();
        // Same day-rounding rule IpdBillingService uses for the final bill.
        $days = max(1, (int) ceil(Carbon::parse($allocation->allocated_at)->diffInHours($end) / 24));
        $rate = (string) ($room->daily_rate ?? '0.00');

        return [
            'ward' => $ward?->name,
            'ward_type' => $ward?->ward_type,
            'room' => $room->room_number,
            'room_type' => $room->room_type,
            'bed' => $allocation->bed->bed_number,
            'is_icu' => stripos((string) $ward?->ward_type, 'icu') !== false
                || stripos((string) $ward?->name, 'icu') !== false
                || stripos((string) $room->room_type, 'icu') !== false,
            'from' => $allocation->allocated_at,
            'to' => $allocation->released_at,
            'days' => $days,
            'daily_rate' => $rate,
            'charge' => bcmul($rate, (string) $days, 2),
        ];
    }

    private function emergencyVisits(Patient $patient): array
    {
        return EmergencyVisit::query()
            ->where('patient_id', $patient->id)
            ->with('doctor')
            ->oldest('registered_at')
            ->get()
            ->map(fn (EmergencyVisit $v) => [
                'id' => $v->id,
                'registered_at' => $v->registered_at,
                'triage_level' => $v->triage_level,
                'status' => $v->status,
                'chief_complaint' => $v->chief_complaint,
                'treatment_notes' => $v->treatment_notes,
                'referred_to' => $v->referred_to,
                'doctor' => $v->doctor?->name,
                'admission_id' => $v->admission_id,
            ])
            ->all();
    }

    private function otSchedules(Patient $patient): array
    {
        return OtSchedule::query()
            ->where('patient_id', $patient->id)
            ->with('surgeon')
            ->oldest('scheduled_at')
            ->get()
            ->map(fn (OtSchedule $s) => [
                'id' => $s->id,
                'procedure_name' => $s->procedure_name,
                'ot_room' => $s->ot_room,
                'scheduled_at' => $s->scheduled_at,
                'status' => $s->status,
                'surgeon' => $s->surgeon?->name,
                'notes' => $s->notes,
                'admission_id' => $s->admission_id,
            ])
            ->all();
    }

    private function pharmacySales(Patient $patient): array
    {
        return PharmacySale::query()
            ->where('patient_id', $patient->id)
            ->with('items.batch.medicine')
            ->oldest()
            ->get()
            ->map(fn (PharmacySale $sale) => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'date' => $sale->created_at,
                'status' => $sale->status,
                'payment_status' => $sale->payment_status,
                'total_amount' => $sale->total_amount,
                'discount_amount' => $sale->discount_amount,
                'tax_amount' => $sale->tax_amount,
                'net_amount' => $sale->net_amount,
                'items' => $sale->items->map(fn ($item) => [
                    'medicine' => trim(($item->batch?->medicine?->name ?? 'Medicine').' '.($item->batch?->medicine?->strength ?? '')),
                    'batch_number' => $item->batch?->batch_number,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ])->values(),
            ])
            ->all();
    }

    private function labOrders(Patient $patient): array
    {
        return LabOrder::query()
            ->where('patient_id', $patient->id)
            ->with(['doctor', 'items.test', 'items.results'])
            ->oldest('ordered_at')
            ->get()
            ->map(fn (LabOrder $order) => [
                'id' => $order->id,
                'ordered_at' => $order->ordered_at,
                'status' => $order->status,
                'doctor' => $order->doctor?->name,
                'items' => $order->items->map(fn ($item) => [
                    'test' => $item->test?->name,
                    'price' => $item->test?->price,
                    'status' => $item->status,
                    // Only approved values belong on a patient-facing report.
                    'results' => $item->results->where('is_approved', true)->map(fn ($r) => [
                        'parameter' => $r->parameter_name,
                        'value' => $r->result_value,
                        'unit' => $r->unit,
                        'reference_range' => $r->reference_range,
                        'flag' => $r->flag,
                    ])->values(),
                ])->values(),
            ])
            ->all();
    }

    private function radiologyOrders(Patient $patient): array
    {
        return RadiologyOrder::query()
            ->where('patient_id', $patient->id)
            ->with(['doctor', 'test', 'report'])
            ->oldest('ordered_at')
            ->get()
            ->map(fn (RadiologyOrder $order) => [
                'id' => $order->id,
                'ordered_at' => $order->ordered_at,
                'status' => $order->status,
                'test' => $order->test?->name,
                'modality' => $order->test?->modality,
                'price' => $order->test?->price,
                'doctor' => $order->doctor?->name,
                'findings' => $order->report?->is_approved ? $order->report->findings : null,
                'impression' => $order->report?->is_approved ? $order->report->impression : null,
            ])
            ->all();
    }

    private function consultations(Patient $patient): array
    {
        return $patient->consultations()
            ->with('doctor')
            ->reorder('created_at')
            ->get()
            ->map(fn (Consultation $c) => [
                'id' => $c->id,
                'date' => $c->created_at,
                'doctor' => $c->doctor?->name,
                'chief_complaint' => $c->chief_complaint,
                'symptoms' => $c->symptoms,
                'examination' => $c->examination,
                'diagnosis' => $c->diagnosis,
                'clinical_notes' => $c->clinical_notes,
                'follow_up_date' => $c->follow_up_date?->toDateString(),
            ])
            ->all();
    }

    private function diagnoses(Patient $patient): array
    {
        return $patient->diagnoses()
            ->with('doctor')
            ->reorder('diagnosed_at')
            ->get()
            ->map(fn (Diagnosis $d) => [
                'id' => $d->id,
                'diagnosed_at' => $d->diagnosed_at,
                'code' => $d->diagnosis_code,
                'name' => $d->diagnosis_name,
                'notes' => $d->notes,
                'doctor' => $d->doctor?->name,
            ])
            ->all();
    }

    private function prescriptions(Patient $patient): array
    {
        return $patient->prescriptions()
            ->with(['doctor', 'items.medicine'])
            ->reorder('created_at')
            ->get()
            ->map(fn (Prescription $p) => [
                'id' => $p->id,
                'date' => $p->created_at,
                'doctor' => $p->doctor?->name,
                'status' => $p->status,
                'notes' => $p->notes,
                'items' => $p->items->map(fn ($i) => [
                    'medicine' => $i->medicine?->name,
                    'dosage' => $i->dosage,
                    'frequency' => $i->frequency,
                    'duration' => $i->duration,
                    'route' => $i->route,
                    'instructions' => $i->instructions,
                    'quantity' => $i->quantity,
                ])->values(),
            ])
            ->all();
    }

    private function insuranceClaims(Patient $patient): array
    {
        return InsuranceClaim::query()
            ->whereHas('policy', fn ($q) => $q->where('patient_id', $patient->id))
            ->with(['policy', 'bill'])
            ->oldest()
            ->get()
            ->map(fn (InsuranceClaim $c) => [
                'id' => $c->id,
                'claim_number' => $c->claim_number,
                'policy_number' => $c->policy?->policy_number,
                'bill_number' => $c->bill?->bill_number,
                'status' => $c->status,
                'requested_amount' => $c->requested_amount,
                'approved_amount' => $c->approved_amount,
                'rejected_amount' => $c->rejected_amount,
                'rejection_reason' => $c->rejection_reason,
                'submitted_at' => $c->submitted_at,
                'settled_at' => $c->settled_at,
            ])
            ->all();
    }

    /**
     * Every bill with items, discounts and payments, plus totals and a
     * per-category charge breakdown. Cancelled bills are listed but excluded
     * from the totals.
     */
    private function bills(Patient $patient, User $user, Request $request): array
    {
        $withPayments = $user->hasPermission('payments.view');

        $bills = $patient->bills()
            ->with(['items', 'discounts'])
            ->when($withPayments, fn ($q) => $q->with('payments'))
            ->reorder('created_at')
            ->get();

        $active = $bills->where('status', '!=', 'cancelled');

        $byCategory = $active
            ->flatMap(fn (Bill $b) => $b->items)
            ->groupBy(fn ($item) => $item->category ?: 'other')
            ->map(fn (Collection $items, string $category) => [
                'category' => $category,
                'amount' => $items->reduce(fn (string $c, $i) => bcadd($c, (string) $i->total_amount, 2), '0.00'),
            ])
            ->values();

        $total = $this->sum($active, 'total_amount');
        $paid = $this->sum($active, 'paid_amount');

        return [
            'bills' => $bills->map(fn (Bill $bill) => [
                ...(new BillResource($bill))->resolve($request),
                'payments' => $withPayments ? PaymentResource::collection($bill->payments)->resolve($request) : [],
            ])->values()->all(),
            'charges_by_category' => $byCategory->all(),
            'totals' => [
                'bills_count' => $bills->count(),
                'cancelled_count' => $bills->count() - $active->count(),
                'subtotal' => $this->sum($active, 'subtotal'),
                'discount_total' => $this->sum($active, 'discount_total'),
                'tax_total' => $this->sum($active, 'tax_total'),
                'total_amount' => $total,
                'paid_amount' => $paid,
                'outstanding_amount' => bcsub($total, $paid, 2),
            ],
        ];
    }

    private function sum(Collection $bills, string $field): string
    {
        return $bills->reduce(fn (string $carry, Bill $b) => bcadd($carry, (string) $b->{$field}, 2), '0.00');
    }
}
