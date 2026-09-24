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
    { key: 'diagnosis_name', label: 'Diagnosis' },
    { key: 'diagnosis_code', label: 'Code', format: (r) => r.diagnosis_code || '—' },
    { key: 'doctor', label: 'Doctor', format: (r) => r.doctor?.name ?? '—' },
    { key: 'diagnosed_at', label: 'Date' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/diagnoses', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete Diagnosis', message: `Delete diagnosis "${row.diagnosis_name}"?`, danger: true }))) return;
    await apiClient.delete(`/diagnoses/${row.id}`);
    ui.toast('Diagnosis deleted', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="diagnoses.create">
                <RouterLink :to="{ name: 'diagnoses.create' }" class="btn btn-primary">
                    + New Diagnosis
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <PermissionGate permission="diagnoses.delete">
                    <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
