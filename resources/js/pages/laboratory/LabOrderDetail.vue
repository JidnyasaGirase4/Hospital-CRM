<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import Badge from '../../components/Badge.vue';
import { formatDateTime, doctorName } from '../../utils/format';

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
    <PageLoader v-if="loading" />
    <div v-else-if="order" class="space-y-5">
        <PageHero
            :title="order.patient?.name ?? 'Lab Order'"
            :status="order.status"
            :subtitle="`MRN ${order.patient?.mrn ?? '—'} · ${doctorName(order.doctor?.name)} · ${formatDateTime(order.ordered_at)}`"
            initials
        />

        <SectionCard v-for="item in order.items" :key="item.id" :title="`${item.test?.name ?? 'Test'} (${item.test?.code ?? '—'})`">
            <template #actions><Badge :value="item.status" /></template>
            <div v-if="item.results?.length" class="mb-4 overflow-x-auto">
                <table class="table-simple">
                    <thead><tr><th>Parameter</th><th>Result</th><th>Unit</th><th>Flag</th></tr></thead>
                    <tbody>
                        <tr v-for="r in item.results" :key="r.id">
                            <td class="font-medium text-slate-800">{{ r.parameter_name }}</td>
                            <td class="font-semibold tabular-nums">{{ r.result_value }}</td>
                            <td>{{ r.unit || '—' }}</td>
                            <td><Badge v-if="r.flag && r.flag !== 'normal'" :value="r.flag === 'high' || r.flag === 'low' ? 'critical' : r.flag" /><span v-else class="text-slate-400">Normal</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="mb-4 text-sm text-slate-400">No results recorded yet.</p>
            <PermissionGate permission="laboratory.collect-sample">
                <div class="flex flex-wrap gap-2">
                    <button v-if="item.status === 'pending'" type="button" class="btn btn-sm btn-soft" @click="collectSample(item)">Collect Sample</button>
                    <button v-if="item.status === 'sample-collected'" type="button" class="btn btn-sm btn-soft" @click="startProcessing(item)">Start Processing</button>
                    <button v-if="item.status === 'processing'" type="button" class="btn btn-sm btn-soft" @click="recordResults(item)">Record Result</button>
                    <button v-if="item.status === 'resulted'" type="button" class="btn btn-sm btn-success-soft" @click="approve(item)">Approve</button>
                </div>
            </PermissionGate>
        </SectionCard>
    </div>
</template>
