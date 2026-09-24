<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';

const tests = ref([]);
const showForm = ref(false);
const form = ref({ name: '', code: '', sample_type: '', unit: '', reference_range: '', price: '' });

async function load() {
    const { data } = await apiClient.get('/lab-tests');
    tests.value = data.data;
}

async function addTest() {
    await apiClient.post('/lab-tests', form.value);
    form.value = { name: '', code: '', sample_type: '', unit: '', reference_range: '', price: '' };
    showForm.value = false;
    load();
}

onMounted(load);
</script>

<template>
    <div class="space-y-4">
        <div class="toolbar">
            <RouterLink :to="{ name: 'lab-orders.index' }" class="btn btn-primary">
                View Lab Orders →
            </RouterLink>
            <button type="button" class="btn btn-sm btn-soft" @click="showForm = !showForm">
                {{ showForm ? 'Cancel' : '+ Add Test Type' }}
            </button>
        </div>
        <form v-if="showForm" class="card p-4 grid grid-cols-1 sm:grid-cols-3 gap-2" @submit.prevent="addTest">
            <input v-model="form.name" placeholder="Test name" required class="input input-sm" />
            <input v-model="form.code" placeholder="Code" required class="input input-sm" />
            <input v-model="form.sample_type" placeholder="Sample type" class="input input-sm" />
            <input v-model="form.unit" placeholder="Unit" class="input input-sm" />
            <input v-model="form.reference_range" placeholder="Reference range" class="input input-sm" />
            <div class="flex gap-2">
                <input v-model.number="form.price" type="number" step="0.01" placeholder="Price" class="input input-sm" />
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </div>
        </form>
        <div class="card p-5">
            <h3 class="card-title mb-3">Test Catalog</h3>
            <table class="table-simple">
                <thead><tr><th>Name</th><th>Code</th><th>Sample</th><th>Reference Range</th><th>Price</th></tr></thead>
                <tbody>
                    <tr v-for="t in tests" :key="t.id">
                        <td class="font-medium text-slate-800">{{ t.name }}</td>
                        <td>{{ t.code }}</td>
                        <td>{{ t.sample_type || '—' }}</td>
                        <td>{{ t.reference_range || '—' }}</td>
                        <td>{{ t.price || '—' }}</td>
                    </tr>
                    <tr v-if="tests.length === 0"><td colspan="5" class="empty-note">No test types yet</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
