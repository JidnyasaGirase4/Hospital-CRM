<script setup>
defineProps({
    title: { type: String, default: '' },
});
defineEmits(['close']);
</script>

<template>
    <div class="print:hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" @click.self="$emit('close')">
        <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-bold text-slate-900">{{ title }}</h3>
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-xl leading-none text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700" aria-label="Close" @click="$emit('close')">&times;</button>
            </div>
            <div class="p-6">
                <slot />
            </div>
        </div>
    </div>
</template>
