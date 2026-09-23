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
    { key: 'triage_level', label: 'Triage', format: (r) => r.triage_level || '—' },
    { key: 'chief_complaint', label: 'Complaint' },
    { key: 'status', label: 'Status', badge: true },
    { key: 'registered_at', label: 'Registered' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/emergency-visits', { params: { page: page.value, per_page: 15 } });
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

async function triage(row) {
    const result = await ui.prompt({
        title: 'Triage Patient',
        fields: [{
            key: 'triage_level',
            label: 'Triage level',
            type: 'select',
            required: true,
            options: ['critical', 'urgent', 'semi-urgent', 'non-urgent'],
        }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/emergency-visits/${row.id}/triage`, { triage_level: result.triage_level }), 'Patient triaged');
}

function startTreatment(row) {
    runAction(() => apiClient.patch(`/emergency-visits/${row.id}/start-treatment`), 'Treatment started');
}

async function discharge(row) {
    if (!(await ui.confirm({ title: 'Discharge Patient', message: 'Discharge this patient from emergency?' }))) return;
    runAction(() => apiClient.patch(`/emergency-visits/${row.id}/discharge`), 'Patient discharged');
}

async function refer(row) {
    const result = await ui.prompt({
        title: 'Refer Patient',
        fields: [{ key: 'referred_to', label: 'Refer to (hospital/department)', type: 'text', required: true }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/emergency-visits/${row.id}/refer`, { referred_to: result.referred_to }), 'Patient referred');
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="emergency.create">
                <RouterLink :to="{ name: 'emergency-visits.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + Register Visit
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-patient="{ row }">
                <RouterLink :to="{ name: 'emergency-visits.show', params: { id: row.id } }" class="text-brand-600 hover:underline">{{ row.patient?.name ?? '—' }}</RouterLink>
            </template>
            <template #actions="{ row }">
                <PermissionGate permission="emergency.update">
                    <div class="flex gap-2 justify-end text-xs">
                        <button v-if="row.status === 'registered'" type="button" class="text-amber-600 hover:underline" @click="triage(row)">Triage</button>
                        <button v-if="row.status === 'triaged'" type="button" class="text-brand-600 hover:underline" @click="startTreatment(row)">Start Treatment</button>
                        <template v-if="row.status === 'in-treatment'">
                            <button type="button" class="text-emerald-600 hover:underline" @click="discharge(row)">Discharge</button>
                            <button type="button" class="text-purple-600 hover:underline" @click="refer(row)">Refer</button>
                        </template>
                    </div>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
