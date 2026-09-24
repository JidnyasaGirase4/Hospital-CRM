<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();
const tests = ref([]);

const patientId = ref(null);
const doctorId = ref(null);
const notes = ref('');
const selectedTestIds = ref([]);
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
    const { data } = await apiClient.get('/lab-tests');
    tests.value = data.data;
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const { data } = await apiClient.post('/lab-orders', {
            patient_id: patientId.value,
            doctor_id: doctorId.value,
            notes: notes.value,
            test_ids: selectedTestIds.value,
        });
        router.push({ name: 'lab-orders.show', params: { id: data.data.id } });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Lab Order</h2>

        <div>
            <label class="label">Patient</label>
            <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
            <p v-if="errors.patient_id" class="field-error">{{ errors.patient_id[0] }}</p>
        </div>
        <div>
            <label class="label">Doctor</label>
            <SearchSelect v-model="doctorId" :fetcher="fetchDoctors" placeholder="Search staff by name…" />
            <p v-if="errors.doctor_id" class="field-error">{{ errors.doctor_id[0] }}</p>
        </div>
        <div>
            <label class="label">Tests</label>
            <div class="flex flex-wrap gap-2">
                <label v-for="t in tests" :key="t.id" class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-brand-300 hover:bg-brand-50/50">
                    <input type="checkbox" :value="t.id" v-model="selectedTestIds" /> {{ t.name }}
                </label>
            </div>
            <p v-if="errors.test_ids" class="field-error">{{ errors.test_ids[0] }}</p>
        </div>
        <div>
            <label class="label">Notes</label>
            <textarea v-model="notes" rows="2" class="input"></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'lab-orders.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
