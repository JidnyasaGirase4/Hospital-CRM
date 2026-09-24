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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">Record Payment</h2>

        <div v-if="!route.query.patient_id">
            <label class="label">Patient</label>
            <SearchSelect v-model="form.patient_id" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="field-error">{{ errors.patient_id[0] }}</p>
        </div>
        <div v-if="form.bill_id">
            <label class="label">Bill ID</label>
            <input v-model.number="form.bill_id" type="number" class="input bg-slate-100" readonly />
        </div>

        <div>
            <label class="label">Amount</label>
            <input v-model.number="form.amount" type="number" step="0.01" min="0.01" class="input" />
            <p v-if="errors.amount" class="field-error">{{ errors.amount[0] }}</p>
        </div>

        <div>
            <label class="label">Method</label>
            <select v-model="form.method" class="input">
                <option v-for="m in ['cash', 'card', 'upi', 'bank-transfer', 'cheque', 'insurance', 'tpa']" :key="m" :value="m">{{ m }}</option>
            </select>
        </div>

        <div>
            <label class="label">Reference number</label>
            <input v-model="form.reference_number" class="input" />
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'payments.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Record Payment' }}
            </button>
        </div>
    </form>
</template>
