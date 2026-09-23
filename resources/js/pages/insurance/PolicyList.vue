<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '../../api/client';
import DataTable from '../../components/DataTable.vue';
import PermissionGate from '../../components/PermissionGate.vue';

const rows = ref([]);
const pagination = ref(null);
const loading = ref(false);
const page = ref(1);

const companies = ref([]);
const showCompanyForm = ref(false);
const companyForm = ref({ name: '', contact_person: '', phone: '', email: '' });

const columns = [
    { key: 'policy_number', label: 'Policy #' },
    { key: 'insurance_company', label: 'Company', format: (r) => r.insurance_company?.name ?? '—' },
    { key: 'patient_id', label: 'Patient ID' },
    { key: 'valid_till', label: 'Valid Till' },
    { key: 'coverage_amount', label: 'Coverage' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/insurance-policies', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function loadCompanies() {
    const { data } = await apiClient.get('/insurance-companies');
    companies.value = data.data;
}

async function addCompany() {
    await apiClient.post('/insurance-companies', companyForm.value);
    companyForm.value = { name: '', contact_person: '', phone: '', email: '' };
    showCompanyForm.value = false;
    loadCompanies();
}

onMounted(() => {
    load();
    loadCompanies();
});
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <RouterLink :to="{ name: 'insurance-claims.index' }" class="text-sm text-brand-600 hover:underline">View Claims →</RouterLink>
                <button type="button" class="text-sm text-slate-500 hover:text-slate-800" @click="showCompanyForm = !showCompanyForm">
                    {{ showCompanyForm ? 'Cancel' : 'Manage Companies' }}
                </button>
            </div>
            <PermissionGate permission="insurance.create">
                <RouterLink :to="{ name: 'insurance-policies.create' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                    + New Policy
                </RouterLink>
            </PermissionGate>
        </div>

        <div v-if="showCompanyForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 space-y-3">
            <form class="grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addCompany">
                <input v-model="companyForm.name" placeholder="Company name" required class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model="companyForm.contact_person" placeholder="Contact person" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <input v-model="companyForm.phone" placeholder="Phone" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add</button>
            </form>
            <ul class="text-sm text-slate-600 flex flex-wrap gap-3">
                <li v-for="c in companies" :key="c.id" class="border border-slate-200 rounded px-2 py-1">{{ c.name }}</li>
            </ul>
        </div>

        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }" />
    </div>
</template>
