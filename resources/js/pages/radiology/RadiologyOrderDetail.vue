<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import Badge from '../../components/Badge.vue';
import { doctorName } from '../../utils/format';

const route = useRoute();
const order = ref(null);
const loading = ref(true);
const reportForm = ref({ findings: '', impression: '' });
const showReportForm = ref(false);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/radiology-orders/${route.params.id}`);
    order.value = data.data;
    if (order.value.report) {
        reportForm.value = { findings: order.value.report.findings || '', impression: order.value.report.impression || '' };
    }
    loading.value = false;
}

async function schedule() {
    await apiClient.patch(`/radiology-orders/${order.value.id}/schedule`);
    load();
}

async function submitReport() {
    await apiClient.post(`/radiology-orders/${order.value.id}/report`, reportForm.value);
    showReportForm.value = false;
    load();
}

async function approveReport() {
    await apiClient.patch(`/radiology-orders/${order.value.id}/report/approve`);
    load();
}

onMounted(load);
</script>

<template>
    <PageLoader v-if="loading" />
    <div v-else-if="order" class="space-y-5">
        <PageHero
            :title="order.patient?.name ?? 'Radiology Order'"
            :status="order.status"
            :subtitle="`${order.test?.name ?? 'Test'} (${order.test?.modality ?? '—'}) · ${doctorName(order.doctor?.name)} · MRN ${order.patient?.mrn ?? '—'}`"
            initials
        >
            <template #actions>
                <PermissionGate permission="radiology.update">
                    <button v-if="order.status === 'ordered'" type="button" class="btn btn-primary" @click="schedule">Schedule</button>
                </PermissionGate>
            </template>
        </PageHero>

        <SectionCard v-if="order.report" title="Report">
            <template #actions><Badge :value="order.report.is_approved ? 'approved' : 'pending'" /></template>
            <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="kv"><dt>Findings</dt><dd class="whitespace-pre-line font-medium">{{ order.report.findings || '—' }}</dd></div>
                <div class="kv"><dt>Impression</dt><dd class="whitespace-pre-line font-medium">{{ order.report.impression || '—' }}</dd></div>
            </dl>
            <PermissionGate permission="radiology.update">
                <button v-if="!order.report.is_approved" type="button" class="btn btn-success-soft mt-4" @click="approveReport">Approve Report</button>
            </PermissionGate>
        </SectionCard>

        <PermissionGate permission="radiology.update">
            <SectionCard v-if="order.status === 'scheduled' || order.status === 'ordered'" :title="order.report ? 'Edit Report' : 'Submit Report'">
                <template #actions>
                    <button type="button" class="btn btn-sm btn-soft" @click="showReportForm = !showReportForm">
                        {{ showReportForm ? 'Cancel' : (order.report ? 'Edit Report' : '+ Submit Report') }}
                    </button>
                </template>
                <form v-if="showReportForm" class="space-y-4" @submit.prevent="submitReport">
                    <div><label class="label">Findings</label><textarea v-model="reportForm.findings" rows="3" placeholder="Findings" class="input"></textarea></div>
                    <div><label class="label">Impression</label><textarea v-model="reportForm.impression" rows="3" placeholder="Impression" class="input"></textarea></div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
                <p v-else class="text-sm text-slate-500">Use the button above to write or update the radiology report.</p>
            </SectionCard>
        </PermissionGate>
    </div>
</template>
