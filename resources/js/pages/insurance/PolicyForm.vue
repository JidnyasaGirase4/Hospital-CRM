<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();
const companies = ref([]);

const form = ref({
    patient_id: null,
    insurance_company_id: '',
    policy_number: '',
    valid_from: '',
    valid_till: '',
    coverage_amount: '',
});
const errors = ref({});
const saving = ref(false);

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

onMounted(async () => {
    const { data } = await apiClient.get('/insurance-companies');
    companies.value = data.data;
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        await apiClient.post('/insurance-policies', form.value);
        router.push({ name: 'insurance-policies.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Insurance Policy</h2>

        <div>
            <label class="label">Patient</label>
            <SearchSelect v-model="form.patient_id" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="field-error">{{ errors.patient_id[0] }}</p>
        </div>

        <div>
            <label class="label">Insurance company</label>
            <select v-model="form.insurance_company_id" class="input">
                <option value="">Select…</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <p v-if="errors.insurance_company_id" class="field-error">{{ errors.insurance_company_id[0] }}</p>
        </div>

        <div>
            <label class="label">Policy number</label>
            <input v-model="form.policy_number" class="input" />
            <p v-if="errors.policy_number" class="field-error">{{ errors.policy_number[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Valid from</label>
                <input v-model="form.valid_from" type="date" class="input" />
            </div>
            <div>
                <label class="label">Valid till</label>
                <input v-model="form.valid_till" type="date" class="input" />
            </div>
        </div>

        <div>
            <label class="label">Coverage amount</label>
            <input v-model.number="form.coverage_amount" type="number" step="0.01" class="input" />
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'insurance-policies.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
