<script setup>
defineProps({
    title: { type: String, required: true },
    count: { type: [Number, String], default: null },
    // Flush removes the body padding so a table can run edge to edge.
    flush: { type: Boolean, default: false },
});
</script>

<template>
    <section class="card overflow-hidden">
        <header class="card-header">
            <h3 class="card-title">
                {{ title }}
                <span v-if="count !== null" class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">{{ count }}</span>
            </h3>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 print:hidden">
                <slot name="actions" />
            </div>
        </header>
        <div :class="flush ? '' : 'card-body'">
            <slot />
        </div>
    </section>
</template>
