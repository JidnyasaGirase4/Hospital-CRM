<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import { useUiStore } from '../../stores/ui';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import { formatDate, doctorName } from '../../utils/format';

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

const vitalEntries = computed(() => Object.entries(visit.value?.vitals || {}).filter(([, v]) => v !== null && v !== ''));

onMounted(load);
</script>

<template>
    <PageLoader v-if="loading" />
    <div v-else-if="visit" class="space-y-5">
        <PageHero
            :title="visit.patient?.name ?? 'OPD Visit'"
            :status="visit.status"
            :subtitle="`MRN ${visit.patient?.mrn ?? '—'} · ${doctorName(visit.doctor?.name)} · ${formatDate(visit.visit_date)}`"
            initials
        >
            <template #actions>
                <PermissionGate permission="opd.update">
                    <button v-if="visit.status !== 'closed'" type="button" class="btn btn-danger-soft" @click="closeVisit">Close Visit</button>
                </PermissionGate>
            </template>
        </PageHero>

        <SectionCard title="Visit Notes">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="label">Symptoms</label>
                    <textarea v-model="visit.symptoms" rows="3" class="input"></textarea>
                </div>
                <div>
                    <label class="label">Diagnosis</label>
                    <textarea v-model="visit.diagnosis" rows="3" class="input"></textarea>
                </div>
                <div>
                    <label class="label">Notes</label>
                    <textarea v-model="visit.notes" rows="3" class="input"></textarea>
                </div>
                <div>
                    <label class="label">Follow-up date</label>
                    <input v-model="visit.follow_up_date" type="date" class="input" />
                </div>
            </div>
            <PermissionGate permission="opd.update">
                <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                    <button type="button" :disabled="saving" class="btn btn-primary" @click="save">
                        {{ saving ? 'Saving…' : 'Save Changes' }}
                    </button>
                </div>
            </PermissionGate>
        </SectionCard>

        <SectionCard v-if="visit.vitals" title="Vitals">
            <dl v-if="vitalEntries.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div v-for="[k, v] in vitalEntries" :key="k" class="kv"><dt>{{ k.replace(/_/g, ' ') }}</dt><dd>{{ v }}</dd></div>
            </dl>
            <p v-else class="empty-note !py-3">No vitals recorded</p>
        </SectionCard>
    </div>
</template>
