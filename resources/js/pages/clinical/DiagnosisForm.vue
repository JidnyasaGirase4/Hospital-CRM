<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();

const form = ref({
    patient_id: null,
    doctor_id: null,
    diagnosis_code: '',
    diagnosis_name: '',
    notes: '',
});
const errors = ref({});
const saving = ref(false);

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

async function fetchDoctors(q) {
    const { data } = await apiClient.get('/lookups/staff', { params: { search: q } });
    return data.data.map((u) => ({ id: u.id, label: u.name }));
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        await apiClient.post('/diagnoses', form.value);
        router.push({ name: 'diagnoses.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Diagnosis</h2>

        <div>
            <label class="label">Patient</label>
            <SearchSelect v-model="form.patient_id" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="field-error">{{ errors.patient_id[0] }}</p>
        </div>
        <div>
            <label class="label">Doctor</label>
            <SearchSelect v-model="form.doctor_id" :fetcher="fetchDoctors" placeholder="Search staff by name…" />
            <p v-if="errors.doctor_id" class="field-error">{{ errors.doctor_id[0] }}</p>
        </div>
        <div>
            <label class="label">Diagnosis name</label>
            <input v-model="form.diagnosis_name" class="input" />
            <p v-if="errors.diagnosis_name" class="field-error">{{ errors.diagnosis_name[0] }}</p>
        </div>
        <div>
            <label class="label">Diagnosis code (ICD, optional)</label>
            <input v-model="form.diagnosis_code" class="input" />
        </div>
        <div>
            <label class="label">Notes</label>
            <textarea v-model="form.notes" rows="2" class="input"></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'diagnoses.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
