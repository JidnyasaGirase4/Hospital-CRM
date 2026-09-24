<script setup>
import { useUiStore } from '../stores/ui';

const ui = useUiStore();

const colors = {
    success: 'bg-emerald-600',
    error: 'bg-red-600',
    info: 'bg-slate-800',
};
</script>

<template>
    <div class="print:hidden fixed right-4 top-4 z-[100] w-80 max-w-[calc(100vw-2rem)] space-y-2.5">
        <TransitionGroup name="toast">
            <div
                v-for="t in ui.toasts"
                :key="t.id"
                :class="colors[t.type] || colors.info"
                class="flex items-start justify-between gap-3 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-xl shadow-slate-900/20"
            >
                <span>{{ t.message }}</span>
                <button type="button" class="text-white/70 hover:text-white leading-none" @click="ui.dismissToast(t.id)">&times;</button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
