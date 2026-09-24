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
        <div class="toolbar">
            <RouterLink :to="{ name: 'users.index' }" class="btn btn-sm btn-soft">← Back to Users</RouterLink>
            <PermissionGate permission="roles.create">
                <RouterLink :to="{ name: 'roles.create' }" class="btn btn-primary">
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
                        <RouterLink :to="{ name: 'roles.edit', params: { id: row.id } }" class="btn btn-sm btn-neutral-soft">Edit</RouterLink>
                    </PermissionGate>
                    <PermissionGate v-if="!row.is_system" permission="roles.delete">
                        <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
