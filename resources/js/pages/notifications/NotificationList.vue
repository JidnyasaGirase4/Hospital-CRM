<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '../../api/client';
import DataTable from '../../components/DataTable.vue';

const rows = ref([]);
const pagination = ref(null);
const loading = ref(false);
const page = ref(1);

const columns = [
    { key: 'data', label: 'Message', format: (r) => r.data?.message || JSON.stringify(r.data) },
    { key: 'read_at', label: 'Read?', format: (r) => (r.read_at ? 'Read' : 'Unread') },
    { key: 'created_at', label: 'Date' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/notifications', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function markRead(row) {
    await apiClient.patch(`/notifications/${row.id}/read`);
    load();
}

async function markAllRead() {
    await apiClient.patch('/notifications/read-all');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <button type="button" class="btn btn-sm btn-soft" @click="markAllRead">Mark all as read</button>
        </div>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <button v-if="!row.read_at" type="button" class="btn btn-sm btn-soft" @click="markRead(row)">Mark Read</button>
            </template>
        </DataTable>
    </div>
</template>
