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
        <div class="toolbar">
            <input v-model="search" type="text" placeholder="Search suppliers…" class="input w-64" @keyup.enter="onSearch" />
            <PermissionGate permission="suppliers.create">
                <RouterLink :to="{ name: 'suppliers.create' }" class="btn btn-primary">
                    + New Supplier
                </RouterLink>
            </PermissionGate>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <div class="flex gap-2 justify-end text-xs">
                    <PermissionGate permission="suppliers.create">
                        <RouterLink :to="{ name: 'suppliers.edit', params: { id: row.id } }" class="btn btn-sm btn-neutral-soft">Edit</RouterLink>
                    </PermissionGate>
                    <PermissionGate v-if="row.is_active" permission="suppliers.delete">
                        <button type="button" class="btn btn-sm btn-danger-soft" @click="deactivate(row)">Deactivate</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
