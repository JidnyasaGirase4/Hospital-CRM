<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();

const suppliers = ref([]);
const form = ref({ supplier_id: '', invoice_number: '', purchase_date: new Date().toISOString().slice(0, 10) });
const items = ref([{ medicine_id: null, batch_number: '', quantity: 1, unit_cost: '', selling_price: '', mrp: '', expiry_date: '' }]);
const errors = ref({});
const saving = ref(false);

async function fetchMedicines(q) {
    const { data } = await apiClient.get('/medicines', { params: { search: q, per_page: 10 } });
    return data.data.map((m) => ({ id: m.id, label: `${m.name} ${m.strength || ''}`.trim() }));
}

onMounted(async () => {
    const { data } = await apiClient.get('/suppliers', { params: { per_page: 100 } });
    suppliers.value = data.data;
});

function addItem() {
    items.value.push({ medicine_id: null, batch_number: '', quantity: 1, unit_cost: '', selling_price: '', mrp: '', expiry_date: '' });
}

function removeItem(index) {
    items.value.splice(index, 1);
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        await apiClient.post('/pharmacy-purchases', { ...form.value, items: items.value });
        router.push({ name: 'pharmacy-purchases.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Pharmacy Purchase</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="label">Supplier</label>
                <select v-model="form.supplier_id" class="input">
                    <option value="">Select…</option>
                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <p v-if="errors.supplier_id" class="field-error">{{ errors.supplier_id[0] }}</p>
            </div>
            <div>
                <label class="label">Invoice number</label>
                <input v-model="form.invoice_number" class="input" />
            </div>
            <div>
                <label class="label">Purchase date</label>
                <input v-model="form.purchase_date" type="date" class="input" />
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="card-title mb-3">Items</h3>
                <button type="button" class="btn btn-sm btn-soft" @click="addItem">+ Add item</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="border border-slate-200 rounded p-3 mb-2 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex-1 mr-2">
                        <SearchSelect v-model="item.medicine_id" :fetcher="fetchMedicines" placeholder="Search medicine…" />
                    </div>
                    <button v-if="items.length > 1" type="button" class="btn btn-sm btn-danger-soft" @click="removeItem(index)">Remove</button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-6 gap-2">
                    <input v-model="item.batch_number" placeholder="Batch #" class="input input-sm" />
                    <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="input input-sm" />
                    <input v-model.number="item.unit_cost" type="number" step="0.01" placeholder="Unit cost" class="input input-sm" />
                    <input v-model.number="item.selling_price" type="number" step="0.01" placeholder="Selling price" class="input input-sm" />
                    <input v-model.number="item.mrp" type="number" step="0.01" placeholder="MRP" class="input input-sm" />
                    <input v-model="item.expiry_date" type="date" class="input input-sm" />
                </div>
            </div>
            <p v-if="errors.items" class="field-error">{{ errors.items[0] }}</p>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'pharmacy-purchases.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
