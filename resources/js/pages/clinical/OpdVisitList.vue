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
    { key: 'visit_date', label: 'Visit Date' },
    { key: 'patient', label: 'Patient', format: (r) => r.patient?.name ?? '—' },
    { key: 'doctor', label: 'Doctor', format: (r) => r.doctor?.name ?? '—' },
    { key: 'diagnosis', label: 'Diagnosis', format: (r) => r.diagnosis || '—' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/opd-visits', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete OPD Visit', message: `Delete this OPD visit for ${row.patient?.name ?? 'this patient'}?`, danger: true }))) return;
    await apiClient.delete(`/opd-visits/${row.id}`);
    ui.toast('OPD visit deleted', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="opd.create">
                <RouterLink :to="{ name: 'opd-visits.create' }" class="btn btn-primary">
                    + New OPD Visit
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-visit_date="{ row }">
                <RouterLink :to="{ name: 'opd-visits.show', params: { id: row.id } }" class="link">{{ row.visit_date }}</RouterLink>
            </template>
            <template #actions="{ row }">
                <PermissionGate permission="opd.delete">
                    <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
