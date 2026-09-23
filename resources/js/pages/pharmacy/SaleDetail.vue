<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const route = useRoute();
const sale = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/pharmacy-sales/${route.params.id}`);
    sale.value = data.data;
    loading.value = false;
}

async function returnItem(item) {
    const result = await ui.prompt({
        title: `Return Item — ${item.medicine?.name}`,
        fields: [
            { key: 'quantity', label: `Return quantity (max ${item.quantity})`, type: 'number', value: item.quantity, required: true },
            { key: 'reason', label: 'Reason (optional)', type: 'text' },
        ],
    });
    if (!result) return;
    try {
        await apiClient.post(`/pharmacy-sales/${sale.value.id}/returns`, {
            pharmacy_sale_item_id: item.id,
            quantity: Number(result.quantity),
            reason: result.reason || undefined,
        });
        ui.toast('Return processed', 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Return failed', 'error');
    }
}

async function generateBill() {
    try {
        await apiClient.post(`/pharmacy-sales/${sale.value.id}/bill`);
        ui.toast('Bill generated — check the Billing module', 'success');
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Failed to generate bill', 'error');
    }
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="sale" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ sale.invoice_number }}</h2>
                <p class="text-sm text-slate-500">{{ sale.patient?.name || 'Walk-in' }} · {{ sale.status }} · {{ sale.payment_status }}</p>
            </div>
            <PermissionGate permission="billing.create">
                <button type="button" class="text-sm text-brand-600 hover:underline" @click="generateBill">Generate Bill</button>
            </PermissionGate>
        </div>

        <table class="w-full text-sm">
            <thead class="text-left text-slate-500"><tr><th class="py-1">Medicine</th><th>Batch</th><th>Qty</th><th>Unit Price</th><th>Total</th><th class="text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="item in sale.items" :key="item.id">
                    <td class="py-1.5">{{ item.medicine?.name }}</td>
                    <td>{{ item.batch_number }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ item.unit_price }}</td>
                    <td>{{ item.total_price }}</td>
                    <td class="text-right">
                        <PermissionGate permission="pharmacy.return">
                            <button type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="returnItem(item)">Return</button>
                        </PermissionGate>
                    </td>
                </tr>
            </tbody>
        </table>

        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div><dt class="text-slate-400">Discount</dt><dd>{{ sale.discount_amount }}</dd></div>
            <div><dt class="text-slate-400">Tax</dt><dd>{{ sale.tax_amount }}</dd></div>
            <div><dt class="text-slate-400">Net Amount</dt><dd class="font-semibold">{{ sale.net_amount }}</dd></div>
        </dl>
    </div>
</template>
