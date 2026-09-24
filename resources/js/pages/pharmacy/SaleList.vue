<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '../../api/client';
import DataTable from '../../components/DataTable.vue';
import PermissionGate from '../../components/PermissionGate.vue';

const rows = ref([]);
const pagination = ref(null);
const loading = ref(false);
const page = ref(1);

const columns = [
    { key: 'invoice_number', label: 'Invoice #' },
    { key: 'patient', label: 'Patient', format: (r) => r.patient?.name ?? 'Walk-in' },
    { key: 'net_amount', label: 'Net Amount' },
    { key: 'payment_status', label: 'Payment', badge: true },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/pharmacy-sales', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="pharmacy.dispense">
                <RouterLink :to="{ name: 'pharmacy-sales.create' }" class="btn btn-primary">
                    + New Sale
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-invoice_number="{ row }">
                <RouterLink :to="{ name: 'pharmacy-sales.show', params: { id: row.id } }" class="link">{{ row.invoice_number }}</RouterLink>
            </template>
        </DataTable>
    </div>
</template>
