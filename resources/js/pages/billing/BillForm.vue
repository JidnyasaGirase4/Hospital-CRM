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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Bill</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Patient</label>
                <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
                <p v-if="errors.patient_id" class="field-error">{{ errors.patient_id[0] }}</p>
            </div>
            <div>
                <label class="label">Bill type</label>
                <select v-model="type" class="input">
                    <option v-for="t in ['opd', 'ipd', 'pharmacy', 'laboratory', 'radiology', 'ot', 'other']" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="card-title mb-3">Line Items</h3>
                <button type="button" class="btn btn-sm btn-soft" @click="addItem">+ Add item</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-start mb-2">
                <input v-model="item.category" placeholder="Category" class="input input-sm col-span-1" />
                <input v-model="item.description" placeholder="Description" class="input input-sm col-span-2" />
                <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="input input-sm" />
                <input v-model.number="item.unit_price" type="number" step="0.01" min="0" placeholder="Unit price" class="input input-sm" />
                <div class="flex gap-1">
                    <input v-model.number="item.discount_amount" type="number" step="0.01" min="0" placeholder="Disc." class="input input-sm" />
                    <button v-if="items.length > 1" type="button" class="btn btn-sm btn-danger-soft shrink-0 !px-2" title="Remove" aria-label="Remove item" @click="removeItem(index)">✕</button>
                </div>
            </div>
            <p v-if="errors.items" class="field-error">{{ errors.items[0] }}</p>
            <p class="text-right text-sm font-medium text-slate-700 mt-2">Estimated total: {{ total.toFixed(2) }}</p>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'bills.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Create Bill' }}
            </button>
        </div>
    </form>
</template>
