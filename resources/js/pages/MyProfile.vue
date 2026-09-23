<script setup>
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
    <div v-if="loading" class="text-slate-400">Loading…</div>
    <form v-else class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">My Profile</h2>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input v-model="form.name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name[0] }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email[0] }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mobile</label>
            <input v-model="form.mobile" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">New password (optional)</label>
            <input v-model="form.password" type="password" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password[0] }}</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save Changes' }}
            </button>
        </div>
    </form>
</template>
