<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const report = ref(null);
const loading = ref(true);
const error = ref('');

const icuStay = computed(() => (report.value?.admissions || []).some((a) => a.is_icu));

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get(`/patients/${route.params.id}/complete-report`);
        report.value = data.data;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load report';
    } finally {
        loading.value = false;
    }
}

function date(value) {
    return value ? new Date(value).toLocaleDateString() : '';
}

function dateTime(value) {
    return value ? new Date(value).toLocaleString() : '';
}

function printReport() {
    window.print();
}

onMounted(load);
</script>

<template>
    <PageLoader v-if="loading" />
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else-if="report" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 text-sm print:border-0 print:shadow-none print:rounded-none print:p-0 print:text-black space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-slate-800 pb-3">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Hospital CRM</h2>
                <p class="text-xs text-slate-500">Complete Patient Report</p>
            </div>
            <div class="text-right">
                <button type="button" class="btn btn-primary print:hidden" @click="printReport">Print Report</button>
                <p class="text-xs text-slate-500 mt-1">Generated: {{ dateTime(report.generated_at) }}</p>
            </div>
        </div>

        <!-- Patient -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div><p class="text-xs text-slate-500">Patient</p><p class="font-semibold">{{ report.patient.name }}</p></div>
            <div><p class="text-xs text-slate-500">MRN</p><p class="font-semibold">{{ report.patient.mrn }}</p></div>
            <div><p class="text-xs text-slate-500">Gender / DOB</p><p>{{ report.patient.gender || '—' }} · {{ date(report.patient.dob) || '—' }}</p></div>
            <div><p class="text-xs text-slate-500">Mobile</p><p>{{ report.patient.mobile || '—' }}</p></div>
            <div><p class="text-xs text-slate-500">Blood group</p><p>{{ report.patient.blood_group || '—' }}</p></div>
            <div class="col-span-1 sm:col-span-3"><p class="text-xs text-slate-500">Allergies</p><p>{{ report.patient.allergies || '—' }}</p></div>
        </div>

        <!-- Summary -->
        <section v-if="report.totals" class="break-inside-avoid">
            <h3 class="section-title">Summary of Charges</h3>
            <div class="grid sm:grid-cols-2 gap-6">
                <table class="w-full">
                    <thead><tr class="text-left border-b border-slate-400"><th class="py-1">Category</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <tr v-for="c in report.charges_by_category" :key="c.category" class="border-b border-slate-200">
                            <td class="py-1 capitalize">{{ c.category }}</td><td class="text-right">{{ c.amount }}</td>
                        </tr>
                        <tr v-if="!report.charges_by_category.length"><td colspan="2" class="py-2 text-slate-400">No charges</td></tr>
                    </tbody>
                </table>
                <dl class="space-y-1">
                    <div class="flex justify-between"><dt>Bills</dt><dd>{{ report.totals.bills_count }}<span v-if="report.totals.cancelled_count" class="text-slate-500"> ({{ report.totals.cancelled_count }} cancelled, excluded)</span></dd></div>
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ report.totals.subtotal }}</dd></div>
                    <div class="flex justify-between"><dt>Discount</dt><dd>-{{ report.totals.discount_total }}</dd></div>
                    <div class="flex justify-between"><dt>Tax</dt><dd>{{ report.totals.tax_total }}</dd></div>
                    <div class="flex justify-between font-bold border-t border-slate-400 pt-1"><dt>Grand Total</dt><dd>{{ report.totals.total_amount }}</dd></div>
                    <div class="flex justify-between"><dt>Total Paid</dt><dd>{{ report.totals.paid_amount }}</dd></div>
                    <div class="flex justify-between font-bold text-red-600 print:text-black"><dt>Total Outstanding</dt><dd>{{ report.totals.outstanding_amount }}</dd></div>
                </dl>
            </div>
        </section>

        <!-- Admissions / ICU -->
        <section v-if="report.admissions" class="break-inside-avoid">
            <h3 class="section-title">Admissions &amp; Bed Stays <span v-if="icuStay" class="ml-2 text-xs bg-red-100 text-red-700 print:bg-transparent print:border print:border-black px-2 py-0.5 rounded-full">ICU</span></h3>
            <p v-if="!report.admissions.length" class="empty">No admissions.</p>
            <div v-for="a in report.admissions" :key="a.id" class="mb-4">
                <div class="flex justify-between bg-slate-100 print:bg-slate-200 px-3 py-1.5 font-semibold">
                    <span>{{ dateTime(a.admission_date) }} → {{ a.discharge_date ? dateTime(a.discharge_date) : 'Still admitted' }}</span>
                    <span class="capitalize">{{ a.admission_type }} · {{ a.status }}</span>
                </div>
                <p class="px-3 pt-1"><span class="text-slate-500">Doctor:</span> {{ a.doctor || '—' }} · <span class="text-slate-500">Reason:</span> {{ a.reason || '—' }}</p>
                <table class="w-full mt-1">
                    <thead><tr class="text-left border-b border-slate-400"><th class="py-1 px-3">Ward / Room / Bed</th><th>From</th><th>To</th><th class="text-right">Days</th><th class="text-right">Rate</th><th class="text-right px-3">Charge</th></tr></thead>
                    <tbody>
                        <tr v-for="(s, i) in a.stays" :key="i" class="border-b border-slate-200">
                            <td class="py-1 px-3">{{ s.ward }} <span v-if="s.is_icu" class="font-semibold">(ICU)</span> · Room {{ s.room }} · Bed {{ s.bed }}</td>
                            <td>{{ dateTime(s.from) }}</td>
                            <td>{{ s.to ? dateTime(s.to) : 'Current' }}</td>
                            <td class="text-right">{{ s.days }}</td>
                            <td class="text-right">{{ s.daily_rate }}</td>
                            <td class="text-right px-3">{{ s.charge }}</td>
                        </tr>
                        <tr class="font-semibold"><td colspan="5" class="text-right py-1">Room total</td><td class="text-right px-3">{{ a.room_total }}</td></tr>
                    </tbody>
                </table>
                <p v-if="a.discharge_summary" class="px-3 pt-1"><span class="text-slate-500">Discharge summary:</span> {{ a.discharge_summary }}</p>
            </div>
        </section>

        <!-- Emergency -->
        <section v-if="report.emergency_visits && report.emergency_visits.length" class="break-inside-avoid">
            <h3 class="section-title">Emergency Visits</h3>
            <table class="w-full">
                <thead><tr class="text-left border-b border-slate-400"><th class="py-1">Date</th><th>Triage</th><th>Complaint</th><th>Treatment</th><th>Status</th></tr></thead>
                <tbody>
                    <tr v-for="v in report.emergency_visits" :key="v.id" class="border-b border-slate-200">
                        <td class="py-1">{{ dateTime(v.registered_at) }}</td><td class="capitalize">{{ v.triage_level }}</td>
                        <td>{{ v.chief_complaint }}</td><td>{{ v.treatment_notes }}</td><td class="capitalize">{{ v.status }}<span v-if="v.referred_to"> → {{ v.referred_to }}</span></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Surgery -->
        <section v-if="report.ot_schedules && report.ot_schedules.length" class="break-inside-avoid">
            <h3 class="section-title">Surgery / OT</h3>
            <table class="w-full">
                <thead><tr class="text-left border-b border-slate-400"><th class="py-1">Procedure</th><th>Date</th><th>OT room</th><th>Surgeon</th><th>Status</th></tr></thead>
                <tbody>
                    <tr v-for="s in report.ot_schedules" :key="s.id" class="border-b border-slate-200">
                        <td class="py-1">{{ s.procedure_name }}</td><td>{{ dateTime(s.scheduled_at) }}</td><td>{{ s.ot_room }}</td><td>{{ s.surgeon || '—' }}</td><td class="capitalize">{{ s.status }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Diagnoses -->
        <section v-if="report.diagnoses" class="break-inside-avoid">
            <h3 class="section-title">Diagnoses / Problems</h3>
            <p v-if="!report.diagnoses.length" class="empty">None recorded.</p>
            <ul class="divide-y divide-slate-200">
                <li v-for="d in report.diagnoses" :key="d.id" class="py-1.5">
                    <span class="font-semibold">{{ d.name }}</span><span v-if="d.code" class="text-slate-500"> ({{ d.code }})</span>
                    <span class="text-slate-500"> · {{ date(d.diagnosed_at) }} · {{ d.doctor || '—' }}</span>
                    <p v-if="d.notes" class="text-slate-600">{{ d.notes }}</p>
                </li>
            </ul>
        </section>

        <!-- Consultations -->
        <section v-if="report.consultations && report.consultations.length" class="break-inside-avoid">
            <h3 class="section-title">Consultations</h3>
            <ul class="divide-y divide-slate-200">
                <li v-for="c in report.consultations" :key="c.id" class="py-1.5">
                    <span class="font-semibold">{{ date(c.date) }}</span> · {{ c.doctor || '—' }}
                    <p><span class="text-slate-500">Complaint:</span> {{ c.chief_complaint || '—' }} <span v-if="c.diagnosis">· <span class="text-slate-500">Diagnosis:</span> {{ c.diagnosis }}</span></p>
                    <p v-if="c.clinical_notes" class="text-slate-600">{{ c.clinical_notes }}</p>
                    <p v-if="c.follow_up_date" class="text-slate-600">Follow-up: {{ date(c.follow_up_date) }}</p>
                </li>
            </ul>
        </section>

        <!-- Prescriptions -->
        <section v-if="report.prescriptions && report.prescriptions.length" class="break-inside-avoid">
            <h3 class="section-title">Prescriptions</h3>
            <div v-for="p in report.prescriptions" :key="p.id" class="mb-3">
                <p class="font-semibold">{{ date(p.date) }} · {{ p.doctor || '—' }} <span class="text-slate-500 capitalize">({{ p.status }})</span></p>
                <table class="w-full">
                    <thead><tr class="text-left border-b border-slate-400"><th class="py-1">Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th class="text-right">Qty</th></tr></thead>
                    <tbody>
                        <tr v-for="(i, idx) in p.items" :key="idx" class="border-b border-slate-200">
                            <td class="py-1">{{ i.medicine }}</td><td>{{ i.dosage }}</td><td>{{ i.frequency }}</td><td>{{ i.duration }}</td><td class="text-right">{{ i.quantity }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Medicines -->
        <section v-if="report.pharmacy_sales" class="break-inside-avoid">
            <h3 class="section-title">Medicines (Pharmacy Bills)</h3>
            <p v-if="!report.pharmacy_sales.length" class="empty">No pharmacy purchases.</p>
            <div v-for="s in report.pharmacy_sales" :key="s.id" class="mb-3">
                <div class="flex justify-between bg-slate-100 print:bg-slate-200 px-3 py-1.5 font-semibold">
                    <span>{{ s.invoice_number }} · {{ date(s.date) }}</span>
                    <span class="capitalize">{{ s.status }} · {{ s.payment_status }}</span>
                </div>
                <table class="w-full">
                    <thead><tr class="text-left border-b border-slate-400"><th class="py-1 px-3">Medicine</th><th>Batch</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right px-3">Total</th></tr></thead>
                    <tbody>
                        <tr v-for="(i, idx) in s.items" :key="idx" class="border-b border-slate-200">
                            <td class="py-1 px-3">{{ i.medicine }}</td><td>{{ i.batch_number }}</td><td class="text-right">{{ i.quantity }}</td><td class="text-right">{{ i.unit_price }}</td><td class="text-right px-3">{{ i.total_price }}</td>
                        </tr>
                        <tr class="font-semibold"><td colspan="4" class="text-right py-1">Net amount</td><td class="text-right px-3">{{ s.net_amount }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Lab -->
        <section v-if="report.lab_orders && report.lab_orders.length" class="break-inside-avoid">
            <h3 class="section-title">Laboratory Tests</h3>
            <div v-for="o in report.lab_orders" :key="o.id" class="mb-3">
                <p class="font-semibold">{{ dateTime(o.ordered_at) }} · {{ o.doctor || '—' }} <span class="text-slate-500 capitalize">({{ o.status }})</span></p>
                <div v-for="(i, idx) in o.items" :key="idx" class="pl-3 border-l-2 border-slate-300 mb-1">
                    <p>{{ i.test }} <span class="text-slate-500">· {{ i.status }} · {{ i.price ?? '—' }}</span></p>
                    <p v-for="(r, ri) in i.results" :key="ri" class="text-xs text-slate-600">
                        {{ r.parameter }}: <span class="font-semibold" :class="r.flag && r.flag !== 'normal' ? 'text-red-600 print:text-black' : ''">{{ r.value }}</span> {{ r.unit }}
                        <span v-if="r.reference_range">(ref {{ r.reference_range }})</span> <span v-if="r.flag">[{{ r.flag }}]</span>
                    </p>
                </div>
            </div>
        </section>

        <!-- Radiology -->
        <section v-if="report.radiology_orders && report.radiology_orders.length" class="break-inside-avoid">
            <h3 class="section-title">Radiology</h3>
            <div v-for="o in report.radiology_orders" :key="o.id" class="mb-2">
                <p><span class="font-semibold">{{ o.test }}</span> <span class="text-slate-500">({{ o.modality }}) · {{ dateTime(o.ordered_at) }} · {{ o.status }} · {{ o.price ?? '—' }}</span></p>
                <p v-if="o.impression" class="text-slate-600">Impression: {{ o.impression }}</p>
            </div>
        </section>

        <!-- Bills -->
        <section v-if="report.bills">
            <h3 class="section-title">Bills &amp; Payments</h3>
            <p v-if="!report.bills.length" class="empty">No bills.</p>
            <div v-for="bill in report.bills" :key="bill.id" class="mb-4 break-inside-avoid">
                <div class="flex justify-between bg-slate-100 print:bg-slate-200 px-3 py-1.5 font-semibold">
                    <span>{{ bill.bill_number }} · <span class="capitalize">{{ bill.type }}</span> · {{ date(bill.created_at) }}</span>
                    <span class="capitalize" :class="bill.status === 'cancelled' ? 'text-red-600' : ''">{{ bill.status }}</span>
                </div>
                <table class="w-full">
                    <thead><tr class="text-left border-b border-slate-400"><th class="py-1 px-3">Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right px-3">Total</th></tr></thead>
                    <tbody>
                        <tr v-for="item in bill.items" :key="item.id" class="border-b border-slate-200">
                            <td class="py-1 px-3">{{ item.description }}</td><td class="text-right">{{ item.quantity }}</td><td class="text-right">{{ item.unit_price }}</td>
                            <td class="text-right">{{ item.discount_amount }}</td><td class="text-right">{{ item.tax_amount }}</td><td class="text-right px-3">{{ item.total_amount }}</td>
                        </tr>
                        <tr v-for="d in bill.discounts" :key="`d${d.id}`" class="border-b border-slate-200 text-slate-600">
                            <td class="py-1 px-3" colspan="5">Discount: {{ d.description }}</td><td class="text-right px-3">-{{ d.amount }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex justify-between items-start px-3 pt-2 text-xs">
                    <div>
                        <p v-for="p in bill.payments" :key="p.id" class="text-slate-600">
                            Paid {{ p.amount }} by <span class="capitalize">{{ p.method }}</span> ({{ p.payment_number }}, {{ date(p.paid_at) }})<span v-if="Number(p.refunded_amount)"> — refunded {{ p.refunded_amount }}</span>
                        </p>
                    </div>
                    <div class="text-right space-y-0.5">
                        <p>Total: <span class="font-semibold">{{ bill.total_amount }}</span></p>
                        <p>Paid: {{ bill.paid_amount }}</p>
                        <p>Outstanding: <span class="font-semibold">{{ bill.outstanding_amount }}</span></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Insurance -->
        <section v-if="report.insurance_claims && report.insurance_claims.length" class="break-inside-avoid">
            <h3 class="section-title">Insurance Claims</h3>
            <table class="w-full">
                <thead><tr class="text-left border-b border-slate-400"><th class="py-1">Claim #</th><th>Policy</th><th>Bill</th><th>Status</th><th class="text-right">Requested</th><th class="text-right">Approved</th><th class="text-right">Rejected</th></tr></thead>
                <tbody>
                    <tr v-for="c in report.insurance_claims" :key="c.id" class="border-b border-slate-200">
                        <td class="py-1">{{ c.claim_number }}</td><td>{{ c.policy_number }}</td><td>{{ c.bill_number || '—' }}</td><td class="capitalize">{{ c.status }}</td>
                        <td class="text-right">{{ c.requested_amount }}</td><td class="text-right">{{ c.approved_amount ?? '—' }}</td><td class="text-right">{{ c.rejected_amount ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <p class="text-center text-xs text-slate-500 pt-4">This is a computer-generated report.</p>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 700;
    font-size: 0.95rem;
    border-bottom: 1px solid #94a3b8;
    padding-bottom: 0.25rem;
    margin-bottom: 0.5rem;
}

.empty {
    color: #94a3b8;
}
</style>
