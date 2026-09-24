<script setup>
import { useUiStore } from '../stores/ui';
import Modal from './Modal.vue';

const ui = useUiStore();
</script>

<template>
    <Modal v-if="ui.confirmState" :title="ui.confirmState.title" @close="ui.resolveConfirm(false)">
        <p class="mb-6 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ ui.confirmState.message }}</p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-secondary" @click="ui.resolveConfirm(false)">Cancel</button>
            <button
                type="button"
                class="btn"
                :class="ui.confirmState.danger ? 'btn-danger' : 'btn-primary'"
                @click="ui.resolveConfirm(true)"
            >{{ ui.confirmState.confirmLabel }}</button>
        </div>
    </Modal>
</template>
