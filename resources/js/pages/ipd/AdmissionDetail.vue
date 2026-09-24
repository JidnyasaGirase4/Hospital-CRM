<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import Icon from '../../components/Icon.vue';
import { formatDateTime, doctorName } from '../../utils/format';

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
    const { data } = await apiClient.get('/lookups/beds');
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
            options: availableBeds.map((b) => ({ value: b.id, label: `Bed ${b.bed_number}${b.ward ? ` — ${b.ward}, Room ${b.room_number}` : ''}` })),
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
    <PageLoader v-if="loading" />
    <div v-else-if="admission" class="space-y-5">
        <PageHero
            :title="admission.patient?.name ?? 'Admission'"
            :status="admission.status"
            :subtitle="`MRN ${admission.patient?.mrn ?? '—'} · ${doctorName(admission.doctor?.name)} · ${admission.admission_type || 'General'} admission`"
            initials
        >
            <template #actions>
                <template v-if="admission.status !== 'discharged'">
                    <PermissionGate permission="beds.allocate">
                        <button type="button" class="btn btn-soft" @click="transferBed">Transfer Bed</button>
                    </PermissionGate>
                    <PermissionGate permission="ipd.discharge">
                        <button type="button" class="btn btn-danger-soft" @click="discharge">Discharge</button>
                    </PermissionGate>
                </template>
                <PermissionGate permission="billing.create">
                    <button v-if="admission.status === 'discharged'" type="button" class="btn btn-primary" @click="generateFinalBill"><Icon name="banknote" :size="16" /> Generate Final Bill</button>
                </PermissionGate>
            </template>
        </PageHero>

        <SectionCard title="Admission Details">
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="kv"><dt>Admitted</dt><dd>{{ formatDateTime(admission.admission_date) }}</dd></div>
                <div class="kv"><dt>Discharged</dt><dd>{{ admission.discharge_date ? formatDateTime(admission.discharge_date) : 'Still admitted' }}</dd></div>
                <div class="kv"><dt>Current bed</dt><dd>{{ admission.current_bed?.bed?.bed_number || 'Not allocated' }}</dd></div>
                <div class="kv"><dt>Reason</dt><dd>{{ admission.reason || '—' }}</dd></div>
            </dl>
            <div v-if="admission.discharge_summary" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Discharge summary</p>
                <p class="mt-1 text-sm text-emerald-900">{{ admission.discharge_summary }}</p>
            </div>
        </SectionCard>
    </div>
</template>
