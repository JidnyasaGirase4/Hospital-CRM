<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { NAV_MODULES } from '../router/modules';

const route = useRoute();

const crumbs = computed(() => {
    if (route.path === '/') return [{ label: 'Dashboard' }];

    const module = NAV_MODULES
        .filter((m) => m.path !== '/' && route.path.startsWith(m.path))
        .sort((a, b) => b.path.length - a.path.length)[0];

    const result = [{ label: 'Dashboard', to: '/' }];
    if (module) result.push({ label: module.label, to: module.path });

    const title = route.meta.title;
    if (title && (!module || title !== module.label)) result.push({ label: title });

    return result;
});
</script>

<template>
    <nav class="text-xs text-slate-400 mb-0.5" aria-label="Breadcrumb">
        <template v-for="(c, i) in crumbs" :key="i">
            <RouterLink v-if="c.to && i < crumbs.length - 1" :to="c.to" class="hover:text-slate-600">{{ c.label }}</RouterLink>
            <span v-else class="text-slate-500">{{ c.label }}</span>
            <span v-if="i < crumbs.length - 1" class="mx-1.5">/</span>
        </template>
    </nav>
</template>
