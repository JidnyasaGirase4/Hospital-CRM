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
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or email…"
                    class="w-64 border border-slate-300 rounded px-3 py-2 text-sm"
                    @keyup.enter="onSearch"
                />
                <RouterLink :to="{ name: 'roles.index' }" class="text-sm text-brand-600 hover:underline">Manage Roles →</RouterLink>
            </div>
            <PermissionGate permission="users.create">
                <RouterLink :to="{ name: 'users.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
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
                        <RouterLink :to="{ name: 'users.edit', params: { id: row.id } }" class="inline-flex items-center bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-200 hover:ring-slate-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors">Edit</RouterLink>
                        <button type="button" class="text-xs" :class="row.is_active ? 'text-red-600' : 'text-emerald-600'" @click="toggleActive(row)">
                            {{ row.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </PermissionGate>
                    <PermissionGate v-if="row.id !== auth.user?.id" permission="users.delete">
                        <button type="button" class="inline-flex items-center bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 hover:ring-red-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
