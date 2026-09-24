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
            <div v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm font-medium text-emerald-700">{{ message }}</div>
            <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm font-medium text-red-700">{{ error }}</div>
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" required class="input" />
            </div>
            <div>
                <label class="label">Reset token</label>
                <input v-model="form.token" required class="input" />
            </div>
            <div>
                <label class="label">New password</label>
                <input v-model="form.password" type="password" required class="input" />
            </div>
            <div>
                <label class="label">Confirm new password</label>
                <input v-model="form.password_confirmation" type="password" required class="input" />
            </div>
            <button type="submit" :disabled="saving" class="btn btn-primary w-full">
                {{ saving ? 'Resetting…' : 'Reset Password' }}
            </button>
            <RouterLink :to="{ name: 'login' }" class="link block text-center text-sm">Back to login</RouterLink>
        </form>
    </div>
</template>
