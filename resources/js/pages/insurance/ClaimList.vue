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
const status = ref('');

const columns = [
    { key: 'claim_number', label: 'Claim #' },
    { key: 'policy', label: 'Policy', format: (r) => r.policy?.policy_number ?? '—' },
    { key: 'requested_amount', label: 'Requested' },
    { key: 'approved_amount', label: 'Approved' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/insurance-claims', { params: { status: status.value || undefined, page: page.value, per_page: 15 } });
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

async function approve(row) {
    const result = await ui.prompt({
        title: 'Approve Claim',
        fields: [{ key: 'approved_amount', label: 'Approved amount', type: 'number', step: '0.01', value: row.requested_amount, required: true }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/insurance-claims/${row.id}/approve`, { approved_amount: Number(result.approved_amount) }), 'Claim approved');
}

async function reject(row) {
    const result = await ui.prompt({
        title: 'Reject Claim',
        fields: [{ key: 'rejection_reason', label: 'Rejection reason', type: 'textarea', required: true }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/insurance-claims/${row.id}/reject`, { rejection_reason: result.rejection_reason }), 'Claim rejected');
}

async function settle(row) {
    const result = await ui.prompt({
        title: 'Settle Claim',
        fields: [{ key: 'settled_amount', label: 'Settled amount', type: 'number', step: '0.01', value: row.approved_amount || row.requested_amount, required: true }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/insurance-claims/${row.id}/settle`, { settled_amount: Number(result.settled_amount) }), 'Claim settled');
}

onMounted(load);
watch([page, status], load);
</script>

<template>
    <div class="space-y-4">
        <div class="toolbar">
            <div class="toolbar-filters">
                <RouterLink :to="{ name: 'insurance-policies.index' }" class="btn btn-sm btn-soft">← Back to Policies</RouterLink>
                <select v-model="status" class="input w-auto">
                    <option value="">All statuses</option>
                    <option v-for="s in ['submitted', 'approved', 'rejected', 'settled']" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <PermissionGate permission="insurance.create">
                <RouterLink :to="{ name: 'insurance-claims.create' }" class="btn btn-primary">
                    + Submit Claim
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <PermissionGate permission="insurance.approve-claim">
                    <div class="flex gap-2 justify-end">
                        <button v-if="row.status === 'submitted'" type="button" class="btn btn-sm btn-success-soft" @click="approve(row)">Approve</button>
                        <button v-if="row.status === 'submitted'" type="button" class="btn btn-sm btn-danger-soft" @click="reject(row)">Reject</button>
                        <button v-if="row.status === 'approved'" type="button" class="btn btn-sm btn-soft" @click="settle(row)">Settle</button>
                    </div>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
