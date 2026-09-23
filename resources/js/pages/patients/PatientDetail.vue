<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const data = ref(null);
const loading = ref(true);
const error = ref('');

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

function scalarEntries(item) {
    return Object.entries(item).filter(([, v]) => v === null || typeof v !== 'object');
}

async function load() {
    loading.value = true;
    try {
        const { data: res } = await apiClient.get(`/patients/${route.params.id}/360`);
        data.value = res.data;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load patient';
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else-if="data" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ data.profile.full_name }}</h2>
                    <p class="text-sm text-slate-500">MRN {{ data.profile.mrn }} · {{ data.profile.gender || '—' }} · {{ data.profile.mobile }}</p>
                </div>
                <RouterLink :to="{ name: 'patients.edit', params: { id: data.profile.id } }" class="text-sm text-brand-600 hover:underline">Edit</RouterLink>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 text-sm">
                <div><dt class="text-slate-400">DOB</dt><dd>{{ data.profile.dob || '—' }}</dd></div>
                <div><dt class="text-slate-400">Blood group</dt><dd>{{ data.profile.blood_group || '—' }}</dd></div>
                <div><dt class="text-slate-400">Email</dt><dd>{{ data.profile.email || '—' }}</dd></div>
                <div><dt class="text-slate-400">Allergies</dt><dd>{{ data.profile.allergies || '—' }}</dd></div>
                <div><dt class="text-slate-400">Insurance</dt><dd>{{ data.profile.insurance?.provider || '—' }}</dd></div>
                <div><dt class="text-slate-400">Emergency contact</dt><dd>{{ data.profile.emergency_contact?.name || '—' }}</dd></div>
            </dl>
        </div>

        <div v-for="(label, key) in SECTION_LABELS" :key="key" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">
                {{ label }} <span class="text-slate-400 font-normal">({{ (data[key] || []).length }})</span>
            </h3>
            <p v-if="!data[key] || data[key].length === 0" class="text-sm text-slate-400">None</p>
            <ul v-else class="divide-y divide-slate-100 text-sm">
                <li v-for="item in data[key]" :key="item.id" class="py-2 flex flex-wrap gap-x-4 gap-y-1">
                    <span v-for="[k, v] in scalarEntries(item)" :key="k" class="text-slate-600">
                        <span class="text-slate-400">{{ k }}:</span> {{ v ?? '—' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
