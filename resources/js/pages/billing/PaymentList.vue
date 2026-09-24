<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '../../api/client';
import DataTable from '../../components/DataTable.vue';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const rows = ref([]);
const pagination = ref(null);
const loading = ref(false);
const page = ref(1);

const columns = [
    { key: 'payment_number', label: 'Payment #' },
    { key: 'bill_id', label: 'Bill ID' },
    { key: 'amount', label: 'Amount' },
    { key: 'method', label: 'Method' },
    { key: 'status', label: 'Status', badge: true },
    { key: 'refunded_amount', label: 'Refunded' },
    { key: 'paid_at', label: 'Paid At' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/payments', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function refundPayment(row) {
    const result = await ui.prompt({
        title: 'Refund Payment',
        fields: [
            { key: 'amount', label: 'Refund amount', type: 'number', step: '0.01', value: row.amount, required: true },
            { key: 'reason', label: 'Reason (optional)', type: 'text' },
        ],
    });
    if (!result) return;
    try {
        await apiClient.post(`/payments/${row.id}/refund`, { amount: Number(result.amount), reason: result.reason || undefined });
        ui.toast('Refund processed', 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Refund failed', 'error');
    }
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="toolbar">
            <RouterLink :to="{ name: 'bills.index' }" class="btn btn-sm btn-soft">← Back to Bills</RouterLink>
            <PermissionGate permission="payments.create">
                <RouterLink :to="{ name: 'payments.create' }" class="btn btn-primary">
                    + Record Payment
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable
            :columns="columns"
            :rows="rows"
            :loading="loading"
            :pagination="pagination"
            @page-change="(p) => { page = p; }"
        >
            <template #actions="{ row }">
                <PermissionGate permission="payments.refund">
                    <button v-if="row.status !== 'refunded'" type="button" class="btn btn-sm btn-danger-soft" @click="refundPayment(row)">Refund</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
