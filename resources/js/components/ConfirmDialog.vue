<script setup>
import { useUiStore } from '../stores/ui';
import Modal from './Modal.vue';

const ui = useUiStore();
</script>

<template>
    <Modal v-if="ui.confirmState" :title="ui.confirmState.title" @close="ui.resolveConfirm(false)">
        <p class="text-sm text-slate-600 mb-5 whitespace-pre-line">{{ ui.confirmState.message }}</p>
        <div class="flex justify-end gap-3">
            <button type="button" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900" @click="ui.resolveConfirm(false)">Cancel</button>
            <button
                type="button"
                class="px-4 py-2 text-sm text-white rounded"
                :class="ui.confirmState.danger ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-600 hover:bg-brand-700'"
                @click="ui.resolveConfirm(true)"
            >{{ ui.confirmState.confirmLabel }}</button>
        </div>
    </Modal>
</template>
