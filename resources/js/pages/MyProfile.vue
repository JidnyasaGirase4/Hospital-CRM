<script setup>
import PageLoader from '../components/PageLoader.vue';
import { ref, onMounted } from 'vue';
import apiClient from '../api/client';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';

const auth = useAuthStore();
const ui = useUiStore();

const form = ref({ name: '', email: '', mobile: '', password: '' });
const errors = ref({});
const saving = ref(false);
const loading = ref(true);

async function load() {
    loading.value = true;
    const { data } = await apiClient.get(`/users/${auth.user.id}`);
    form.value = { name: data.data.name, email: data.data.email, mobile: data.data.mobile || '', password: '' };
    loading.value = false;
}

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (!payload.password) delete payload.password;
        const { data } = await apiClient.put(`/users/${auth.user.id}`, payload);
        auth.setSession(auth.token, { ...auth.user, ...data.data });
        ui.toast('Profile updated', 'success');
        form.value.password = '';
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>

<template>
    <PageLoader v-if="loading" />
    <form v-else class="card mx-auto max-w-2xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">My Profile</h2>

        <div>
            <label class="label">Name</label>
            <input v-model="form.name" class="input" />
            <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
        </div>
        <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" />
            <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
        </div>
        <div>
            <label class="label">Mobile</label>
            <input v-model="form.mobile" class="input" />
        </div>
        <div>
            <label class="label">New password (optional)</label>
            <input v-model="form.password" type="password" class="input" />
            <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save Changes' }}
            </button>
        </div>
    </form>
</template>
