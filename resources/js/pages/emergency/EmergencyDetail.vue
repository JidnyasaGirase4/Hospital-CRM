<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import { formatDateTime } from '../../utils/format';

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
    <PageLoader v-if="loading" />
    <div v-else-if="visit" class="space-y-5">
        <PageHero
            :title="visit.patient?.name ?? 'Emergency Visit'"
            :status="visit.status"
            :subtitle="`MRN ${visit.patient?.mrn ?? '—'} · Triage: ${visit.triage_level || 'Not triaged'} · Registered ${formatDateTime(visit.registered_at)}`"
            initials
        >
            <template #actions>
                <PermissionGate permission="emergency.update">
                    <button v-if="visit.status === 'registered'" type="button" class="btn btn-warn-soft" @click="triage">Triage</button>
                    <button v-if="visit.status === 'triaged'" type="button" class="btn btn-primary" @click="startTreatment">Start Treatment</button>
                    <template v-if="visit.status === 'in-treatment'">
                        <button type="button" class="btn btn-soft" @click="admit">Admit</button>
                        <button type="button" class="btn btn-success-soft" @click="discharge">Discharge</button>
                        <button type="button" class="btn btn-soft" @click="refer">Refer</button>
                    </template>
                </PermissionGate>
            </template>
        </PageHero>

        <SectionCard title="Visit Details">
            <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="kv md:col-span-2"><dt>Chief complaint</dt><dd class="font-medium">{{ visit.chief_complaint || '—' }}</dd></div>
                <div v-if="visit.treatment_notes" class="kv md:col-span-2"><dt>Treatment notes</dt><dd class="whitespace-pre-line font-medium">{{ visit.treatment_notes }}</dd></div>
                <div v-if="visit.referred_to" class="kv"><dt>Referred to</dt><dd>{{ visit.referred_to }}</dd></div>
            </dl>
        </SectionCard>
    </div>
</template>
