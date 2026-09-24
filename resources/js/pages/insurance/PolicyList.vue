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
        <div class="toolbar">
            <div class="toolbar-filters">
                <RouterLink :to="{ name: 'insurance-claims.index' }" class="btn btn-sm btn-soft">View Claims →</RouterLink>
                <button type="button" class="btn btn-sm btn-neutral-soft" @click="showCompanyForm = !showCompanyForm">
                    {{ showCompanyForm ? 'Cancel' : 'Manage Companies' }}
                </button>
            </div>
            <PermissionGate permission="insurance.create">
                <RouterLink :to="{ name: 'insurance-policies.create' }" class="btn btn-primary">
                    + New Policy
                </RouterLink>
            </PermissionGate>
        </div>

        <div v-if="showCompanyForm" class="card p-4 space-y-3">
            <form class="grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addCompany">
                <input v-model="companyForm.name" placeholder="Company name" required class="input input-sm" />
                <input v-model="companyForm.contact_person" placeholder="Contact person" class="input input-sm" />
                <input v-model="companyForm.phone" placeholder="Phone" class="input input-sm" />
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </form>
            <ul class="text-sm text-slate-600 flex flex-wrap gap-3">
                <li v-for="c in companies" :key="c.id" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ c.name }}</li>
            </ul>
        </div>

        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }" />
    </div>
</template>
