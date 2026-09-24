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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">{{ isEdit ? 'Edit Supplier' : 'New Supplier' }}</h2>

        <div>
            <label class="label">Name</label>
            <input v-model="form.name" class="input" />
            <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Contact person</label>
                <input v-model="form.contact_person" class="input" />
            </div>
            <div>
                <label class="label">Phone</label>
                <input v-model="form.phone" class="input" />
            </div>
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" class="input" />
            </div>
            <div>
                <label class="label">Tax ID</label>
                <input v-model="form.tax_id" class="input" />
            </div>
        </div>
        <div>
            <label class="label">Address</label>
            <textarea v-model="form.address" rows="2" class="input"></textarea>
        </div>
        <label v-if="isEdit" class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_active" type="checkbox" /> Active
        </label>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'suppliers.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
