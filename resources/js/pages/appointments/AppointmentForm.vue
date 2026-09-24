<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();

const form = ref({
    patient_id: null,
    doctor_id: null,
    scheduled_at: '',
    duration_minutes: 30,
    type: 'new',
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
        await apiClient.post('/appointments', form.value);
        router.push({ name: 'appointments.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Appointment</h2>

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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Scheduled at</label>
                <input v-model="form.scheduled_at" type="datetime-local" class="input" />
                <p v-if="errors.scheduled_at" class="field-error">{{ errors.scheduled_at[0] }}</p>
            </div>
            <div>
                <label class="label">Duration (minutes)</label>
                <input v-model.number="form.duration_minutes" type="number" min="5" max="240" class="input" />
            </div>
        </div>

        <div>
            <label class="label">Type</label>
            <select v-model="form.type" class="input">
                <option value="new">New</option>
                <option value="follow-up">Follow-up</option>
                <option value="emergency">Emergency</option>
            </select>
        </div>

        <div>
            <label class="label">Notes</label>
            <textarea v-model="form.notes" rows="3" class="input"></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'appointments.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
