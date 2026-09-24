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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">Submit Insurance Claim</h2>

        <div>
            <label class="label">Patient</label>
            <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
        </div>

        <div>
            <label class="label">Policy</label>
            <select v-model="form.insurance_policy_id" :disabled="!patientId" class="input">
                <option value="">{{ patientId ? 'Select…' : 'Select a patient first' }}</option>
                <option v-for="p in policies" :key="p.id" :value="p.id">{{ p.policy_number }} ({{ p.insurance_company?.name }})</option>
            </select>
            <p v-if="patientId && policies.length === 0" class="text-xs text-slate-400 mt-1">This patient has no insurance policies yet.</p>
            <p v-if="errors.insurance_policy_id" class="field-error">{{ errors.insurance_policy_id[0] }}</p>
        </div>

        <div>
            <label class="label">Bill ID (optional)</label>
            <input v-model.number="form.bill_id" type="number" class="input" />
        </div>

        <div>
            <label class="label">Requested amount</label>
            <input v-model.number="form.requested_amount" type="number" step="0.01" min="0.01" class="input" />
            <p v-if="errors.requested_amount" class="field-error">{{ errors.requested_amount[0] }}</p>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'insurance-claims.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Submit' }}
            </button>
        </div>
    </form>
</template>
