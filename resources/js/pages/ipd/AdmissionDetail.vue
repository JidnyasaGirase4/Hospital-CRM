<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const route = useRoute();
const admission = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/admissions/${route.params.id}`);
    admission.value = data.data;
    loading.value = false;
}

async function discharge() {
    const result = await ui.prompt({
        title: 'Discharge Patient',
        fields: [{ key: 'discharge_summary', label: 'Discharge summary (optional)', type: 'textarea' }],
    });
    if (result === null) return;
    if (!(await ui.confirm({ title: 'Discharge Patient', message: 'Discharge this patient?', confirmLabel: 'Discharge' }))) return;
    await apiClient.patch(`/admissions/${admission.value.id}/discharge`, { discharge_summary: result.discharge_summary || undefined });
    ui.toast('Patient discharged', 'success');
    load();
}

async function transferBed() {
    const { data } = await apiClient.get('/beds', { params: { status: 'available' } });
    const availableBeds = data.data;
    if (availableBeds.length === 0) {
        ui.toast('No available beds to transfer to', 'error');
        return;
    }
    const result = await ui.prompt({
        title: 'Transfer Bed',
        fields: [{
            key: 'bed_id',
            label: 'Transfer to bed',
            type: 'select',
            required: true,
            options: availableBeds.map((b) => ({ value: b.id, label: `Bed ${b.bed_number}` })),
        }],
    });
    if (!result) return;
    try {
        await apiClient.patch(`/admissions/${admission.value.id}/transfer-bed`, { bed_id: Number(result.bed_id) });
        ui.toast('Bed transferred', 'success');
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Transfer failed', 'error');
    }
}

async function generateFinalBill() {
    try {
        await apiClient.post(`/admissions/${admission.value.id}/final-bill`);
        ui.toast('Final bill generated — check the Billing module', 'success');
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Failed to generate bill', 'error');
    }
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="admission" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ admission.patient?.name }} ({{ admission.patient?.mrn }})</h2>
                <p class="text-sm text-slate-500">Dr. {{ admission.doctor?.name }} · {{ admission.admission_type || 'General' }} · {{ admission.status }}</p>
            </div>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-400">Admitted</dt><dd>{{ admission.admission_date }}</dd></div>
            <div><dt class="text-slate-400">Discharged</dt><dd>{{ admission.discharge_date || '—' }}</dd></div>
            <div><dt class="text-slate-400">Current bed</dt><dd>{{ admission.current_bed?.bed?.bed_number || 'Not allocated' }}</dd></div>
            <div><dt class="text-slate-400">Reason</dt><dd>{{ admission.reason || '—' }}</dd></div>
        </dl>
        <p v-if="admission.discharge_summary" class="text-sm text-slate-600"><span class="text-slate-400">Discharge summary:</span> {{ admission.discharge_summary }}</p>

        <PermissionGate permission="ipd.update">
            <div v-if="admission.status !== 'discharged'" class="flex gap-3">
                <button type="button" class="text-sm text-brand-600 hover:underline" @click="transferBed">Transfer Bed</button>
                <button type="button" class="text-sm text-red-600 hover:underline" @click="discharge">Discharge</button>
            </div>
        </PermissionGate>
        <PermissionGate permission="billing.create">
            <button v-if="admission.status === 'discharged'" type="button" class="text-sm text-brand-600 hover:underline" @click="generateFinalBill">Generate Final Bill</button>
        </PermissionGate>
    </div>
</template>
