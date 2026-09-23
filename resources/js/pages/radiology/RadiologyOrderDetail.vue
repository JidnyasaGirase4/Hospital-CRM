<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';

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
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="order" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ order.patient?.name }} ({{ order.patient?.mrn }})</h2>
                <p class="text-sm text-slate-500">{{ order.test?.name }} ({{ order.test?.modality }}) · Dr. {{ order.doctor?.name }} · {{ order.status }}</p>
            </div>
            <PermissionGate permission="radiology.update">
                <button v-if="order.status === 'ordered'" type="button" class="text-sm text-brand-600 hover:underline" @click="schedule">Schedule</button>
            </PermissionGate>
        </div>

        <div v-if="order.report" class="border border-slate-200 rounded p-4 space-y-2">
            <h3 class="font-medium text-slate-800">Report {{ order.report.is_approved ? '(Approved)' : '(Pending Approval)' }}</h3>
            <p class="text-sm text-slate-600"><span class="text-slate-400">Findings:</span> {{ order.report.findings || '—' }}</p>
            <p class="text-sm text-slate-600"><span class="text-slate-400">Impression:</span> {{ order.report.impression || '—' }}</p>
            <PermissionGate permission="radiology.update">
                <button v-if="!order.report.is_approved" type="button" class="inline-flex items-center bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100 hover:ring-emerald-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="approveReport">Approve Report</button>
            </PermissionGate>
        </div>

        <PermissionGate permission="radiology.update">
            <div v-if="order.status === 'scheduled' || order.status === 'ordered'">
                <button type="button" class="text-brand-600 text-sm hover:underline" @click="showReportForm = !showReportForm">
                    {{ showReportForm ? 'Cancel' : (order.report ? 'Edit Report' : '+ Submit Report') }}
                </button>
                <form v-if="showReportForm" class="mt-3 space-y-2" @submit.prevent="submitReport">
                    <textarea v-model="reportForm.findings" rows="2" placeholder="Findings" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                    <textarea v-model="reportForm.impression" rows="2" placeholder="Impression" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                    <button type="submit" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">Submit</button>
                </form>
            </div>
        </PermissionGate>
    </div>
</template>
