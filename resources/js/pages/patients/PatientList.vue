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
    { key: 'mrn', label: 'MRN' },
    { key: 'full_name', label: 'Name' },
    { key: 'mobile', label: 'Mobile' },
    { key: 'gender', label: 'Gender' },
    { key: 'is_active', label: 'Status', format: (row) => (row.is_active ? 'Active' : 'Inactive') , badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/patients', {
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

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete Patient', message: `Delete patient "${row.full_name}" (${row.mrn})?`, danger: true }))) return;
    await apiClient.delete(`/patients/${row.id}`);
    ui.toast('Patient deleted', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <input
                v-model="search"
                type="text"
                placeholder="Search by MRN, name or mobile…"
                class="w-72 border border-slate-300 rounded px-3 py-2 text-sm"
                @keyup.enter="onSearch"
            />
            <PermissionGate permission="patients.create">
                <RouterLink :to="{ name: 'patients.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + New Patient
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
            <template #cell-mrn="{ row }">
                <RouterLink :to="{ name: 'patients.show', params: { id: row.id } }" class="text-brand-600 hover:underline">
                    {{ row.mrn }}
                </RouterLink>
            </template>
            <template #actions="{ row }">
                <div class="flex gap-2 justify-end text-xs">
                    <PermissionGate permission="patients.update">
                        <RouterLink :to="{ name: 'patients.edit', params: { id: row.id } }" class="inline-flex items-center bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-200 hover:ring-slate-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors">
                            Edit
                        </RouterLink>
                    </PermissionGate>
                    <PermissionGate permission="patients.delete">
                        <button type="button" class="text-red-600 hover:underline" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
