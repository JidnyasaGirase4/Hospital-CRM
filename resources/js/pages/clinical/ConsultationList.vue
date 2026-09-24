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
    { key: 'chief_complaint', label: 'Chief Complaint' },
    { key: 'status', label: 'Status', badge: true },
    { key: 'created_at', label: 'Date' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/consultations', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function complete(row) {
    try {
        await apiClient.patch(`/consultations/${row.id}/complete`);
        ui.toast('Consultation marked completed', 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Action failed', 'error');
    }
}

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete Consultation', message: `Delete this consultation for ${row.patient?.name ?? 'this patient'}?`, danger: true }))) return;
    await apiClient.delete(`/consultations/${row.id}`);
    ui.toast('Consultation deleted', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="consultations.create">
                <RouterLink :to="{ name: 'consultations.create' }" class="btn btn-primary">
                    + New Consultation
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <div class="flex gap-2 justify-end">
                    <PermissionGate permission="consultations.update">
                        <button v-if="row.status !== 'completed'" type="button" class="btn btn-sm btn-success-soft" @click="complete(row)">Complete</button>
                    </PermissionGate>
                    <PermissionGate permission="consultations.delete">
                        <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
