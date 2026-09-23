<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '../../api/client';
import DataTable from '../../components/DataTable.vue';

const rows = ref([]);
const pagination = ref(null);
const loading = ref(false);
const page = ref(1);
const action = ref('');

const columns = [
    { key: 'created_at', label: 'When' },
    { key: 'user', label: 'User', format: (r) => r.user?.name ?? 'System' },
    { key: 'action', label: 'Action' },
    { key: 'auditable_type', label: 'Entity', format: (r) => (r.auditable_type ? r.auditable_type.split('\\').pop() : '—') },
    { key: 'auditable_id', label: 'Entity ID' },
    { key: 'ip_address', label: 'IP' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/audit-logs', { params: { action: action.value || undefined, page: page.value, per_page: 25 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

function onFilterChange() {
    page.value = 1;
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <input v-model="action" type="text" placeholder="Filter by action…" class="w-64 border border-slate-300 rounded px-3 py-2 text-sm" @keyup.enter="onFilterChange" />
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }" />
    </div>
</template>
