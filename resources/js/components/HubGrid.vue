<script setup>
import { computed } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    links: { type: Array, required: true },
});

// Avoid a lonely orphan card on the last row.
const cols = computed(() => (props.links.length % 3 === 0 ? 'xl:grid-cols-3' : 'xl:grid-cols-2'));
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" :class="cols">
        <RouterLink v-for="link in links" :key="link.to" :to="{ name: link.to }" class="hub-card group">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-md shadow-brand-600/25 transition-transform group-hover:scale-105">
                <Icon :name="link.icon || 'clipboard'" :size="22" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-base font-bold text-slate-900">{{ link.label }}</span>
                <span class="mt-0.5 block text-sm text-slate-500">{{ link.desc }}</span>
            </span>
            <Icon name="arrow-left" :size="18" class="mt-1 rotate-180 text-slate-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand-500" />
        </RouterLink>
    </div>
</template>
