<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();

const patientId = ref(null);
const form = ref({ prescription_id: '', discount_amount: '', tax_amount: '' });
const items = ref([{ medicine_id: null, quantity: 1 }]);
const errors = ref({});
const saving = ref(false);

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

async function fetchMedicines(q) {
    const { data } = await apiClient.get('/medicines', { params: { search: q, per_page: 10 } });
    return data.data.map((m) => ({ id: m.id, label: `${m.name} ${m.strength || ''}`.trim() }));
}

function addItem() {
    items.value.push({ medicine_id: null, quantity: 1 });
}

function removeItem(index) {
    items.value.splice(index, 1);
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const { data } = await apiClient.post('/pharmacy-sales', {
            patient_id: patientId.value || undefined,
            prescription_id: form.value.prescription_id || undefined,
            discount_amount: form.value.discount_amount || undefined,
            tax_amount: form.value.tax_amount || undefined,
            items: items.value,
        });
        router.push({ name: 'pharmacy-sales.show', params: { id: data.data.id } });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">New Pharmacy Sale</h2>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Patient (optional — leave blank for walk-in)</label>
            <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Prescription ID (optional)</label>
            <input v-model.number="form.prescription_id" type="number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-slate-600">Items</h3>
                <button type="button" class="text-brand-600 text-xs hover:underline" @click="addItem">+ Add item</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="flex items-center gap-2 mb-2">
                <div class="flex-1">
                    <SearchSelect v-model="item.medicine_id" :fetcher="fetchMedicines" placeholder="Search medicine…" />
                </div>
                <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="w-24 border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <button v-if="items.length > 1" type="button" class="text-red-500 text-xs" @click="removeItem(index)">Remove</button>
            </div>
            <p v-if="errors.items" class="text-xs text-red-600 mt-1">{{ errors.items[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Discount amount</label>
                <input v-model.number="form.discount_amount" type="number" step="0.01" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tax amount</label>
                <input v-model.number="form.tax_amount" type="number" step="0.01" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'pharmacy-sales.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Complete Sale' }}
            </button>
        </div>
    </form>
</template>
