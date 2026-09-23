<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';

const router = useRouter();

const patientId = ref(null);
const doctorId = ref(null);
const notes = ref('');
const items = ref([{ medicine_id: null, dosage: '', frequency: '', route: '', duration: '', quantity: 1, instructions: '' }]);
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

async function fetchMedicines(q) {
    const { data } = await apiClient.get('/medicines', { params: { search: q, per_page: 10 } });
    return data.data.map((m) => ({ id: m.id, label: `${m.name} ${m.strength || ''}`.trim() }));
}

function addItem() {
    items.value.push({ medicine_id: null, dosage: '', frequency: '', route: '', duration: '', quantity: 1, instructions: '' });
}

function removeItem(index) {
    items.value.splice(index, 1);
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        await apiClient.post('/prescriptions', {
            patient_id: patientId.value,
            doctor_id: doctorId.value,
            notes: notes.value,
            items: items.value,
        });
        router.push({ name: 'prescriptions.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">New Prescription</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Patient</label>
                <SearchSelect v-model="patientId" :fetcher="fetchPatients" placeholder="Search patient by name/MRN…" />
                <p v-if="errors.patient_id" class="text-xs text-red-600 mt-1">{{ errors.patient_id[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Doctor</label>
                <SearchSelect v-model="doctorId" :fetcher="fetchDoctors" placeholder="Search staff by name…" />
                <p v-if="errors.doctor_id" class="text-xs text-red-600 mt-1">{{ errors.doctor_id[0] }}</p>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-slate-600">Medicines</h3>
                <button type="button" class="text-brand-600 text-xs hover:underline" @click="addItem">+ Add medicine</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="border border-slate-200 rounded p-3 mb-2 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex-1 mr-2">
                        <SearchSelect v-model="item.medicine_id" :fetcher="fetchMedicines" placeholder="Search medicine…" />
                    </div>
                    <button v-if="items.length > 1" type="button" class="text-red-500 text-xs" @click="removeItem(index)">Remove</button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <input v-model="item.dosage" placeholder="Dosage" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                    <input v-model="item.frequency" placeholder="Frequency" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                    <input v-model="item.route" placeholder="Route" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                    <input v-model="item.duration" placeholder="Duration" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                    <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                </div>
                <input v-model="item.instructions" placeholder="Instructions" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" />
            </div>
            <p v-if="errors.items" class="text-xs text-red-600 mt-1">{{ errors.items[0] }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
            <textarea v-model="notes" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'prescriptions.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
