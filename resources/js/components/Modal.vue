<script setup>
defineProps({
    title: { type: String, default: '' },
});
defineEmits(['close']);
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @click.self="$emit('close')">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-800">{{ title }}</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600 text-lg leading-none" @click="$emit('close')">&times;</button>
            </div>
            <div class="p-5">
                <slot />
            </div>
        </div>
    </div>
</template>
