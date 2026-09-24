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
    { key: 'scheduled_at', label: 'When' },
    { key: 'patient', label: 'Patient', format: (r) => r.patient?.name ?? '—' },
    { key: 'doctor', label: 'Doctor', format: (r) => r.doctor?.name ?? '—' },
    { key: 'type', label: 'Type' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/appointments', { params: { page: page.value, per_page: 15 } });
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

function checkIn(row) {
    runAction(() => apiClient.patch(`/appointments/${row.id}/check-in`), 'Patient checked in');
}

async function cancel(row) {
    const result = await ui.prompt({
        title: 'Cancel Appointment',
        fields: [{ key: 'reason', label: 'Cancellation reason (optional)', type: 'text' }],
    });
    if (result === null) return;
    runAction(() => apiClient.patch(`/appointments/${row.id}/cancel`, { reason: result.reason || undefined }), 'Appointment cancelled');
}

async function reschedule(row) {
    const result = await ui.prompt({
        title: 'Reschedule Appointment',
        fields: [{ key: 'scheduled_at', label: 'New date/time', type: 'datetime-local', required: true }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/appointments/${row.id}/reschedule`, { scheduled_at: result.scheduled_at }), 'Appointment rescheduled');
}

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete Appointment', message: `Delete this appointment for ${row.patient?.name ?? 'this patient'}?`, danger: true }))) return;
    runAction(() => apiClient.delete(`/appointments/${row.id}`), 'Appointment deleted');
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="appointments.create">
                <RouterLink :to="{ name: 'appointments.create' }" class="btn btn-primary">
                    + New Appointment
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable
            :columns="columns"
            :rows="rows"
            :loading="loading"
            :pagination="pagination"
            @page-change="(p) => { page = p; }"
        >
            <template #actions="{ row }">
                <div class="flex gap-2 justify-end">
                    <button v-if="row.status !== 'cancelled'" type="button" class="btn btn-sm btn-success-soft" @click="checkIn(row)">Check-in</button>
                    <button v-if="row.status !== 'cancelled'" type="button" class="btn btn-sm btn-soft" @click="reschedule(row)">Reschedule</button>
                    <button v-if="row.status !== 'cancelled'" type="button" class="btn btn-sm btn-danger-soft" @click="cancel(row)">Cancel</button>
                    <PermissionGate permission="appointments.delete">
                        <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
