<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import Icon from '../../components/Icon.vue';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref('admin@gmail.com');
const password = ref('');
const error = ref('');
const loading = ref(false);
const showPassword = ref(false);

const highlights = [
    { icon: 'user', label: 'Patient 360°', desc: 'One record, full history' },
    { icon: 'banknote', label: 'Billing & Insurance', desc: 'End-to-end revenue cycle' },
    { icon: 'archive', label: 'Pharmacy & Inventory', desc: 'Live stock across the hospital' },
    { icon: 'beaker', label: 'Lab & Radiology', desc: 'Order to result, tracked' },
];

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.login(email.value, password.value);
        router.push(route.query.redirect || { name: 'dashboard' });
    } catch (e) {
        error.value = e.response?.data?.message || 'Login failed. Check your credentials.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen flex">
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-orange-500 via-rose-600 to-slate-900 text-white p-12 flex-col justify-between">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-80 h-80 bg-rose-400/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/3 right-10 w-40 h-40 bg-amber-300/10 rounded-full blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-16">
                    <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center backdrop-blur">
                        <Icon name="heart" :size="20" />
                    </div>
                    <span class="text-lg font-semibold">Hospital CRM</span>
                </div>
                <h1 class="text-3xl font-bold leading-tight max-w-md">One connected system for your entire hospital.</h1>
                <p class="text-orange-100 mt-3 max-w-sm">Registration to discharge, every department works from the same patient record.</p>
            </div>

            <div class="relative z-10 grid grid-cols-2 gap-4">
                <div v-for="h in highlights" :key="h.label" class="bg-white/10 backdrop-blur rounded-xl p-4 hover:bg-white/15 transition-colors">
                    <Icon :name="h.icon" :size="20" class="text-orange-200 mb-2" />
                    <p class="text-sm font-semibold">{{ h.label }}</p>
                    <p class="text-xs text-orange-100/80 mt-0.5">{{ h.desc }}</p>
                </div>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center bg-slate-50 px-4">
            <form class="w-full max-w-sm bg-white rounded-2xl shadow-xl shadow-slate-900/5 border border-slate-100 p-8 space-y-5" @submit.prevent="submit">
                <div class="lg:hidden flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-rose-600 flex items-center justify-center">
                        <Icon name="heart" :size="16" class="text-white" />
                    </div>
                    <span class="font-semibold text-slate-800">Hospital CRM</span>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Welcome back</h1>
                    <p class="text-sm text-slate-500">Sign in to your staff account</p>
                </div>
                <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm font-medium text-red-700">
                    {{ error }}
                </div>
                <div>
                    <label class="label">Email</label>
                    <input v-model="email" type="email" required class="input focus:border-rose-500 focus:ring-rose-500/15" />
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="label">Password</label>
                        <RouterLink :to="{ name: 'forgot-password' }" class="text-xs font-semibold text-rose-600 hover:underline">Forgot password?</RouterLink>
                    </div>
                    <div class="relative">
                        <input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            class="input pr-10 focus:border-rose-500 focus:ring-rose-500/15"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 w-9 flex items-center justify-center text-slate-400 hover:text-slate-600"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            tabindex="-1"
                            @click="showPassword = !showPassword"
                        >
                            <Icon :name="showPassword ? 'eye-off' : 'eye'" :size="18" />
                        </button>
                    </div>
                </div>
                <button type="submit" :disabled="loading" class="btn w-full bg-gradient-to-r from-orange-500 to-rose-600 py-3 text-white shadow-md shadow-rose-600/30 hover:from-orange-600 hover:to-rose-700">
                    {{ loading ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>
        </div>
    </div>
</template>
