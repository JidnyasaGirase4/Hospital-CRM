<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import Icon from '../../components/Icon.vue';
import { formatMoney } from '../../utils/format';

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
    <PageLoader v-if="loading" />
    <div v-else-if="sale" class="space-y-5">
        <PageHero
            :title="sale.invoice_number"
            :status="sale.status"
            :subtitle="`${sale.patient?.name || 'Walk-in customer'} · Payment: ${sale.payment_status}`"
        >
            <template #actions>
                <PermissionGate permission="pharmacy.dispense">
                    <button type="button" class="btn btn-primary" @click="generateBill"><Icon name="banknote" :size="16" /> Generate Bill</button>
                </PermissionGate>
            </template>
        </PageHero>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <SectionCard title="Items" :count="sale.items?.length ?? 0" flush class="lg:col-span-2">
                <div class="overflow-x-auto px-3 pb-3 pt-2">
                    <table class="table-simple">
                        <thead><tr><th>Medicine</th><th>Batch</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="item in sale.items" :key="item.id">
                                <td class="font-medium text-slate-800">{{ item.medicine?.name }}</td>
                                <td>{{ item.batch_number }}</td>
                                <td class="text-right tabular-nums">{{ item.quantity }}</td>
                                <td class="text-right tabular-nums">{{ formatMoney(item.unit_price) }}</td>
                                <td class="text-right font-semibold tabular-nums text-slate-900">{{ formatMoney(item.total_price) }}</td>
                                <td class="text-right">
                                    <PermissionGate permission="pharmacy.return">
                                        <button type="button" class="btn btn-sm btn-danger-soft" @click="returnItem(item)">Return</button>
                                    </PermissionGate>
                                </td>
                            </tr>
                            <tr v-if="!sale.items?.length"><td colspan="6" class="empty-note">No items</td></tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>

            <section class="card h-fit overflow-hidden">
                <header class="card-header"><h3 class="card-title">Sale Summary</h3></header>
                <dl class="space-y-2.5 p-5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="font-medium tabular-nums text-emerald-600">- {{ formatMoney(sale.discount_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd class="font-medium tabular-nums">{{ formatMoney(sale.tax_amount) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base"><dt class="font-bold text-slate-800">Net Amount</dt><dd class="font-extrabold tabular-nums text-slate-900">{{ formatMoney(sale.net_amount) }}</dd></div>
                </dl>
            </section>
        </div>
    </div>
</template>
