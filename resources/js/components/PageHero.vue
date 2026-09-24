<script setup>
import { computed } from 'vue';
import Badge from './Badge.vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    status: { type: [String, Boolean], default: '' },
    initials: { type: Boolean, default: false },
});

const avatar = computed(() =>
    props.title.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0]?.toUpperCase()).join('')
);
</script>

<template>
    <div class="hero">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                <div v-if="initials" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-lg font-extrabold text-white shadow-md shadow-brand-600/30">
                    {{ avatar }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="hero-title truncate">{{ title }}</h2>
                        <Badge v-if="status !== ''" :value="status" />
                        <slot name="badges" />
                    </div>
                    <p v-if="subtitle" class="hero-sub">{{ subtitle }}</p>
                    <slot name="meta" />
                </div>
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 print:hidden">
                <slot name="actions" />
            </div>
        </div>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
