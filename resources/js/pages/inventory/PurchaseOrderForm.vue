<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';

const router = useRouter();
const suppliers = ref([]);
const items = ref([{ inventory_item_id: '', quantity: 1, unit_cost: '' }]);
const inventoryItems = ref([]);

const form = ref({ supplier_id: '', order_date: new Date().toISOString().slice(0, 10) });
const errors = ref({});
const saving = ref(false);

onMounted(async () => {
    const [suppliersRes, itemsRes] = await Promise.all([
        apiClient.get('/suppliers', { params: { per_page: 100 } }),
        apiClient.get('/inventory-items', { params: { per_page: 100 } }),
    ]);
    suppliers.value = suppliersRes.data.data;
    inventoryItems.value = itemsRes.data.data;
});

function addItem() {
    items.value.push({ inventory_item_id: '', quantity: 1, unit_cost: '' });
}
function removeItem(index) {
    items.value.splice(index, 1);
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        await apiClient.post('/purchase-orders', { ...form.value, items: items.value });
        router.push({ name: 'purchase-orders.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">New Purchase Order</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Supplier</label>
                <select v-model="form.supplier_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                    <option value="">Select…</option>
                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <p v-if="errors.supplier_id" class="text-xs text-red-600 mt-1">{{ errors.supplier_id[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Order date</label>
                <input v-model="form.order_date" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-slate-600">Items</h3>
                <button type="button" class="text-brand-600 text-xs hover:underline" @click="addItem">+ Add item</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2">
                <select v-model="item.inventory_item_id" class="col-span-2 border border-slate-300 rounded px-2 py-1.5 text-sm">
                    <option value="">Select item…</option>
                    <option v-for="i in inventoryItems" :key="i.id" :value="i.id">{{ i.name }}</option>
                </select>
                <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <div class="flex gap-1">
                    <input v-model.number="item.unit_cost" type="number" step="0.01" placeholder="Unit cost" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" />
                    <button v-if="items.length > 1" type="button" class="text-red-500 text-xs px-1" @click="removeItem(index)">✕</button>
                </div>
            </div>
            <p v-if="errors.items" class="text-xs text-red-600 mt-1">{{ errors.items[0] }}</p>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'purchase-orders.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
