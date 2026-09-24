<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();
const patientId = ref(null);
const chiefComplaint = ref('');
const errors = ref({});
const saving = ref(false);

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const { data } = await apiClient.post('/emergency-visits', { patient_id: patientId.value, chief_complaint: chiefComplaint.value });
        router.push({ name: 'emergency-visits.show', params: { id: data.data.id } });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">Register Emergency Visit</h2>
        <div>
            <label class="label">Patient</label>
            <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="field-error">{{ errors.patient_id[0] }}</p>
        </div>
        <div>
            <label class="label">Chief complaint</label>
            <textarea v-model="chiefComplaint" rows="3" class="input"></textarea>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'emergency-visits.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Register' }}
            </button>
        </div>
    </form>
</template>
