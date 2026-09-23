<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const route = useRoute();
const order = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/lab-orders/${route.params.id}`);
    order.value = data.data;
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

function collectSample(item) {
    runAction(() => apiClient.patch(`/lab-order-items/${item.id}/collect-sample`), 'Sample collected');
}

function startProcessing(item) {
    runAction(() => apiClient.patch(`/lab-order-items/${item.id}/start-processing`), 'Moved to processing');
}

async function recordResults(item) {
    const result = await ui.prompt({
        title: `Record Result — ${item.test?.name}`,
        fields: [
            { key: 'result_value', label: 'Result value', type: 'text', required: true },
            { key: 'unit', label: 'Unit (optional)', type: 'text' },
            {
                key: 'flag',
                label: 'Flag',
                type: 'select',
                options: ['normal', 'low', 'high', 'critical'],
            },
        ],
    });
    if (!result) return;
    runAction(() => apiClient.post(`/lab-order-items/${item.id}/results`, {
        results: [{ parameter_name: item.test?.name, result_value: result.result_value, unit: result.unit || undefined, flag: result.flag || undefined }],
    }), 'Result recorded');
}

function approve(item) {
    runAction(() => apiClient.patch(`/lab-order-items/${item.id}/approve`), 'Result approved');
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="order" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ order.patient?.name }} ({{ order.patient?.mrn }})</h2>
            <p class="text-sm text-slate-500">Dr. {{ order.doctor?.name }} · {{ order.ordered_at }} · {{ order.status }}</p>
        </div>

        <div v-for="item in order.items" :key="item.id" class="border border-slate-200 rounded p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-medium text-slate-800">{{ item.test?.name }} ({{ item.test?.code }})</h3>
                <span class="text-xs uppercase text-slate-500">{{ item.status }}</span>
            </div>
            <ul v-if="item.results?.length" class="text-sm text-slate-600 mb-2">
                <li v-for="r in item.results" :key="r.id">{{ r.parameter_name }}: {{ r.result_value }} {{ r.unit }} <span v-if="r.flag && r.flag !== 'normal'" class="text-red-600">({{ r.flag }})</span></li>
            </ul>
            <PermissionGate permission="laboratory.collect-sample">
                <div class="flex gap-3">
                    <button v-if="item.status === 'pending'" type="button" class="inline-flex items-center bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-100 hover:ring-brand-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="collectSample(item)">Collect Sample</button>
                    <button v-if="item.status === 'sample-collected'" type="button" class="inline-flex items-center bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-100 hover:ring-brand-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="startProcessing(item)">Start Processing</button>
                    <button v-if="item.status === 'processing'" type="button" class="inline-flex items-center bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-100 hover:ring-brand-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="recordResults(item)">Record Result</button>
                    <button v-if="item.status === 'resulted'" type="button" class="inline-flex items-center bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100 hover:ring-emerald-300 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-colors" @click="approve(item)">Approve</button>
                </div>
            </PermissionGate>
        </div>
    </div>
</template>
