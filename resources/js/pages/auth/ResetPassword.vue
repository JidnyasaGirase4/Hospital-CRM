<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const router = useRouter();

const form = ref({
    email: route.query.email || '',
    token: route.query.token || '',
    password: '',
    password_confirmation: '',
});
const message = ref('');
const error = ref('');
const saving = ref(false);

async function submit() {
    message.value = '';
    error.value = '';
    saving.value = true;
    try {
        const { data } = await apiClient.post('/auth/reset-password', form.value);
        message.value = data.message || 'Password reset successfully.';
        setTimeout(() => router.push({ name: 'login' }), 1500);
    } catch (e) {
        error.value = e.response?.data?.message || 'Reset failed. The link may have expired.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4">
        <form class="w-full max-w-sm bg-white rounded-lg shadow p-8 space-y-4" @submit.prevent="submit">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Reset Password</h1>
                <p class="text-sm text-slate-500">Choose a new password</p>
            </div>
            <div v-if="message" class="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-3 py-2">{{ message }}</div>
            <div v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">{{ error }}</div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input v-model="form.email" type="email" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Reset token</label>
                <input v-model="form.token" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                <input v-model="form.password" type="password" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
                <input v-model="form.password_confirmation" type="password" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <button type="submit" :disabled="saving" class="w-full bg-brand-600 text-white rounded px-3 py-2 text-sm font-medium hover:bg-brand-700 disabled:opacity-50">
                {{ saving ? 'Resetting…' : 'Reset Password' }}
            </button>
            <RouterLink :to="{ name: 'login' }" class="block text-center text-sm text-brand-600 hover:underline">Back to login</RouterLink>
        </form>
    </div>
</template>
