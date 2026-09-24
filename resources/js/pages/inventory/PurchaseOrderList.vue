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
    { key: 'po_number', label: 'PO #' },
    { key: 'supplier', label: 'Supplier', format: (r) => r.supplier?.name ?? '—' },
    { key: 'order_date', label: 'Order Date' },
    { key: 'total_amount', label: 'Total' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/purchase-orders', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function runAction(fn, successMessage) {
    try {
        await fn();
        if (successMessage) ui.toast(successMessage, 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Action failed', 'error');
    }
}

function markOrdered(row) {
    runAction(() => apiClient.patch(`/purchase-orders/${row.id}/mark-ordered`), 'Marked as ordered');
}
function receive(row) {
    runAction(() => apiClient.patch(`/purchase-orders/${row.id}/receive`), 'Purchase order received — stock updated');
}
async function cancel(row) {
    if (!(await ui.confirm({ title: 'Cancel Purchase Order', message: 'Cancel this purchase order?', danger: true }))) return;
    runAction(() => apiClient.patch(`/purchase-orders/${row.id}/cancel`), 'Purchase order cancelled');
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="toolbar">
            <RouterLink :to="{ name: 'inventory.hub' }" class="btn btn-sm btn-soft">← Inventory</RouterLink>
            <PermissionGate permission="inventory.create">
                <RouterLink :to="{ name: 'purchase-orders.create' }" class="btn btn-primary">
                    + New Purchase Order
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <PermissionGate permission="inventory.update">
                    <div class="flex gap-2 justify-end text-xs">
                        <button v-if="row.status === 'draft'" type="button" class="btn btn-sm btn-soft" @click="markOrdered(row)">Mark Ordered</button>
                        <button v-if="row.status === 'ordered'" type="button" class="btn btn-sm btn-success-soft" @click="receive(row)">Receive</button>
                        <button v-if="row.status === 'draft' || row.status === 'ordered'" type="button" class="btn btn-sm btn-danger-soft" @click="cancel(row)">Cancel</button>
                    </div>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
