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
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <RouterLink :to="{ name: 'inventory.hub' }" class="text-sm text-brand-600 hover:underline">← Inventory</RouterLink>
                <input v-model="search" type="text" placeholder="Search items…" class="w-56 border border-slate-300 rounded px-3 py-2 text-sm" @keyup.enter="onSearch" />
            </div>
            <PermissionGate permission="inventory.create">
                <button type="button" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all" @click="showForm = !showForm">
                    {{ showForm ? 'Cancel' : '+ New Item' }}
                </button>
            </PermissionGate>
        </div>
        <form v-if="showForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addItem">
            <input v-model="form.name" placeholder="Item name" required class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <input v-model="form.category" placeholder="Category" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <input v-model="form.unit" placeholder="Unit" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <div class="flex gap-2">
                <input v-model.number="form.reorder_level" type="number" min="0" placeholder="Reorder level" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add</button>
            </div>
        </form>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <PermissionGate permission="inventory.update">
                    <button type="button" class="inline-flex items-center bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-100 hover:ring-brand-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="recordTransaction(row)">Adjust Stock</button>
                </PermissionGate>
            </template>
        </DataTable>
    </div>
</template>
