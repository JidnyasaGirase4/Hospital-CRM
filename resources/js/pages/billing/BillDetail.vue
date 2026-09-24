<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import Badge from '../../components/Badge.vue';
import Icon from '../../components/Icon.vue';
import { formatMoney, formatDate, formatDateTime } from '../../utils/format';

const ui = useUiStore();
const route = useRoute();
const router = useRouter();

const bill = ref(null);
const payments = ref([]);
const loading = ref(true);
const showItemForm = ref(false);
const showDiscountForm = ref(false);

const itemForm = ref({ category: '', description: '', quantity: 1, unit_price: '', discount_amount: '', tax_amount: '' });
const discountForm = ref({ description: '', type: 'fixed', value: '' });
const errors = ref({});

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/bills/${route.params.id}`);
    bill.value = data.data;
    const { data: paymentsRes } = await apiClient.get('/payments', { params: { bill_id: route.params.id, per_page: 50 } });
    payments.value = paymentsRes.data;
    loading.value = false;
}

async function addItem() {
    errors.value = {};
    try {
        const { data } = await apiClient.post(`/bills/${bill.value.id}/items`, itemForm.value);
        bill.value = data.data;
        showItemForm.value = false;
        itemForm.value = { category: '', description: '', quantity: 1, unit_price: '', discount_amount: '', tax_amount: '' };
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    }
}

async function addDiscount() {
    errors.value = {};
    try {
        const { data } = await apiClient.post(`/bills/${bill.value.id}/discounts`, discountForm.value);
        bill.value = data.data;
        showDiscountForm.value = false;
        discountForm.value = { description: '', type: 'fixed', value: '' };
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    }
}

async function cancelBill() {
    if (!(await ui.confirm({ title: 'Cancel Bill', message: 'Cancel this bill?', confirmLabel: 'Cancel Bill', danger: true }))) return;
    await apiClient.patch(`/bills/${bill.value.id}/cancel`);
    ui.toast('Bill cancelled', 'success');
    load();
}

function recordPayment() {
    router.push({ name: 'payments.create', query: { bill_id: bill.value.id, patient_id: bill.value.patient?.id } });
}

async function refundPayment(payment) {
    const result = await ui.prompt({
        title: 'Refund Payment',
        fields: [
            { key: 'amount', label: 'Refund amount', type: 'number', step: '0.01', value: payment.amount, required: true },
            { key: 'reason', label: 'Reason (optional)', type: 'text' },
        ],
    });
    if (!result) return;
    try {
        await apiClient.post(`/payments/${payment.id}/refund`, { amount: Number(result.amount), reason: result.reason || undefined });
        ui.toast('Refund processed', 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Refund failed', 'error');
    }
}

function printBill() {
    window.print();
}

onMounted(load);
</script>

<template>
    <PageLoader v-if="loading" />
    <div v-else-if="bill">
    <div class="space-y-5 print:hidden">
        <PageHero
            :title="bill.bill_number"
            :status="bill.status"
            :subtitle="`${bill.patient?.name ?? 'Unknown patient'} (${bill.patient?.mrn ?? '—'}) · ${bill.type} bill · ${formatDate(bill.created_at)}`"
        >
            <template #actions>
                <button type="button" class="btn btn-secondary" @click="printBill"><Icon name="print" :size="16" /> Print</button>
                <PermissionGate permission="payments.create">
                    <button v-if="bill.status !== 'cancelled' && Number(bill.outstanding_amount) > 0" type="button" class="btn btn-primary" @click="recordPayment"><Icon name="banknote" :size="16" /> Record Payment</button>
                </PermissionGate>
                <PermissionGate permission="billing.update">
                    <button v-if="bill.status !== 'cancelled'" type="button" class="btn btn-danger-soft" @click="cancelBill">Cancel Bill</button>
                </PermissionGate>
            </template>
        </PageHero>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <SectionCard title="Line Items" :count="bill.items?.length ?? 0" flush>
                    <template #actions>
                        <PermissionGate permission="billing.update">
                            <button v-if="bill.status !== 'cancelled'" type="button" class="btn btn-sm btn-soft" @click="showItemForm = !showItemForm">
                                {{ showItemForm ? 'Cancel' : '+ Add item' }}
                            </button>
                        </PermissionGate>
                    </template>
                    <form v-if="showItemForm" class="grid grid-cols-2 gap-2.5 border-b border-slate-100 bg-slate-50/70 p-5 sm:grid-cols-6" @submit.prevent="addItem">
                        <input v-model="itemForm.category" placeholder="Category" class="input input-sm" />
                        <input v-model="itemForm.description" placeholder="Description" class="input input-sm col-span-2" />
                        <input v-model.number="itemForm.quantity" type="number" min="1" placeholder="Qty" class="input input-sm" />
                        <input v-model.number="itemForm.unit_price" type="number" step="0.01" placeholder="Unit price" class="input input-sm" />
                        <button type="submit" class="btn btn-primary btn-sm">Add</button>
                        <p v-if="errors.description" class="field-error col-span-full">{{ errors.description[0] }}</p>
                    </form>
                    <div class="overflow-x-auto px-3 pb-3 pt-2">
                        <table class="table-simple">
                            <thead>
                                <tr><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right">Total</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in bill.items" :key="item.id">
                                    <td class="font-medium text-slate-800">{{ item.description }}</td>
                                    <td class="text-right tabular-nums">{{ item.quantity }}</td>
                                    <td class="text-right tabular-nums">{{ formatMoney(item.unit_price) }}</td>
                                    <td class="text-right tabular-nums">{{ formatMoney(item.discount_amount) }}</td>
                                    <td class="text-right tabular-nums">{{ formatMoney(item.tax_amount) }}</td>
                                    <td class="text-right font-semibold tabular-nums text-slate-900">{{ formatMoney(item.total_amount) }}</td>
                                </tr>
                                <tr v-if="!bill.items || bill.items.length === 0"><td colspan="6" class="empty-note">No items on this bill yet</td></tr>
                            </tbody>
                        </table>
                    </div>
                </SectionCard>

                <SectionCard title="Payments" :count="payments.length" flush>
                    <div class="overflow-x-auto px-3 pb-3 pt-2">
                        <table class="table-simple">
                            <thead>
                                <tr><th>Payment #</th><th class="text-right">Amount</th><th>Method</th><th>Status</th><th class="text-right">Refunded</th><th>Paid At</th><th class="text-right">Actions</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in payments" :key="p.id">
                                    <td class="font-medium text-slate-800">{{ p.payment_number }}</td>
                                    <td class="text-right font-semibold tabular-nums text-slate-900">{{ formatMoney(p.amount) }}</td>
                                    <td class="capitalize">{{ p.method }}</td>
                                    <td><Badge :value="p.status" /></td>
                                    <td class="text-right tabular-nums">{{ formatMoney(p.refunded_amount) }}</td>
                                    <td class="whitespace-nowrap">{{ formatDateTime(p.paid_at) }}</td>
                                    <td class="text-right">
                                        <PermissionGate permission="payments.refund">
                                            <button type="button" class="btn btn-sm btn-danger-soft" @click="refundPayment(p)">Refund</button>
                                        </PermissionGate>
                                    </td>
                                </tr>
                                <tr v-if="payments.length === 0"><td colspan="7" class="empty-note">No payments recorded yet</td></tr>
                            </tbody>
                        </table>
                    </div>
                </SectionCard>
            </div>

            <div class="space-y-5">
                <section class="card overflow-hidden">
                    <header class="card-header"><h3 class="card-title">Bill Summary</h3></header>
                    <dl class="space-y-2.5 p-5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-medium tabular-nums">{{ formatMoney(bill.subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="font-medium tabular-nums text-emerald-600">- {{ formatMoney(bill.discount_total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd class="font-medium tabular-nums">{{ formatMoney(bill.tax_total) }}</dd></div>
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-base"><dt class="font-bold text-slate-800">Total</dt><dd class="font-extrabold tabular-nums text-slate-900">{{ formatMoney(bill.total_amount) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd class="font-semibold tabular-nums text-emerald-600">{{ formatMoney(bill.paid_amount) }}</dd></div>
                    </dl>
                    <div class="flex items-center justify-between px-5 py-4" :class="Number(bill.outstanding_amount) > 0 ? 'bg-red-50' : 'bg-emerald-50'">
                        <span class="text-sm font-bold" :class="Number(bill.outstanding_amount) > 0 ? 'text-red-700' : 'text-emerald-700'">{{ Number(bill.outstanding_amount) > 0 ? 'Outstanding' : 'Fully settled' }}</span>
                        <span class="text-xl font-extrabold tabular-nums" :class="Number(bill.outstanding_amount) > 0 ? 'text-red-700' : 'text-emerald-700'">{{ formatMoney(bill.outstanding_amount) }}</span>
                    </div>
                </section>

                <SectionCard title="Discounts" :count="bill.discounts?.length ?? 0">
                    <template #actions>
                        <PermissionGate permission="billing.update">
                            <button v-if="bill.status !== 'cancelled'" type="button" class="btn btn-sm btn-soft" @click="showDiscountForm = !showDiscountForm">
                                {{ showDiscountForm ? 'Cancel' : '+ Add' }}
                            </button>
                        </PermissionGate>
                    </template>
                    <form v-if="showDiscountForm" class="mb-4 space-y-2.5 rounded-xl bg-slate-50 p-3.5" @submit.prevent="addDiscount">
                        <input v-model="discountForm.description" placeholder="Description" class="input input-sm" />
                        <div class="grid grid-cols-2 gap-2.5">
                            <select v-model="discountForm.type" class="input input-sm">
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                            <input v-model.number="discountForm.value" type="number" step="0.01" placeholder="Value" class="input input-sm" />
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-full">Apply discount</button>
                    </form>
                    <ul class="divide-y divide-slate-100 text-sm">
                        <li v-for="d in bill.discounts" :key="d.id" class="flex items-center justify-between gap-3 py-2.5">
                            <span class="min-w-0"><span class="block truncate font-medium text-slate-800">{{ d.description }}</span><span class="text-xs capitalize text-slate-400">{{ d.type }}: {{ d.value }}</span></span>
                            <span class="font-semibold tabular-nums text-emerald-600">- {{ formatMoney(d.amount) }}</span>
                        </li>
                        <li v-if="!bill.discounts || bill.discounts.length === 0" class="empty-note !py-4">No discounts applied</li>
                    </ul>
                </SectionCard>
            </div>
        </div>
    </div>

    <!-- Print-only invoice -->
    <div class="hidden print:block text-black text-sm">
        <div class="flex justify-between items-start border-b-2 border-black pb-3">
            <div>
                <h1 class="text-2xl font-bold">Hospital CRM</h1>
                <p class="text-xs">Bill / Invoice</p>
            </div>
            <div class="text-right">
                <p class="font-semibold">{{ bill.bill_number }}</p>
                <p class="text-xs">Date: {{ formatDate(bill.created_at) }}</p>
                <p class="text-xs capitalize">Status: {{ bill.status }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 py-3">
            <div>
                <p class="text-xs text-slate-600">Patient</p>
                <p class="font-semibold">{{ bill.patient?.name }}</p>
                <p class="text-xs">MRN: {{ bill.patient?.mrn }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-600">Bill type</p>
                <p class="font-semibold capitalize">{{ bill.type }}</p>
            </div>
        </div>

        <table class="w-full border-collapse">
            <thead>
                <tr class="border-y border-black text-left">
                    <th class="py-1.5">#</th><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th>
                    <th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, i) in bill.items" :key="item.id" class="border-b border-slate-300">
                    <td class="py-1.5">{{ i + 1 }}</td>
                    <td>{{ item.description }}</td>
                    <td class="text-right">{{ item.quantity }}</td>
                    <td class="text-right">{{ item.unit_price }}</td>
                    <td class="text-right">{{ item.discount_amount }}</td>
                    <td class="text-right">{{ item.tax_amount }}</td>
                    <td class="text-right">{{ item.total_amount }}</td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-end mt-4">
            <dl class="w-64 space-y-1">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ bill.subtotal }}</dd></div>
                <div class="flex justify-between"><dt>Discount</dt><dd>-{{ bill.discount_total }}</dd></div>
                <div class="flex justify-between"><dt>Tax</dt><dd>{{ bill.tax_total }}</dd></div>
                <div class="flex justify-between border-t border-black pt-1 font-bold"><dt>Total</dt><dd>{{ bill.total_amount }}</dd></div>
                <div class="flex justify-between"><dt>Paid</dt><dd>{{ bill.paid_amount }}</dd></div>
                <div class="flex justify-between font-bold"><dt>Outstanding</dt><dd>{{ bill.outstanding_amount }}</dd></div>
            </dl>
        </div>

        <div v-if="payments.length" class="mt-6">
            <p class="font-semibold mb-1">Payments</p>
            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr class="border-y border-black text-left"><th class="py-1">Payment #</th><th>Method</th><th>Date</th><th class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                    <tr v-for="p in payments" :key="p.id" class="border-b border-slate-300">
                        <td class="py-1">{{ p.payment_number }}</td>
                        <td class="capitalize">{{ p.method }}</td>
                        <td>{{ formatDate(p.paid_at) }}</td>
                        <td class="text-right">{{ p.amount }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-center text-xs text-slate-600 mt-10">Thank you. This is a computer-generated bill.</p>
    </div>
    </div>
</template>
