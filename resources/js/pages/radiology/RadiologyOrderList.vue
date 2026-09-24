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
    { key: 'patient', label: 'Patient', format: (r) => r.patient?.name ?? '—' },
    { key: 'test', label: 'Test', format: (r) => r.test?.name ?? '—' },
    { key: 'doctor', label: 'Doctor', format: (r) => r.doctor?.name ?? '—' },
    { key: 'ordered_at', label: 'Ordered At' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/radiology-orders', { params: { page: page.value, per_page: 15 } });
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
        <div class="toolbar">
            <RouterLink :to="{ name: 'radiology.hub' }" class="btn btn-sm btn-soft">← Test Catalog</RouterLink>
            <PermissionGate permission="radiology.create">
                <RouterLink :to="{ name: 'radiology-orders.create' }" class="btn btn-primary">
                    + New Radiology Order
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-patient="{ row }">
                <RouterLink :to="{ name: 'radiology-orders.show', params: { id: row.id } }" class="link">{{ row.patient?.name ?? '—' }}</RouterLink>
            </template>
        </DataTable>
    </div>
</template>
