<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const route = useRoute();
const router = useRouter();

const form = ref({
    patient_id: route.query.patient_id ? Number(route.query.patient_id) : null,
    bill_id: route.query.bill_id ? Number(route.query.bill_id) : null,
    amount: '',
    method: 'cash',
    reference_number: '',
});
const errors = ref({});
const saving = ref(false);

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

onMounted(async () => {
    if (form.value.bill_id) {
        try {
            const { data } = await apiClient.get(`/bills/${form.value.bill_id}`);
            form.value.amount = data.data.outstanding_amount;
        } catch {
            // bill lookup best-effort only, ignore failures
        }
    }
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const { data } = await apiClient.post('/payments', form.value);
        if (form.value.bill_id) {
            router.push({ name: 'bills.show', params: { id: form.value.bill_id } });
        } else {
            router.push({ name: 'payments.index' });
        }
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">Record Payment</h2>

        <div v-if="!route.query.patient_id">
            <label class="block text-sm font-medium text-slate-700 mb-1">Patient</label>
            <SearchSelect v-model="form.patient_id" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="text-xs text-red-600 mt-1">{{ errors.patient_id[0] }}</p>
        </div>
        <div v-if="form.bill_id">
            <label class="block text-sm font-medium text-slate-700 mb-1">Bill ID</label>
            <input v-model.number="form.bill_id" type="number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-slate-100" readonly />
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
            <input v-model.number="form.amount" type="number" step="0.01" min="0.01" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            <p v-if="errors.amount" class="text-xs text-red-600 mt-1">{{ errors.amount[0] }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Method</label>
            <select v-model="form.method" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                <option v-for="m in ['cash', 'card', 'upi', 'bank-transfer', 'cheque', 'insurance', 'tpa']" :key="m" :value="m">{{ m }}</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Reference number</label>
            <input v-model="form.reference_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'payments.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Record Payment' }}
            </button>
        </div>
    </form>
</template>
