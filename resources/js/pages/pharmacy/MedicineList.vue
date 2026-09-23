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
const search = ref('');
const page = ref(1);
const lowStockOnly = ref(false);

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'category', label: 'Category', format: (r) => r.category?.name ?? '—' },
    { key: 'strength', label: 'Strength' },
    { key: 'stock_on_hand', label: 'Stock' },
    { key: 'reorder_level', label: 'Reorder Level' },
    { key: 'is_active', label: 'Status', format: (r) => (r.is_active ? 'Active' : 'Inactive') , badge: true },
];

async function load() {
    loading.value = true;
    try {
        if (lowStockOnly.value) {
            const { data } = await apiClient.get('/medicines/low-stock');
            rows.value = data.data;
            pagination.value = null;
        } else {
            const { data } = await apiClient.get('/medicines', {
                params: { search: search.value || undefined, page: page.value, per_page: 15 },
            });
            rows.value = data.data;
            pagination.value = data.meta?.pagination ?? null;
        }
    } finally {
        loading.value = false;
    }
}

function onSearch() {
    page.value = 1;
    load();
}

function toggleLowStock() {
    lowStockOnly.value = !lowStockOnly.value;
    page.value = 1;
    load();
}

async function deactivate(row) {
    if (!(await ui.confirm({ title: 'Deactivate Medicine', message: `Deactivate "${row.name}"? It stays on record but won't be offered for new prescriptions or sales.`, danger: true }))) return;
    await apiClient.delete(`/medicines/${row.id}`);
    ui.toast('Medicine deactivated', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search medicines…"
                    class="w-64 border border-slate-300 rounded px-3 py-2 text-sm"
                    @keyup.enter="onSearch"
                />
                <button type="button" class="text-sm" :class="lowStockOnly ? 'text-red-600 font-medium' : 'text-slate-500'" @click="toggleLowStock">
                    {{ lowStockOnly ? 'Showing low stock only ✕' : 'Show low stock only' }}
                </button>
            </div>
            <PermissionGate permission="pharmacy.create">
                <RouterLink :to="{ name: 'medicines.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + New Medicine
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-name="{ row }">
                <RouterLink :to="{ name: 'medicines.edit', params: { id: row.id } }" class="text-brand-600 hover:underline">{{ row.name }}</RouterLink>
            </template>
            <template #actions="{ row }">
                <PermissionGate v-if="row.is_active" permission="pharmacy.delete">
                    <button type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="deactivate(row)">Deactivate</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
