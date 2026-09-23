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
    const { data } = await apiClient.get('/users', { params: { search: q, per_page: 10 } });
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
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">New Appointment</h2>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Patient</label>
            <SearchSelect v-model="form.patient_id" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="text-xs text-red-600 mt-1">{{ errors.patient_id[0] }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Doctor</label>
            <SearchSelect v-model="form.doctor_id" :fetcher="fetchDoctors" placeholder="Search staff by name…" />
            <p v-if="errors.doctor_id" class="text-xs text-red-600 mt-1">{{ errors.doctor_id[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Scheduled at</label>
                <input v-model="form.scheduled_at" type="datetime-local" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.scheduled_at" class="text-xs text-red-600 mt-1">{{ errors.scheduled_at[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Duration (minutes)</label>
                <input v-model.number="form.duration_minutes" type="number" min="5" max="240" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
            <select v-model="form.type" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                <option value="new">New</option>
                <option value="follow-up">Follow-up</option>
                <option value="emergency">Emergency</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'appointments.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
