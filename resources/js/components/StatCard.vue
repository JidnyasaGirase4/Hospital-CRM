<script setup>
import Icon from './Icon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: '—' },
    hint: { type: String, default: '' },
    icon: { type: String, default: 'chart' },
    color: { type: String, default: 'brand' },
});

const colorClasses = {
    brand: 'bg-brand-50 text-brand-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    red: 'bg-red-50 text-red-600',
    sky: 'bg-sky-50 text-sky-600',
};

const barClasses = {
    brand: 'from-brand-400 to-brand-600',
    emerald: 'from-emerald-400 to-emerald-600',
    amber: 'from-amber-400 to-amber-600',
    red: 'from-red-400 to-red-600',
    sky: 'from-sky-400 to-sky-600',
};
</script>

<template>
    <div class="relative bg-white rounded-2xl shadow-sm border border-slate-200 p-5 pt-6 flex items-start justify-between gap-4 hover:shadow-md hover:-translate-y-0.5 transition-all overflow-hidden">
        <span class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r" :class="barClasses[color] || barClasses.brand"></span>
        <div class="min-w-0">
            <p class="text-sm text-slate-500 truncate">{{ label }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 truncate">{{ value }}</p>
            <p v-if="hint" class="text-xs text-slate-400 mt-1 truncate">{{ hint }}</p>
        </div>
        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="colorClasses[color] || colorClasses.brand">
            <Icon :name="icon" :size="22" />
        </div>
    </div>
</template>
