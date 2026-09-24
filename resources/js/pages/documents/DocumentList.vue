<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '../../api/client';
import DataTable from '../../components/DataTable.vue';
import PermissionGate from '../../components/PermissionGate.vue';
import SearchSelect from '../../components/SearchSelect.vue';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const rows = ref([]);
const pagination = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);

const uploadPatientId = ref(null);
const uploadForm = ref({ title: '', category: 'other' });
const fileInput = ref(null);
const uploading = ref(false);
const errors = ref({});

const columns = [
    { key: 'title', label: 'Title' },
    { key: 'category', label: 'Category' },
    { key: 'original_filename', label: 'File' },
    { key: 'uploaded_by', label: 'Uploaded By', format: (r) => r.uploaded_by?.name ?? '—' },
    { key: 'created_at', label: 'Date' },
];

async function fetchPatients(q) {
    const { data } = await apiClient.get('/patients', { params: { search: q, per_page: 10 } });
    return data.data.map((p) => ({ id: p.id, label: `${p.full_name} (${p.mrn})` }));
}

async function load() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/documents', { params: { page: page.value, per_page: 15 } });
        rows.value = data.data;
        pagination.value = data.meta?.pagination ?? null;
    } finally {
        loading.value = false;
    }
}

async function upload() {
    errors.value = {};
    if (!fileInput.value?.files?.[0]) {
        errors.value = { file: ['Please choose a file'] };
        return;
    }
    uploading.value = true;
    const formData = new FormData();
    formData.append('patient_id', uploadPatientId.value);
    formData.append('title', uploadForm.value.title);
    formData.append('category', uploadForm.value.category);
    formData.append('file', fileInput.value.files[0]);
    try {
        await apiClient.post('/documents', formData);
        showForm.value = false;
        uploadForm.value = { title: '', category: 'other' };
        uploadPatientId.value = null;
        if (fileInput.value) fileInput.value.value = '';
        ui.toast('Document uploaded', 'success');
        load();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        uploading.value = false;
    }
}

async function download(row) {
    const response = await apiClient.get(`/documents/${row.id}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', row.original_filename || row.title);
    document.body.appendChild(link);
    link.click();
    link.remove();
}

async function remove(row) {
    if (!(await ui.confirm({ title: 'Delete Document', message: `Delete "${row.title}"?`, danger: true }))) return;
    await apiClient.delete(`/documents/${row.id}`);
    ui.toast('Document deleted', 'success');
    load();
}

onMounted(load);
watch(page, load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <PermissionGate permission="documents.view">
                <button type="button" class="btn btn-primary" @click="showForm = !showForm">
                    {{ showForm ? 'Cancel' : '+ Upload Document' }}
                </button>
            </PermissionGate>
        </div>
        <form v-if="showForm" class="card p-4 space-y-3" @submit.prevent="upload">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <SearchSelect v-model="uploadPatientId" :fetcher="fetchPatients" placeholder="Search patient…" />
                <input v-model="uploadForm.title" placeholder="Title" class="input input-sm" />
                <select v-model="uploadForm.category" class="input input-sm">
                    <option v-for="c in ['prescription', 'lab-report', 'radiology-report', 'discharge-summary', 'insurance', 'consent', 'other']" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
            <input ref="fileInput" type="file" class="text-sm" />
            <p v-if="errors.file" class="field-error">{{ errors.file[0] }}</p>
            <button type="submit" :disabled="uploading" class="btn btn-primary btn-sm">
                {{ uploading ? 'Uploading…' : 'Upload' }}
            </button>
        </form>
        <DataTable :columns="columns" :rows="rows" :loading="loading" :pagination="pagination" @page-change="(p) => { page = p; }">
            <template #actions="{ row }">
                <div class="flex gap-2 justify-end text-xs">
                    <PermissionGate permission="documents.download">
                        <button type="button" class="btn btn-sm btn-soft" @click="download(row)">Download</button>
                    </PermissionGate>
                    <PermissionGate permission="documents.delete">
                        <button type="button" class="btn btn-sm btn-danger-soft" @click="remove(row)">Delete</button>
                    </PermissionGate>
                </div>
            </template>
        </DataTable>
    </div>
</template>
