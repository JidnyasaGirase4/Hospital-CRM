<script setup>
import { useAuthStore } from '../stores/auth';

defineProps({
    permission: { type: String, default: null },
});

const auth = useAuthStore();
</script>

<template>
    <slot v-if="auth.can(permission)" />
</template>
