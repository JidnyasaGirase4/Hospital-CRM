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
    { key: 'purchase_number', label: 'Purchase #' },
    { key: 'supplier', label: 'Supplier', format: (r) => r.supplier?.name ?? '—' },
    { key: 'purchase_date', label: 'Date' },
    { key: 'total_amount', label: 'Total' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/pharmacy-purchases', { params: { page: page.value, per_page: 15 } });
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
            <PermissionGate permission="pharmacy.purchase">
                <RouterLink :to="{ name: 'pharmacy-purchases.create' }" class="btn btn-primary">
                    + New Purchase
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }" />
    </div>
</template>
