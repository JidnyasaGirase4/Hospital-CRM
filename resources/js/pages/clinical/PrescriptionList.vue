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
    { key: 'patient', label: 'Patient', format: (r) => r.patient?.name ?? '—' },
    { key: 'doctor', label: 'Doctor', format: (r) => r.doctor?.name ?? '—' },
    { key: 'status', label: 'Status', badge: true },
    { key: 'created_at', label: 'Date' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/prescriptions', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function cancel(row) {
    if (!(await ui.confirm({ title: 'Cancel Prescription', message: `Cancel this prescription for ${row.patient?.name ?? 'this patient'}?`, danger: true }))) return;
    await apiClient.delete(`/prescriptions/${row.id}`);
    ui.toast('Prescription cancelled', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="prescriptions.create">
                <RouterLink :to="{ name: 'prescriptions.create' }" class="btn btn-primary">
                    + New Prescription
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-patient="{ row }">
                <RouterLink :to="{ name: 'prescriptions.show', params: { id: row.id } }" class="link">{{ row.patient?.name ?? '—' }}</RouterLink>
            </template>
            <template #actions="{ row }">
                <PermissionGate v-if="row.status !== 'cancelled'" permission="prescriptions.delete">
                    <button type="button" class="btn btn-sm btn-danger-soft" @click="cancel(row)">Cancel</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
