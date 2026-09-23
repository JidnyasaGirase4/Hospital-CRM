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
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">Register Emergency Visit</h2>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Patient</label>
            <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="text-xs text-red-600 mt-1">{{ errors.patient_id[0] }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Chief complaint</label>
            <textarea v-model="chiefComplaint" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>
        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'emergency-visits.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Register' }}
            </button>
        </div>
    </form>
</template>
