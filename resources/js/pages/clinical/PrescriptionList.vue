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
                <RouterLink :to="{ name: 'prescriptions.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + New Prescription
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-patient="{ row }">
                <RouterLink :to="{ name: 'prescriptions.show', params: { id: row.id } }" class="text-brand-600 hover:underline">{{ row.patient?.name ?? '—' }}</RouterLink>
            </template>
            <template #actions="{ row }">
                <PermissionGate v-if="row.status !== 'cancelled'" permission="prescriptions.delete">
                    <button type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="cancel(row)">Cancel</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
