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
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <RouterLink :to="{ name: 'insurance-policies.index' }" class="text-sm text-brand-600 hover:underline">← Back to Policies</RouterLink>
                <select v-model="status" class="border border-slate-300 rounded px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    <option v-for="s in ['submitted', 'approved', 'rejected', 'settled']" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <PermissionGate permission="insurance.create">
                <RouterLink :to="{ name: 'insurance-claims.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + Submit Claim
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <PermissionGate permission="insurance.approve-claim">
                    <div class="flex gap-2 justify-end">
                        <button v-if="row.status === 'submitted'" type="button" class="inline-flex items-center bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100 hover:ring-emerald-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="approve(row)">Approve</button>
                        <button v-if="row.status === 'submitted'" type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="reject(row)">Reject</button>
                        <button v-if="row.status === 'approved'" type="button" class="inline-flex items-center bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-100 hover:ring-brand-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="settle(row)">Settle</button>
                    </div>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
