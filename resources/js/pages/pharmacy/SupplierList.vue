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

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'contact_person', label: 'Contact' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'is_active', label: 'Status', format: (r) => (r.is_active ? 'Active' : 'Inactive') , badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/suppliers', { params: { search: search.value || undefined, page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

function onSearch() {
    page.value = 1;
    load();
}

async function deactivate(row) {
    if (!(await ui.confirm({ title: 'Deactivate Supplier', message: `Deactivate supplier "${row.name}"? It stays on record but won't be offered for new purchases.`, danger: true }))) return;
    await apiClient.delete(`/suppliers/${row.id}`);
    ui.toast('Supplier deactivated', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <input v-model="search" type="text" placeholder="Search suppliers…" class="w-64 border border-slate-300 rounded px-3 py-2 text-sm" @keyup.enter="onSearch" />
            <PermissionGate permission="suppliers.create">
                <RouterLink :to="{ name: 'suppliers.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + New Supplier
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <div class="flex gap-2 justify-end text-xs">
                    <PermissionGate permission="suppliers.create">
                        <RouterLink :to="{ name: 'suppliers.edit', params: { id: row.id } }" class="inline-flex items-center bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-200 hover:ring-slate-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors">Edit</RouterLink>
                    </PermissionGate>
                    <PermissionGate v-if="row.is_active" permission="suppliers.delete">
                        <button type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="deactivate(row)">Deactivate</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
