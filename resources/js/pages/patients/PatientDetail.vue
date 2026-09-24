<script setup>
import PageLoader from '../../components/PageLoader.vue';
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import PageHero from '../../components/PageHero.vue';
import SectionCard from '../../components/SectionCard.vue';
import Badge from '../../components/Badge.vue';
import Icon from '../../components/Icon.vue';
import { formatDate, autoFormat, isMoneyKey } from '../../utils/format';

const route = useRoute();
const auth = useAuthStore();
const data = ref(null);
const loading = ref(true);
const error = ref('');
// True when the user may see the patient's profile but not the full clinical history (no patients.view-360).
const restricted = ref(false);

const SECTION_LABELS = {
    appointments: 'Appointments',
    opd_visits: 'OPD Visits',
    consultations: 'Consultations',
    diagnoses: 'Diagnoses',
    prescriptions: 'Prescriptions',
    pharmacy_sales: 'Pharmacy Sales',
    lab_orders: 'Lab Orders',
    radiology_orders: 'Radiology Orders',
    admissions: 'Admissions',
    bills: 'Bills',
    payments: 'Payments',
    insurance_policies: 'Insurance Policies',
    documents: 'Documents',
};

const HIDDEN_COLUMN = /(^id$|_id$|^created_at$|^updated_at$|^deleted_at$)/;

function humanize(key) {
    return key.replace(/_/g, ' ');
}

// Every section is an array of flat records; build a table column per scalar field
// that actually has a value in at least one row (keeps the tables narrow).
function columnsFor(rows) {
    const cols = [];
    for (const row of rows) {
        for (const [k, v] of Object.entries(row)) {
            if ((v === null || typeof v !== 'object') && v !== null && v !== '' && !HIDDEN_COLUMN.test(k) && !cols.includes(k)) cols.push(k);
        }
    }
    return cols;
}

const sections = computed(() =>
    Object.entries(SECTION_LABELS).map(([key, label]) => {
        const rows = data.value?.[key] || [];
        return { key, label, rows, columns: columnsFor(rows) };
    })
);
const filledSections = computed(() => sections.value.filter((s) => s.rows.length));
const emptySections = computed(() => sections.value.filter((s) => !s.rows.length));

async function load() {
    loading.value = true;
    try {
        if (auth.can('patients.view-360')) {
            const { data: res } = await apiClient.get(`/patients/${route.params.id}/360`);
            data.value = res.data;
        } else {
            const { data: res } = await apiClient.get(`/patients/${route.params.id}`);
            data.value = { profile: res.data };
            restricted.value = true;
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load patient';
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <PageLoader v-if="loading" />
    <div v-else-if="error" class="card card-pad text-sm font-medium text-red-600">{{ error }}</div>
    <div v-else-if="data" class="space-y-5">
        <PageHero
            :title="data.profile.full_name"
            :subtitle="`MRN ${data.profile.mrn} · ${data.profile.gender || '—'} · ${data.profile.mobile || 'No mobile'}`"
            initials
        >
            <template #actions>
                <RouterLink :to="{ name: 'patients.report', params: { id: data.profile.id } }" class="btn btn-secondary"><Icon name="document" :size="16" /> Complete Report</RouterLink>
                <RouterLink v-if="auth.can('patients.update')" :to="{ name: 'patients.edit', params: { id: data.profile.id } }" class="btn btn-primary"><Icon name="pencil" :size="16" /> Edit</RouterLink>
            </template>
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="kv"><dt>Date of birth</dt><dd>{{ formatDate(data.profile.dob) }}</dd></div>
                <div class="kv"><dt>Blood group</dt><dd>{{ data.profile.blood_group || '—' }}</dd></div>
                <div class="kv"><dt>Email</dt><dd>{{ data.profile.email || '—' }}</dd></div>
                <div class="kv"><dt>Allergies</dt><dd :class="data.profile.allergies ? 'text-red-600' : ''">{{ data.profile.allergies || '—' }}</dd></div>
                <div class="kv"><dt>Insurance</dt><dd>{{ data.profile.insurance?.provider || '—' }}</dd></div>
                <div class="kv"><dt>Emergency contact</dt><dd>{{ data.profile.emergency_contact?.name || '—' }}</dd></div>
            </dl>
        </PageHero>

        <div v-if="restricted" class="card card-pad text-sm font-medium text-slate-600">
            Your role can view this patient's profile but not their clinical history.
        </div>

        <div v-if="filledSections.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            <a v-for="s in filledSections" :key="s.key" :href="`#sec-${s.key}`" class="card px-4 py-3 transition-all hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-2xl font-extrabold text-slate-900">{{ s.rows.length }}</p>
                <p class="truncate text-xs font-semibold text-slate-500">{{ s.label }}</p>
            </a>
        </div>

        <SectionCard v-for="s in filledSections" :id="`sec-${s.key}`" :key="s.key" :title="s.label" :count="s.rows.length" flush class="scroll-mt-4">
            <div class="overflow-x-auto px-3 pb-3 pt-2">
                <table class="table-simple">
                    <thead><tr><th v-for="c in s.columns" :key="c" :class="isMoneyKey(c) ? 'text-right' : ''">{{ humanize(c) }}</th></tr></thead>
                    <tbody>
                        <tr v-for="(item, i) in s.rows" :key="item.id ?? i">
                            <td v-for="c in s.columns" :key="c" :class="isMoneyKey(c) ? 'text-right tabular-nums' : ''">
                                <Badge v-if="c === 'status'" :value="item[c]" />
                                <template v-else>{{ autoFormat(c, item[c]) }}</template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>

        <div v-if="!restricted && emptySections.length" class="card card-pad">
            <p class="card-title mb-2.5">Nothing recorded yet</p>
            <div class="flex flex-wrap gap-2">
                <span v-for="s in emptySections" :key="s.key" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ s.label }}</span>
            </div>
        </div>
    </div>
</template>
