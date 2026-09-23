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
            <div v-if="message" class="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-3 py-2">{{ message }}</div>
            <div v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">{{ error }}</div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input v-model="email" type="email" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" />
            </div>
            <button type="submit" :disabled="sending" class="w-full bg-brand-600 text-white rounded px-3 py-2 text-sm font-medium hover:bg-brand-700 disabled:opacity-50">
                {{ sending ? 'Sending…' : 'Send Reset Link' }}
            </button>
            <RouterLink :to="{ name: 'login' }" class="block text-center text-sm text-brand-600 hover:underline">Back to login</RouterLink>
        </form>
    </div>
</template>
