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
const showForm = ref(false);
const form = ref({ name: '', category: '', unit: '', reorder_level: '' });

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'category', label: 'Category' },
    { key: 'stock_on_hand', label: 'Stock' },
    { key: 'reorder_level', label: 'Reorder Level' },
    { key: 'is_low_stock', label: 'Stock Level', format: (r) => (r.is_low_stock ? 'Low Stock' : 'OK'), badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/inventory-items', { params: { search: search.value || undefined, page: page.value, per_page: 15 } });
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

async function addItem() {
    await apiClient.post('/inventory-items', form.value);
    form.value = { name: '', category: '', unit: '', reorder_level: '' };
    showForm.value = false;
    ui.toast('Inventory item created', 'success');
    load();
}

async function recordTransaction(row) {
    const result = await ui.prompt({
        title: `Adjust Stock — ${row.name}`,
        fields: [
            {
                key: 'type',
                label: 'Transaction type',
                type: 'select',
                required: true,
                options: ['purchase', 'issue', 'return', 'adjustment', 'transfer'],
            },
            { key: 'quantity', label: 'Quantity (negative for issue/deduction)', type: 'number', required: true },
        ],
    });
    if (!result) return;
    try {
        await apiClient.post(`/inventory-items/${row.id}/transactions`, { type: result.type, quantity: Number(result.quantity) });
        ui.toast('Transaction recorded', 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Transaction failed', 'error');
    }
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="toolbar">
            <div class="toolbar-filters">
                <RouterLink :to="{ name: 'inventory.hub' }" class="btn btn-sm btn-soft">← Inventory</RouterLink>
                <input v-model="search" type="text" placeholder="Search items…" class="input w-56" @keyup.enter="onSearch" />
            </div>
            <PermissionGate permission="inventory.create">
                <button type="button" class="btn btn-primary" @click="showForm = !showForm">
                    {{ showForm ? 'Cancel' : '+ New Item' }}
                </button>
            </PermissionGate>
        </div>
        <form v-if="showForm" class="card p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addItem">
            <input v-model="form.name" placeholder="Item name" required class="input input-sm" />
            <input v-model="form.category" placeholder="Category" class="input input-sm" />
            <input v-model="form.unit" placeholder="Unit" class="input input-sm" />
            <div class="flex gap-2">
                <input v-model.number="form.reorder_level" type="number" min="0" placeholder="Reorder level" class="input input-sm" />
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </div>
        </form>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <PermissionGate permission="inventory.update">
                    <button type="button" class="btn btn-sm btn-soft" @click="recordTransaction(row)">Adjust Stock</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
