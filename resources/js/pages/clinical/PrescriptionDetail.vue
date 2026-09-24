<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import { doctorName } from '../../utils/format';

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
    <PageLoader v-if="loading" />
    <div v-else-if="prescription" class="space-y-5">
        <PageHero
            :title="prescription.patient?.name ?? 'Prescription'"
            :status="prescription.status"
            :subtitle="`MRN ${prescription.patient?.mrn ?? '—'} · ${doctorName(prescription.doctor?.name)}`"
            initials
        >
            <template #actions>
                <PermissionGate permission="prescriptions.delete">
                    <button v-if="prescription.status !== 'cancelled'" type="button" class="btn btn-danger-soft" @click="cancelPrescription">Cancel Prescription</button>
                </PermissionGate>
            </template>
        </PageHero>

        <SectionCard title="Medicines" :count="prescription.items?.length ?? 0" flush>
            <div class="overflow-x-auto px-3 pb-3 pt-2">
                <table class="table-simple">
                    <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th class="text-right">Qty</th><th class="text-right">Dispensed</th></tr></thead>
                    <tbody>
                        <tr v-for="item in prescription.items" :key="item.id">
                            <td class="font-medium text-slate-800">{{ item.medicine?.name }} {{ item.medicine?.strength }}</td>
                            <td>{{ item.dosage || '—' }}</td>
                            <td>{{ item.frequency || '—' }}</td>
                            <td class="text-right tabular-nums">{{ item.quantity }}</td>
                            <td class="text-right tabular-nums">{{ item.dispensed_quantity }} / {{ item.quantity }}</td>
                        </tr>
                        <tr v-if="!prescription.items?.length"><td colspan="5" class="empty-note">No medicines on this prescription</td></tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>
        <div v-if="prescription.notes" class="card card-pad">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Notes</p>
            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ prescription.notes }}</p>
        </div>
    </div>
</template>
