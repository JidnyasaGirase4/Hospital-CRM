<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';

const tests = ref([]);
const showForm = ref(false);
const form = ref({ name: '', code: '', modality: 'X-Ray', price: '' });

async function load() {
    const { data } = await apiClient.get('/radiology-tests');
    tests.value = data.data;
}

async function addTest() {
    await apiClient.post('/radiology-tests', form.value);
    form.value = { name: '', code: '', modality: 'X-Ray', price: '' };
    showForm.value = false;
    load();
}

onMounted(load);
</script>

<template>
    <div class="space-y-4">
        <div class="toolbar">
            <RouterLink :to="{ name: 'radiology-orders.index' }" class="btn btn-primary">
                View Radiology Orders →
            </RouterLink>
            <button type="button" class="btn btn-sm btn-soft" @click="showForm = !showForm">
                {{ showForm ? 'Cancel' : '+ Add Test Type' }}
            </button>
        </div>
        <form v-if="showForm" class="card p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addTest">
            <input v-model="form.name" placeholder="Test name" required class="input input-sm" />
            <input v-model="form.code" placeholder="Code" required class="input input-sm" />
            <select v-model="form.modality" class="input input-sm">
                <option v-for="m in ['X-Ray', 'CT', 'MRI', 'Ultrasound', 'Other']" :key="m" :value="m">{{ m }}</option>
            </select>
            <div class="flex gap-2">
                <input v-model.number="form.price" type="number" step="0.01" placeholder="Price" class="input input-sm" />
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </div>
        </form>
        <div class="card p-5">
            <h3 class="card-title mb-3">Test Catalog</h3>
            <table class="table-simple">
                <thead><tr><th>Name</th><th>Code</th><th>Modality</th><th>Price</th></tr></thead>
                <tbody>
                    <tr v-for="t in tests" :key="t.id">
                        <td class="font-medium text-slate-800">{{ t.name }}</td>
                        <td>{{ t.code }}</td>
                        <td>{{ t.modality }}</td>
                        <td>{{ t.price || '—' }}</td>
                    </tr>
                    <tr v-if="tests.length === 0"><td colspan="4" class="empty-note">No test types yet</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
