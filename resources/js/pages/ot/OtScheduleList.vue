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
    { key: 'surgeon', label: 'Surgeon', format: (r) => r.surgeon?.name ?? '—' },
    { key: 'procedure_name', label: 'Procedure' },
    { key: 'scheduled_at', label: 'Scheduled' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/ot-schedules', { params: { page: page.value, per_page: 15 } });
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

function start(row) {
    runAction(() => apiClient.patch(`/ot-schedules/${row.id}/start`), 'Surgery started');
}
function complete(row) {
    runAction(() => apiClient.patch(`/ot-schedules/${row.id}/complete`), 'Surgery completed');
}
async function cancel(row) {
    if (!(await ui.confirm({ title: 'Cancel Surgery', message: 'Cancel this surgery?', danger: true }))) return;
    runAction(() => apiClient.patch(`/ot-schedules/${row.id}/cancel`), 'Surgery cancelled');
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="ot.create">
                <RouterLink :to="{ name: 'ot-schedules.create' }" class="btn btn-primary">
                    + Schedule Surgery
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <PermissionGate permission="ot.update">
                    <div class="flex gap-2 justify-end text-xs">
                        <button v-if="row.status === 'scheduled'" type="button" class="btn btn-sm btn-soft" @click="start(row)">Start</button>
                        <button v-if="row.status === 'in-progress'" type="button" class="btn btn-sm btn-success-soft" @click="complete(row)">Complete</button>
                        <button v-if="row.status === 'scheduled'" type="button" class="btn btn-sm btn-danger-soft" @click="cancel(row)">Cancel</button>
                    </div>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
