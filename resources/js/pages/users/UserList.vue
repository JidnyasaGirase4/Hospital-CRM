<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '../../api/client';
import DataTable from '../../components/DataTable.vue';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import { useAuthStore } from '../../stores/auth';

const ui = useUiStore();
const auth = useAuthStore();
const rows = ref([]);
const pagination = ref(null);
const loading = ref(false);
const search = ref('');
const page = ref(1);

const columns = [
    { key: 'employee_code', label: 'Employee Code' },
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'roles', label: 'Roles', format: (r) => (r.roles || []).map((role) => role.name).join(', ') || '—' },
    { key: 'is_active', label: 'Status', format: (r) => (r.is_active ? 'Active' : 'Inactive') , badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/users', {
            params: { search: search.value || undefined, page: page.value, per_page: 15 },
        });
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

async function toggleActive(row) {
    const action = row.is_active ? 'deactivate' : 'activate';
    if (!(await ui.confirm({ title: 'Confirm', message: `${row.is_active ? 'Deactivate' : 'Activate'} ${row.name}?`, danger: row.is_active }))) return;
    try {
        await apiClient.patch(`/users/${row.id}/${action}`);
        ui.toast(`User ${action}d`, 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Action failed', 'error');
    }
}

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete User', message: `Delete user "${row.name}"? This cannot be undone.`, danger: true }))) return;
    try {
        await apiClient.delete(`/users/${row.id}`);
        ui.toast('User deleted', 'success');
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
            <div class="toolbar-filters">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or email…"
                    class="input w-64"
                    @keyup.enter="onSearch"
                />
                <RouterLink :to="{ name: 'roles.index' }" class="btn btn-sm btn-soft">Manage Roles →</RouterLink>
            </div>
            <PermissionGate permission="users.create">
                <RouterLink :to="{ name: 'users.create' }" class="btn btn-primary">
                    + New User
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
                    <PermissionGate permission="users.update">
                        <RouterLink :to="{ name: 'users.edit', params: { id: row.id } }" class="btn btn-sm btn-neutral-soft">Edit</RouterLink>
                        <button type="button" class="btn btn-sm" :class="row.is_active ? 'btn-warn-soft' : 'btn-success-soft'" @click="toggleActive(row)">
                            {{ row.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </PermissionGate>
                    <PermissionGate v-if="row.id !== auth.user?.id" permission="users.delete">
                        <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
