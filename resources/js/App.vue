<script setup>
import ToastStack from './components/ToastStack.vue';
import ConfirmDialog from './components/ConfirmDialog.vue';
import PromptDialog from './components/PromptDialog.vue';
</script>

<template>
    <RouterView />
    <ToastStack />
    <ConfirmDialog />
    <PromptDialog />
</template>
