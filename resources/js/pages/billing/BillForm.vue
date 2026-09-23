<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();

const patientId = ref(null);
const type = ref('opd');
const items = ref([{ category: '', description: '', quantity: 1, unit_price: '', discount_amount: '', tax_amount: '' }]);
const errors = ref({});
const saving = ref(false);

const total = computed(() =>
    items.value.reduce((sum, it) => {
        const qty = Number(it.quantity) || 1;
        const price = Number(it.unit_price) || 0;
        const disc = Number(it.discount_amount) || 0;
        const tax = Number(it.tax_amount) || 0;
        return sum + qty * price - disc + tax;
    }, 0)
);

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

function addItem() {
    items.value.push({ category: '', description: '', quantity: 1, unit_price: '', discount_amount: '', tax_amount: '' });
}

function removeItem(index) {
    items.value.splice(index, 1);
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const { data } = await apiClient.post('/bills', {
            patient_id: patientId.value,
            type: type.value,
            items: items.value.map((it) => ({
                category: it.category || undefined,
                description: it.description,
                quantity: it.quantity || undefined,
                unit_price: it.unit_price,
                discount_amount: it.discount_amount || undefined,
                tax_amount: it.tax_amount || undefined,
            })),
        });
        router.push({ name: 'bills.show', params: { id: data.data.id } });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">New Bill</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Patient</label>
                <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
                <p v-if="errors.patient_id" class="text-xs text-red-600 mt-1">{{ errors.patient_id[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Bill type</label>
                <select v-model="type" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                    <option v-for="t in ['opd', 'ipd', 'pharmacy', 'laboratory', 'radiology', 'ot', 'other']" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-slate-600">Line Items</h3>
                <button type="button" class="text-brand-600 text-xs hover:underline" @click="addItem">+ Add item</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-start mb-2">
                <input v-model="item.category" placeholder="Category" class="col-span-1 border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model="item.description" placeholder="Description" class="col-span-2 border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model.number="item.unit_price" type="number" step="0.01" min="0" placeholder="Unit price" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <div class="flex gap-1">
                    <input v-model.number="item.discount_amount" type="number" step="0.01" min="0" placeholder="Disc." class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" />
                    <button v-if="items.length > 1" type="button" class="text-red-500 text-xs px-1" @click="removeItem(index)">✕</button>
                </div>
            </div>
            <p v-if="errors.items" class="text-xs text-red-600 mt-1">{{ errors.items[0] }}</p>
            <p class="text-right text-sm font-medium text-slate-700 mt-2">Estimated total: {{ total.toFixed(2) }}</p>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'bills.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Create Bill' }}
            </button>
        </div>
    </form>
</template>
