<script setup>
defineProps({
    moduleLabel: { type: String, default: 'This module' },
});
</script>

<template>
    <div class="bg-white rounded-lg border border-dashed border-slate-300 p-10 text-center">
        <p class="text-slate-500">{{ moduleLabel }} is built on the backend API but doesn't have a dedicated screen here yet.</p>
        <p class="text-slate-400 text-sm mt-1">This is next in line to be wired up.</p>
    </div>
</template>
