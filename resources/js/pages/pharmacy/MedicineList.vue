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
        <div class="toolbar">
            <div class="toolbar-filters">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search medicines…"
                    class="input w-64"
                    @keyup.enter="onSearch"
                />
                <button type="button" class="text-sm" :class="lowStockOnly ? 'text-red-600 font-medium' : 'text-slate-500'" @click="toggleLowStock">
                    {{ lowStockOnly ? 'Showing low stock only ✕' : 'Show low stock only' }}
                </button>
            </div>
            <PermissionGate permission="pharmacy.create">
                <RouterLink :to="{ name: 'medicines.create' }" class="btn btn-primary">
                    + New Medicine
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #cell-name="{ row }">
                <RouterLink :to="{ name: 'medicines.edit', params: { id: row.id } }" class="link">{{ row.name }}</RouterLink>
            </template>
            <template #actions="{ row }">
                <PermissionGate v-if="row.is_active" permission="pharmacy.delete">
                    <button type="button" class="btn btn-sm btn-danger-soft" @click="deactivate(row)">Deactivate</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
