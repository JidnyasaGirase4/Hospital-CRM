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
    { key: 'name', label: 'Name' },
    { key: 'slug', label: 'Slug' },
    { key: 'permissions', label: 'Permissions', format: (r) => `${(r.permissions || []).length} granted` },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/roles', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete Role', message: `Delete role "${row.name}"?`, danger: true }))) return;
    try {
        await apiClient.delete(`/roles/${row.id}`);
        ui.toast('Role deleted', 'success');
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
            <RouterLink :to="{ name: 'users.index' }" class="text-sm text-brand-600 hover:underline">← Back to Users</RouterLink>
            <PermissionGate permission="roles.create">
                <RouterLink :to="{ name: 'roles.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + New Role
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
                <div class="flex gap-2 justify-end text-xs">
                    <PermissionGate permission="roles.update">
                        <RouterLink :to="{ name: 'roles.edit', params: { id: row.id } }" class="inline-flex items-center bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-200 hover:ring-slate-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors">Edit</RouterLink>
                    </PermissionGate>
                    <PermissionGate v-if="!row.is_system" permission="roles.delete">
                        <button type="button" class="text-red-600 hover:underline" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
