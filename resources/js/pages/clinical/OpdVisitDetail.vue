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
const saving = ref(false);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/opd-visits/${route.params.id}`);
    visit.value = data.data;
    loading.value = false;
}

async function save() {
    saving.value = true;
    try {
        const { data } = await apiClient.put(`/opd-visits/${visit.value.id}`, {
            symptoms: visit.value.symptoms,
            diagnosis: visit.value.diagnosis,
            notes: visit.value.notes,
            follow_up_date: visit.value.follow_up_date,
        });
        visit.value = data.data;
        ui.toast('Visit updated', 'success');
    } finally {
        saving.value = false;
    }
}

async function closeVisit() {
    if (!(await ui.confirm({ title: 'Close OPD Visit', message: 'Close this OPD visit?' }))) return;
    await apiClient.patch(`/opd-visits/${visit.value.id}/close`);
    ui.toast('OPD visit closed', 'success');
    load();
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="visit" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ visit.patient?.name }} ({{ visit.patient?.mrn }})</h2>
                <p class="text-sm text-slate-500">Dr. {{ visit.doctor?.name }} · {{ visit.visit_date }} · {{ visit.status }}</p>
            </div>
            <PermissionGate permission="opd.update">
                <button v-if="visit.status !== 'closed'" type="button" class="text-sm text-red-600 hover:underline" @click="closeVisit">Close Visit</button>
            </PermissionGate>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Symptoms</label>
            <textarea v-model="visit.symptoms" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Diagnosis</label>
            <textarea v-model="visit.diagnosis" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
            <textarea v-model="visit.notes" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Follow-up date</label>
            <input v-model="visit.follow_up_date" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
        </div>
        <div v-if="visit.vitals" class="text-sm text-slate-600">
            <p class="font-medium text-slate-700 mb-1">Vitals</p>
            <p>{{ Object.entries(visit.vitals).filter(([, v]) => v !== null).map(([k, v]) => `${k}: ${v}`).join(' · ') || '—' }}</p>
        </div>

        <PermissionGate permission="opd.update">
            <div class="flex justify-end">
                <button type="button" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50" @click="save">
                    {{ saving ? 'Saving…' : 'Save Changes' }}
                </button>
            </div>
        </PermissionGate>
    </div>
</template>
