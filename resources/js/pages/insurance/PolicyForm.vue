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
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">New Insurance Policy</h2>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Patient</label>
            <SearchSelect v-model="form.patient_id" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="text-xs text-red-600 mt-1">{{ errors.patient_id[0] }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Insurance company</label>
            <select v-model="form.insurance_company_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                <option value="">Select…</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <p v-if="errors.insurance_company_id" class="text-xs text-red-600 mt-1">{{ errors.insurance_company_id[0] }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Policy number</label>
            <input v-model="form.policy_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            <p v-if="errors.policy_number" class="text-xs text-red-600 mt-1">{{ errors.policy_number[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Valid from</label>
                <input v-model="form.valid_from" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Valid till</label>
                <input v-model="form.valid_till" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Coverage amount</label>
            <input v-model.number="form.coverage_amount" type="number" step="0.01" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'insurance-policies.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
