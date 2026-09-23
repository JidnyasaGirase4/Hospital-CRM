<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

const categories = ref([]);
const newCategoryName = ref('');
const batches = ref([]);
const form = ref({
    medicine_category_id: '',
    name: '',
    generic_name: '',
    manufacturer: '',
    form: '',
    strength: '',
    unit: '',
    reorder_level: '',
    allow_negative_stock: false,
    is_active: true,
});
const errors = ref({});
const saving = ref(false);

async function loadCategories() {
    const { data } = await apiClient.get('/medicine-categories');
    categories.value = data.data;
}

async function addCategory() {
    if (!newCategoryName.value) return;
    const { data } = await apiClient.post('/medicine-categories', { name: newCategoryName.value });
    categories.value.push(data.data);
    form.value.medicine_category_id = data.data.id;
    newCategoryName.value = '';
}

async function loadMedicine() {
    const { data } = await apiClient.get(`/medicines/${route.params.id}`);
    const m = data.data;
    form.value = {
        medicine_category_id: m.category?.id ?? '',
        name: m.name ?? '',
        generic_name: m.generic_name ?? '',
        manufacturer: m.manufacturer ?? '',
        form: m.form ?? '',
        strength: m.strength ?? '',
        unit: m.unit ?? '',
        reorder_level: m.reorder_level ?? '',
        allow_negative_stock: m.allow_negative_stock ?? false,
        is_active: m.is_active,
    };
    batches.value = m.batches || [];
}

onMounted(() => {
    loadCategories();
    if (isEdit.value) loadMedicine();
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        if (isEdit.value) {
            await apiClient.put(`/medicines/${route.params.id}`, form.value);
        } else {
            await apiClient.post('/medicines', form.value);
        }
        router.push({ name: 'medicines.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">{{ isEdit ? 'Edit Medicine' : 'New Medicine' }}</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input v-model="form.name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Generic name</label>
                <input v-model="form.generic_name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                <div class="flex gap-2">
                    <select v-model="form.medicine_category_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                        <option value="">—</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2 mt-1">
                    <input v-model="newCategoryName" placeholder="New category…" class="flex-1 border border-slate-300 rounded px-2 py-1 text-xs" />
                    <button type="button" class="text-xs text-brand-600" @click="addCategory">Add</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Manufacturer</label>
                <input v-model="form.manufacturer" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Form (tablet/syrup…)</label>
                <input v-model="form.form" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Strength</label>
                <input v-model="form.strength" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Unit</label>
                <input v-model="form.unit" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Reorder level</label>
                <input v-model.number="form.reorder_level" type="number" min="0" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.allow_negative_stock" type="checkbox" /> Allow negative stock
            </label>
            <label v-if="isEdit" class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.is_active" type="checkbox" /> Active
            </label>
        </div>

        <div v-if="isEdit && batches.length">
            <h3 class="text-sm font-semibold text-slate-600 mb-2">Batches</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500"><tr><th>Batch #</th><th>Qty</th><th>Expiry</th><th>MRP</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="b in batches" :key="b.id" :class="{ 'text-red-600': b.is_expired }">
                        <td class="py-1">{{ b.batch_number }}</td>
                        <td>{{ b.quantity }}</td>
                        <td>{{ b.expiry_date }}</td>
                        <td>{{ b.mrp }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'medicines.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
