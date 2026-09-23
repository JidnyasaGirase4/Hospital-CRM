<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const route = useRoute();
const router = useRouter();
const prescription = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/prescriptions/${route.params.id}`);
    prescription.value = data.data;
    loading.value = false;
}

async function cancelPrescription() {
    if (!(await ui.confirm({ title: 'Cancel Prescription', message: 'Cancel this prescription?', danger: true }))) return;
    await apiClient.delete(`/prescriptions/${prescription.value.id}`);
    ui.toast('Prescription cancelled', 'success');
    router.push({ name: 'prescriptions.index' });
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="prescription" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ prescription.patient?.name }} ({{ prescription.patient?.mrn }})</h2>
                <p class="text-sm text-slate-500">Dr. {{ prescription.doctor?.name }} · {{ prescription.status }}</p>
            </div>
            <PermissionGate permission="prescriptions.delete">
                <button v-if="prescription.status !== 'cancelled'" type="button" class="text-sm text-red-600 hover:underline" @click="cancelPrescription">Cancel</button>
            </PermissionGate>
        </div>

        <table class="w-full text-sm">
            <thead class="text-left text-slate-500">
                <tr><th class="py-1">Medicine</th><th>Dosage</th><th>Frequency</th><th>Qty</th><th>Dispensed</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="item in prescription.items" :key="item.id">
                    <td class="py-1.5">{{ item.medicine?.name }} {{ item.medicine?.strength }}</td>
                    <td>{{ item.dosage || '—' }}</td>
                    <td>{{ item.frequency || '—' }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ item.dispensed_quantity }} / {{ item.quantity }}</td>
                </tr>
            </tbody>
        </table>
        <p v-if="prescription.notes" class="text-sm text-slate-600"><span class="text-slate-400">Notes:</span> {{ prescription.notes }}</p>
    </div>
</template>
