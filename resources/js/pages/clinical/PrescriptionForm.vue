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
    const { data } = await apiClient.get('/lookups/staff', { params: { search: q } });
    return data.data.map((u) => ({ id: u.id, label: u.name }));
}

async function fetchMedicines(q) {
    const { data } = await apiClient.get('/lookups/medicines', { params: { search: q } });
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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">New Prescription</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="card-title mb-3">Medicines</h3>
                <button type="button" class="btn btn-sm btn-soft" @click="addItem">+ Add medicine</button>
            </div>
            <div v-for="(item, index) in items" :key="index" class="border border-slate-200 rounded p-3 mb-2 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex-1 mr-2">
                        <SearchSelect v-model="item.medicine_id" :fetcher="fetchMedicines" placeholder="Search medicine…" />
                    </div>
                    <button v-if="items.length > 1" type="button" class="btn btn-sm btn-danger-soft" @click="removeItem(index)">Remove</button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <input v-model="item.dosage" placeholder="Dosage" class="input input-sm" />
                    <input v-model="item.frequency" placeholder="Frequency" class="input input-sm" />
                    <input v-model="item.route" placeholder="Route" class="input input-sm" />
                    <input v-model="item.duration" placeholder="Duration" class="input input-sm" />
                    <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="input input-sm" />
                </div>
                <input v-model="item.instructions" placeholder="Instructions" class="input input-sm" />
            </div>
            <p v-if="errors.items" class="field-error">{{ errors.items[0] }}</p>
        </div>

        <div>
            <label class="label">Notes</label>
            <textarea v-model="notes" rows="2" class="input"></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'prescriptions.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
