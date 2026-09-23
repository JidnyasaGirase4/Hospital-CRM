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
const type = ref('');
const status = ref('');

const columns = [
    { key: 'bill_number', label: 'Bill #' },
    { key: 'patient', label: 'Patient', format: (r) => r.patient?.name ?? '—' },
    { key: 'type', label: 'Type' },
    { key: 'total_amount', label: 'Total' },
    { key: 'outstanding_amount', label: 'Outstanding' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/bills', {
            params: { type: type.value || undefined, status: status.value || undefined, page: page.value, per_page: 15 },
        });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

function onFilterChange() {
    page.value = 1;
    load();
}

async function cancelBill(row) {
    if (!(await ui.confirm({ title: 'Cancel Bill', message: `Cancel bill ${row.bill_number}?`, confirmLabel: 'Cancel Bill', danger: true }))) return;
    try {
        await apiClient.patch(`/bills/${row.id}/cancel`);
        ui.toast('Bill cancelled', 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Action failed', 'error');
    }
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex gap-3">
                <select v-model="type" class="border border-slate-300 rounded px-3 py-2 text-sm" @change="onFilterChange">
                    <option value="">All types</option>
                    <option v-for="t in ['opd', 'ipd', 'pharmacy', 'laboratory', 'radiology', 'ot', 'other']" :key="t" :value="t">{{ t }}</option>
                </select>
                <select v-model="status" class="border border-slate-300 rounded px-3 py-2 text-sm" @change="onFilterChange">
                    <option value="">All statuses</option>
                    <option v-for="s in ['unpaid', 'partially-paid', 'paid', 'cancelled']" :key="s" :value="s">{{ s }}</option>
                </select>
                <RouterLink :to="{ name: 'payments.index' }" class="text-sm text-brand-600 hover:underline self-center">View Payments →</RouterLink>
            </div>
            <PermissionGate permission="billing.create">
                <RouterLink :to="{ name: 'bills.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + New Bill
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
            <template #cell-bill_number="{ row }">
                <RouterLink :to="{ name: 'bills.show', params: { id: row.id } }" class="text-brand-600 hover:underline">{{ row.bill_number }}</RouterLink>
            </template>
            <template #actions="{ row }">
                <PermissionGate permission="billing.update">
                    <button v-if="row.status !== 'cancelled'" type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="cancelBill(row)">Cancel</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
