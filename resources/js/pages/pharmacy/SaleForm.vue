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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Pharmacy Sale</h2>

        <div>
            <label class="label">Patient (optional — leave blank for walk-in)</label>
            <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
        </div>
        <div>
            <label class="label">Prescription ID (optional)</label>
            <input v-model.number="form.prescription_id" type="number" class="input" />
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="card-title mb-3">Items</h3>
                <button type="button" class="btn btn-sm btn-soft" @click="addItem">+ Add item</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="flex items-center gap-2 mb-2">
                <div class="flex-1">
                    <SearchSelect v-model="item.medicine_id" :fetcher="fetchMedicines" placeholder="Search medicine…" />
                </div>
                <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="input input-sm w-24" />
                <button v-if="items.length > 1" type="button" class="btn btn-sm btn-danger-soft" @click="removeItem(index)">Remove</button>
            </div>
            <p v-if="errors.items" class="field-error">{{ errors.items[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Discount amount</label>
                <input v-model.number="form.discount_amount" type="number" step="0.01" class="input" />
            </div>
            <div>
                <label class="label">Tax amount</label>
                <input v-model.number="form.tax_amount" type="number" step="0.01" class="input" />
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'pharmacy-sales.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Complete Sale' }}
            </button>
        </div>
    </form>
</template>
