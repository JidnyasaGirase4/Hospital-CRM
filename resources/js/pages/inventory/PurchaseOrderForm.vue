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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Purchase Order</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Supplier</label>
                <select v-model="form.supplier_id" class="input">
                    <option value="">Select…</option>
                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <p v-if="errors.supplier_id" class="field-error">{{ errors.supplier_id[0] }}</p>
            </div>
            <div>
                <label class="label">Order date</label>
                <input v-model="form.order_date" type="date" class="input" />
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="card-title mb-3">Items</h3>
                <button type="button" class="btn btn-sm btn-soft" @click="addItem">+ Add item</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2">
                <select v-model="item.inventory_item_id" class="input input-sm col-span-2">
                    <option value="">Select item…</option>
                    <option v-for="i in inventoryItems" :key="i.id" :value="i.id">{{ i.name }}</option>
                </select>
                <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="input input-sm" />
                <div class="flex gap-1">
                    <input v-model.number="item.unit_cost" type="number" step="0.01" placeholder="Unit cost" class="input input-sm" />
                    <button v-if="items.length > 1" type="button" class="btn btn-sm btn-danger-soft shrink-0 !px-2" title="Remove" aria-label="Remove item" @click="removeItem(index)">✕</button>
                </div>
            </div>
            <p v-if="errors.items" class="field-error">{{ errors.items[0] }}</p>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'purchase-orders.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
