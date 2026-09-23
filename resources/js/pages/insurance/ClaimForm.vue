<script setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();

const patientId = ref(null);
const policies = ref([]);
const form = ref({ insurance_policy_id: '', bill_id: '', requested_amount: '' });
const errors = ref({});
const saving = ref(false);

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

watch(patientId, async (id) => {
    form.value.insurance_policy_id = '';
    policies.value = [];
    if (!id) return;
    const { data } = await apiClient.get('/insurance-policies', { params: { patient_id: id, per_page: 50 } });
    policies.value = data.data;
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        await apiClient.post('/insurance-claims', {
            insurance_policy_id: form.value.insurance_policy_id,
            bill_id: form.value.bill_id || undefined,
            requested_amount: form.value.requested_amount,
        });
        router.push({ name: 'insurance-claims.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">Submit Insurance Claim</h2>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Patient</label>
            <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Policy</label>
            <select v-model="form.insurance_policy_id" :disabled="!patientId" class="w-full border border-slate-300 rounded px-3 py-2 text-sm disabled:bg-slate-100">
                <option value="">{{ patientId ? 'Select…' : 'Select a patient first' }}</option>
                <option v-for="p in policies" :key="p.id" :value="p.id">{{ p.policy_number }} ({{ p.insurance_company?.name }})</option>
            </select>
            <p v-if="patientId && policies.length === 0" class="text-xs text-slate-400 mt-1">This patient has no insurance policies yet.</p>
            <p v-if="errors.insurance_policy_id" class="text-xs text-red-600 mt-1">{{ errors.insurance_policy_id[0] }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Bill ID (optional)</label>
            <input v-model.number="form.bill_id" type="number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Requested amount</label>
            <input v-model.number="form.requested_amount" type="number" step="0.01" min="0.01" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            <p v-if="errors.requested_amount" class="text-xs text-red-600 mt-1">{{ errors.requested_amount[0] }}</p>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'insurance-claims.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Submit' }}
            </button>
        </div>
    </form>
</template>
