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
        <div class="toolbar">
            <input
                v-model="search"
                type="text"
                placeholder="Search by MRN, name or mobile…"
                class="input w-72"
                @keyup.enter="onSearch"
            />
            <PermissionGate permission="patients.create">
                <RouterLink :to="{ name: 'patients.create' }" class="btn btn-primary">
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
                <RouterLink :to="{ name: 'patients.show', params: { id: row.id } }" class="link">
                    {{ row.mrn }}
                </RouterLink>
            </template>
            <template #actions="{ row }">
                <div class="flex gap-2 justify-end text-xs">
                    <PermissionGate permission="patients.update">
                        <RouterLink :to="{ name: 'patients.edit', params: { id: row.id } }" class="btn btn-sm btn-neutral-soft">
                            Edit
                        </RouterLink>
                    </PermissionGate>
                    <PermissionGate permission="patients.delete">
                        <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
