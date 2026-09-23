<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const route = useRoute();
const visit = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/emergency-visits/${route.params.id}`);
    visit.value = data.data;
    loading.value = false;
}

async function runAction(fn, successMessage) {
    try {
        await fn();
        if (successMessage) ui.toast(successMessage, 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Action failed', 'error');
    }
}

async function triage() {
    const result = await ui.prompt({
        title: 'Triage Patient',
        fields: [{
            key: 'triage_level',
            label: 'Triage level',
            type: 'select',
            required: true,
            options: ['critical', 'urgent', 'semi-urgent', 'non-urgent'],
        }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/emergency-visits/${visit.value.id}/triage`, { triage_level: result.triage_level }), 'Patient triaged');
}
function startTreatment() {
    runAction(() => apiClient.patch(`/emergency-visits/${visit.value.id}/start-treatment`), 'Treatment started');
}
async function discharge() {
    if (!(await ui.confirm({ title: 'Discharge Patient', message: 'Discharge this patient from emergency?' }))) return;
    runAction(() => apiClient.patch(`/emergency-visits/${visit.value.id}/discharge`), 'Patient discharged');
}
async function refer() {
    const result = await ui.prompt({
        title: 'Refer Patient',
        fields: [{ key: 'referred_to', label: 'Refer to (hospital/department)', type: 'text', required: true }],
    });
    if (!result) return;
    runAction(() => apiClient.patch(`/emergency-visits/${visit.value.id}/refer`, { referred_to: result.referred_to }), 'Patient referred');
}
async function admit() {
    if (!(await ui.confirm({ title: 'Admit Patient', message: 'Admit this patient to IPD?' }))) return;
    runAction(() => apiClient.patch(`/emergency-visits/${visit.value.id}/admit`, {}), 'Patient admitted');
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="visit" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ visit.patient?.name }} ({{ visit.patient?.mrn }})</h2>
            <p class="text-sm text-slate-500">{{ visit.status }} · Triage: {{ visit.triage_level || 'Not triaged' }} · {{ visit.registered_at }}</p>
        </div>
        <p class="text-sm text-slate-600"><span class="text-slate-400">Chief complaint:</span> {{ visit.chief_complaint || '—' }}</p>
        <p v-if="visit.treatment_notes" class="text-sm text-slate-600"><span class="text-slate-400">Treatment notes:</span> {{ visit.treatment_notes }}</p>
        <p v-if="visit.referred_to" class="text-sm text-slate-600"><span class="text-slate-400">Referred to:</span> {{ visit.referred_to }}</p>

        <PermissionGate permission="emergency.update">
            <div class="flex gap-3 flex-wrap">
                <button v-if="visit.status === 'registered'" type="button" class="text-sm text-amber-600 hover:underline" @click="triage">Triage</button>
                <button v-if="visit.status === 'triaged'" type="button" class="text-sm text-brand-600 hover:underline" @click="startTreatment">Start Treatment</button>
                <template v-if="visit.status === 'in-treatment'">
                    <button type="button" class="text-sm text-indigo-600 hover:underline" @click="admit">Admit</button>
                    <button type="button" class="text-sm text-emerald-600 hover:underline" @click="discharge">Discharge</button>
                    <button type="button" class="text-sm text-purple-600 hover:underline" @click="refer">Refer</button>
                </template>
            </div>
        </PermissionGate>
    </div>
</template>
