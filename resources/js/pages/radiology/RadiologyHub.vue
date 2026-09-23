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
        <div class="flex items-center justify-between">
            <RouterLink :to="{ name: 'radiology-orders.index' }" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all">
                View Radiology Orders →
            </RouterLink>
            <button type="button" class="text-sm text-brand-600 hover:underline" @click="showForm = !showForm">
                {{ showForm ? 'Cancel' : '+ Add Test Type' }}
            </button>
        </div>
        <form v-if="showForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addTest">
            <input v-model="form.name" placeholder="Test name" required class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <input v-model="form.code" placeholder="Code" required class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <select v-model="form.modality" class="border border-slate-300 rounded px-2 py-1.5 text-sm">
                <option v-for="m in ['X-Ray', 'CT', 'MRI', 'Ultrasound', 'Other']" :key="m" :value="m">{{ m }}</option>
            </select>
            <div class="flex gap-2">
                <input v-model.number="form.price" type="number" step="0.01" placeholder="Price" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm" />
                <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add</button>
            </div>
        </form>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Test Catalog</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500"><tr><th>Name</th><th>Code</th><th>Modality</th><th>Price</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="t in tests" :key="t.id">
                        <td class="py-1.5">{{ t.name }}</td>
                        <td>{{ t.code }}</td>
                        <td>{{ t.modality }}</td>
                        <td>{{ t.price || '—' }}</td>
                    </tr>
                    <tr v-if="tests.length === 0"><td colspan="4" class="text-slate-400 py-3 text-center">No test types yet</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
