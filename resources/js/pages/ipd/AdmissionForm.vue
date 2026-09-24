<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();
const availableBeds = ref([]);

const form = ref({ patient_id: null, doctor_id: null, admission_type: '', reason: '', bed_id: '' });
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

onMounted(async () => {
    const { data } = await apiClient.get('/lookups/beds');
    availableBeds.value = data.data;
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const { data } = await apiClient.post('/admissions', { ...form.value, bed_id: form.value.bed_id || undefined });
        router.push({ name: 'admissions.show', params: { id: data.data.id } });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Admission</h2>

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
            <label class="label">Admission type</label>
            <input v-model="form.admission_type" placeholder="e.g. Planned, Emergency" class="input" />
        </div>
        <div>
            <label class="label">Reason</label>
            <textarea v-model="form.reason" rows="2" class="input"></textarea>
        </div>
        <div>
            <label class="label">Bed (optional — allocate now)</label>
            <select v-model="form.bed_id" class="input">
                <option value="">Allocate later</option>
                <option v-for="b in availableBeds" :key="b.id" :value="b.id">Bed {{ b.bed_number }}{{ b.ward ? ' — ' + b.ward + ', Room ' + b.room_number : '' }}</option>
            </select>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'admissions.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Admit Patient' }}
            </button>
        </div>
    </form>
</template>
