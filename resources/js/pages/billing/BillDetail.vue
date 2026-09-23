<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';

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

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="bill" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ bill.bill_number }}</h2>
                    <p class="text-sm text-slate-500">{{ bill.patient?.name }} ({{ bill.patient?.mrn }}) · {{ bill.type }} · {{ bill.status }}</p>
                </div>
                <div class="flex gap-3">
                    <PermissionGate permission="payments.create">
                        <button type="button" class="text-sm text-brand-600 hover:underline" @click="recordPayment">Record Payment</button>
                    </PermissionGate>
                    <PermissionGate permission="billing.update">
                        <button v-if="bill.status !== 'cancelled'" type="button" class="text-sm text-red-600 hover:underline" @click="cancelBill">Cancel Bill</button>
                    </PermissionGate>
                </div>
            </div>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 text-sm">
                <div><dt class="text-slate-400">Subtotal</dt><dd>{{ bill.subtotal }}</dd></div>
                <div><dt class="text-slate-400">Discount</dt><dd>{{ bill.discount_total }}</dd></div>
                <div><dt class="text-slate-400">Tax</dt><dd>{{ bill.tax_total }}</dd></div>
                <div><dt class="text-slate-400">Total</dt><dd class="font-semibold">{{ bill.total_amount }}</dd></div>
                <div><dt class="text-slate-400">Paid</dt><dd>{{ bill.paid_amount }}</dd></div>
                <div><dt class="text-slate-400">Outstanding</dt><dd class="font-semibold text-red-600">{{ bill.outstanding_amount }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700">Line Items</h3>
                <PermissionGate permission="billing.update">
                    <button v-if="bill.status !== 'cancelled'" type="button" class="text-brand-600 text-xs hover:underline" @click="showItemForm = !showItemForm">
                        {{ showItemForm ? 'Cancel' : '+ Add item' }}
                    </button>
                </PermissionGate>
            </div>
            <form v-if="showItemForm" class="grid grid-cols-2 sm:grid-cols-6 gap-2 mb-4" @submit.prevent="addItem">
                <input v-model="itemForm.category" placeholder="Category" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model="itemForm.description" placeholder="Description" class="col-span-2 border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model.number="itemForm.quantity" type="number" min="1" placeholder="Qty" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model.number="itemForm.unit_price" type="number" step="0.01" placeholder="Unit price" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add</button>
                <p v-if="errors.description" class="text-xs text-red-600 col-span-6">{{ errors.description[0] }}</p>
            </form>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500">
                    <tr><th class="py-1">Description</th><th>Qty</th><th>Unit Price</th><th>Discount</th><th>Tax</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="item in bill.items" :key="item.id">
                        <td class="py-1.5">{{ item.description }}</td>
                        <td>{{ item.quantity }}</td>
                        <td>{{ item.unit_price }}</td>
                        <td>{{ item.discount_amount }}</td>
                        <td>{{ item.tax_amount }}</td>
                        <td class="text-right">{{ item.total_amount }}</td>
                    </tr>
                    <tr v-if="!bill.items || bill.items.length === 0"><td colspan="6" class="text-slate-400 py-3 text-center">No items</td></tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700">Discounts</h3>
                <PermissionGate permission="billing.update">
                    <button v-if="bill.status !== 'cancelled'" type="button" class="text-brand-600 text-xs hover:underline" @click="showDiscountForm = !showDiscountForm">
                        {{ showDiscountForm ? 'Cancel' : '+ Add discount' }}
                    </button>
                </PermissionGate>
            </div>
            <form v-if="showDiscountForm" class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4" @submit.prevent="addDiscount">
                <input v-model="discountForm.description" placeholder="Description" class="col-span-2 border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <select v-model="discountForm.type" class="border border-slate-300 rounded px-2 py-1.5 text-sm">
                    <option value="fixed">Fixed</option>
                    <option value="percentage">Percentage</option>
                </select>
                <div class="flex gap-2">
                    <input v-model.number="discountForm.value" type="number" step="0.01" placeholder="Value" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" />
                    <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add</button>
                </div>
            </form>
            <ul class="text-sm divide-y divide-slate-100">
                <li v-for="d in bill.discounts" :key="d.id" class="py-1.5 flex justify-between">
                    <span>{{ d.description }} ({{ d.type }}: {{ d.value }})</span>
                    <span class="font-medium">-{{ d.amount }}</span>
                </li>
                <li v-if="!bill.discounts || bill.discounts.length === 0" class="text-slate-400 py-3 text-center">No discounts</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Payments</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500">
                    <tr><th class="py-1">Payment #</th><th>Amount</th><th>Method</th><th>Status</th><th>Refunded</th><th>Paid At</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="p in payments" :key="p.id">
                        <td class="py-1.5">{{ p.payment_number }}</td>
                        <td>{{ p.amount }}</td>
                        <td>{{ p.method }}</td>
                        <td>{{ p.status }}</td>
                        <td>{{ p.refunded_amount }}</td>
                        <td>{{ p.paid_at }}</td>
                        <td class="text-right">
                            <PermissionGate permission="payments.refund">
                                <button type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="refundPayment(p)">Refund</button>
                            </PermissionGate>
                        </td>
                    </tr>
                    <tr v-if="payments.length === 0"><td colspan="7" class="text-slate-400 py-3 text-center">No payments yet</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
