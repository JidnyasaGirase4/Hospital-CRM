<script setup>
import { ref } from 'vue';
import apiClient from '../../api/client';

const email = ref('');
const message = ref('');
const error = ref('');
const sending = ref(false);

async function submit() {
    message.value = '';
    error.value = '';
    sending.value = true;
    try {
        const { data } = await apiClient.post('/auth/forgot-password', { email: email.value });
        message.value = data.message || 'If that email exists, a reset link has been sent.';
    } catch (e) {
        error.value = e.response?.data?.message || 'Something went wrong. Please try again.';
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4">
        <form class="w-full max-w-sm bg-white rounded-lg shadow p-8 space-y-4" @submit.prevent="submit">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Forgot Password</h1>
                <p class="text-sm text-slate-500">Enter your email to receive a reset link</p>
            </div>
            <div v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm font-medium text-emerald-700">{{ message }}</div>
            <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm font-medium text-red-700">{{ error }}</div>
            <div>
                <label class="label">Email</label>
                <input v-model="email" type="email" required class="input" />
            </div>
            <button type="submit" :disabled="sending" class="btn btn-primary w-full">
                {{ sending ? 'Sending…' : 'Send Reset Link' }}
            </button>
            <RouterLink :to="{ name: 'login' }" class="link block text-center text-sm">Back to login</RouterLink>
        </form>
    </div>
</template>
