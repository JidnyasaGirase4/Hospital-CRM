<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

const form = ref({ name: '', contact_person: '', phone: '', email: '', address: '', tax_id: '', is_active: true });
const errors = ref({});
const saving = ref(false);

async function loadSupplier() {
    const { data } = await apiClient.get(`/suppliers/${route.params.id}`);
    form.value = { ...form.value, ...data.data };
}

onMounted(() => {
    if (isEdit.value) loadSupplier();
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        if (isEdit.value) {
            await apiClient.put(`/suppliers/${route.params.id}`, form.value);
        } else {
            await apiClient.post('/suppliers', form.value);
        }
        router.push({ name: 'suppliers.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">{{ isEdit ? 'Edit Supplier' : 'New Supplier' }}</h2>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input v-model="form.name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name[0] }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Contact person</label>
                <input v-model="form.contact_person" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <input v-model="form.phone" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input v-model="form.email" type="email" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tax ID</label>
                <input v-model="form.tax_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
            <textarea v-model="form.address" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>
        <label v-if="isEdit" class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_active" type="checkbox" /> Active
        </label>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'suppliers.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
