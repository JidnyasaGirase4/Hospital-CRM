<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Icon from './Icon.vue';

const auth = useAuthStore();
const router = useRouter();
const open = ref(false);

const initials = computed(() => {
    const name = auth.user?.name || '';
    return name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('') || '?';
});

function toggle() {
    open.value = !open.value;
}
function close() {
    open.value = false;
}

function viewProfile() {
    close();
    router.push({ name: 'profile' });
}

async function logout() {
    close();
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="relative">
        <button type="button" class="w-full flex items-center gap-3 text-left rounded-lg px-2 py-1.5 hover:bg-slate-50 transition-colors" @click="toggle">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-white flex items-center justify-center text-xs font-semibold shrink-0">
                {{ initials }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-slate-800 text-sm truncate">{{ auth.user?.name }}</p>
                <p class="text-slate-400 text-xs truncate">{{ auth.user?.roles?.[0]?.name || '—' }}</p>
            </div>
            <Icon name="chevron" :size="14" class="text-slate-400 shrink-0 transition-transform" :class="{ 'rotate-180': open }" />
        </button>
        <div v-if="open" class="fixed inset-0 z-10" @click="close"></div>
        <div v-if="open" class="absolute bottom-full left-0 mb-2 w-full bg-white rounded-lg shadow-lg border border-slate-200 py-1 text-sm text-slate-700 z-20 overflow-hidden">
            <button type="button" class="w-full text-left px-4 py-2 hover:bg-slate-50 flex items-center gap-2" @click="viewProfile">
                <Icon name="user" :size="16" class="text-slate-400" /> My Profile
            </button>
            <button type="button" class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2" @click="logout">
                <Icon name="logout" :size="16" /> Log out
            </button>
        </div>
    </div>
</template>
