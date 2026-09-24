<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';
import SearchSelect from '../../components/SearchSelect.vue';
import PermissionGate from '../../components/PermissionGate.vue';

const tab = ref('vitals');

const vitals = ref([]);
const notes = ref([]);
const meds = ref([]);

const vitalPatient = ref(null);
const vitalForm = ref({ temperature_c: '', pulse: '', bp_systolic: '', bp_diastolic: '', respiratory_rate: '', spo2: '' });

const notePatient = ref(null);
const noteForm = ref({ type: 'general', note: '', shift: '' });

const medPatient = ref(null);
const medicineId = ref(null);
const medForm = ref({ dose_given: '', notes: '' });

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

async function fetchMedicines(q) {
    const { data } = await apiClient.get('/lookups/medicines', { params: { search: q } });
    return data.data.map((m) => ({ id: m.id, label: `${m.name} ${m.strength || ''}`.trim() }));
}

async function loadVitals() {
    const { data } = await apiClient.get('/patient-vitals', { params: { per_page: 20 } });
    vitals.value = data.data;
}
async function loadNotes() {
    const { data } = await apiClient.get('/nursing-notes', { params: { per_page: 20 } });
    notes.value = data.data;
}
async function loadMeds() {
    const { data } = await apiClient.get('/medication-administrations', { params: { per_page: 20 } });
    meds.value = data.data;
}

async function addVital() {
    if (!vitalPatient.value) return;
    await apiClient.post('/patient-vitals', { patient_id: vitalPatient.value, ...vitalForm.value });
    vitalForm.value = { temperature_c: '', pulse: '', bp_systolic: '', bp_diastolic: '', respiratory_rate: '', spo2: '' };
    loadVitals();
}

async function addNote() {
    if (!notePatient.value || !noteForm.value.note) return;
    await apiClient.post('/nursing-notes', { patient_id: notePatient.value, ...noteForm.value });
    noteForm.value = { type: 'general', note: '', shift: '' };
    loadNotes();
}

async function addMed() {
    if (!medPatient.value || !medicineId.value) return;
    await apiClient.post('/medication-administrations', { patient_id: medPatient.value, medicine_id: medicineId.value, ...medForm.value });
    medForm.value = { dose_given: '', notes: '' };
    loadMeds();
}

onMounted(() => {
    loadVitals();
    loadNotes();
    loadMeds();
});
</script>

<template>
    <div class="space-y-4">
        <div class="tab-bar">
            <button v-for="t in ['vitals', 'notes', 'medications']" :key="t" type="button"
                class="tab"
                :class="{ 'tab-active': tab === t }"
                @click="tab = t">{{ t }}</button>
        </div>

        <div v-if="tab === 'vitals'" class="space-y-4">
            <PermissionGate permission="nursing.create">
                <form class="card p-4 space-y-2" @submit.prevent="addVital">
                    <SearchSelect v-model="vitalPatient" :fetcher="fetchPatients" placeholder="Search patient…" />
                    <div class="grid grid-cols-2 sm:grid-cols-6 gap-2">
                        <input v-model.number="vitalForm.temperature_c" type="number" step="0.1" placeholder="Temp °C" class="input input-sm" />
                        <input v-model.number="vitalForm.pulse" type="number" placeholder="Pulse" class="input input-sm" />
                        <input v-model.number="vitalForm.bp_systolic" type="number" placeholder="BP Sys" class="input input-sm" />
                        <input v-model.number="vitalForm.bp_diastolic" type="number" placeholder="BP Dia" class="input input-sm" />
                        <input v-model.number="vitalForm.respiratory_rate" type="number" placeholder="Resp Rate" class="input input-sm" />
                        <input v-model.number="vitalForm.spo2" type="number" placeholder="SpO2" class="input input-sm" />
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Record Vitals</button>
                </form>
            </PermissionGate>
            <div class="card p-4">
                <table class="table-simple">
                    <thead><tr><th>Patient</th><th>Temp</th><th>Pulse</th><th>BP</th><th>SpO2</th><th>Recorded</th></tr></thead>
                    <tbody>
                        <tr v-for="v in vitals" :key="v.id">
                            <td class="font-medium text-slate-800">#{{ v.patient_id }}</td>
                            <td>{{ v.temperature_c ?? '—' }}</td>
                            <td>{{ v.pulse ?? '—' }}</td>
                            <td>{{ v.bp_systolic ?? '—' }}/{{ v.bp_diastolic ?? '—' }}</td>
                            <td>{{ v.spo2 ?? '—' }}</td>
                            <td>{{ v.recorded_at }}</td>
                        </tr>
                        <tr v-if="vitals.length === 0"><td colspan="6" class="empty-note">No vitals recorded yet</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'notes'" class="space-y-4">
            <PermissionGate permission="nursing.create">
                <form class="card p-4 space-y-2" @submit.prevent="addNote">
                    <SearchSelect v-model="notePatient" :fetcher="fetchPatients" placeholder="Search patient…" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <select v-model="noteForm.type" class="input input-sm">
                            <option value="general">General</option>
                            <option value="intake-output">Intake/Output</option>
                            <option value="care-plan">Care Plan</option>
                            <option value="shift-handover">Shift Handover</option>
                        </select>
                        <select v-model="noteForm.shift" class="input input-sm">
                            <option value="">Shift…</option>
                            <option value="morning">Morning</option>
                            <option value="evening">Evening</option>
                            <option value="night">Night</option>
                        </select>
                    </div>
                    <textarea v-model="noteForm.note" rows="2" placeholder="Note" class="input input-sm"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
                </form>
            </PermissionGate>
            <div class="card p-4 space-y-2">
                <div v-for="n in notes" :key="n.id" class="text-sm border-b border-slate-100 pb-2">
                    <p class="text-slate-400 text-xs">Patient #{{ n.patient_id }} · {{ n.type }} · {{ n.shift || '—' }} · {{ n.recorded_at }} · by {{ n.nurse?.name }}</p>
                    <p>{{ n.note }}</p>
                </div>
                <p v-if="notes.length === 0" class="text-slate-400 text-sm text-center py-3">No notes yet</p>
            </div>
        </div>

        <div v-if="tab === 'medications'" class="space-y-4">
            <PermissionGate permission="nursing.create">
                <form class="card p-4 space-y-2" @submit.prevent="addMed">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <SearchSelect v-model="medPatient" :fetcher="fetchPatients" placeholder="Search patient…" />
                        <SearchSelect v-model="medicineId" :fetcher="fetchMedicines" placeholder="Search medicine…" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <input v-model="medForm.dose_given" placeholder="Dose given" class="input input-sm" />
                        <input v-model="medForm.notes" placeholder="Notes" class="input input-sm" />
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Record Administration</button>
                </form>
            </PermissionGate>
            <div class="card p-4">
                <table class="table-simple">
                    <thead><tr><th>Patient</th><th>Medicine</th><th>Dose</th><th>By</th><th>At</th></tr></thead>
                    <tbody>
                        <tr v-for="m in meds" :key="m.id">
                            <td class="font-medium text-slate-800">#{{ m.patient_id }}</td>
                            <td>{{ m.medicine?.name }}</td>
                            <td>{{ m.dose_given || '—' }}</td>
                            <td>{{ m.administered_by?.name }}</td>
                            <td>{{ m.administered_at }}</td>
                        </tr>
                        <tr v-if="meds.length === 0"><td colspan="5" class="empty-note">No records yet</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
