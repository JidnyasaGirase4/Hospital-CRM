<script setup>
import { ref, watch } from 'vue';
import { useUiStore } from '../stores/ui';
import Modal from './Modal.vue';

const ui = useUiStore();
const values = ref({});

watch(
    () => ui.promptState,
    (state) => {
        if (!state) return;
        const initial = {};
        for (const f of state.fields) initial[f.key] = f.value ?? '';
        values.value = initial;
    },
    { immediate: true }
);

function submit() {
    for (const f of ui.promptState.fields) {
        if (f.required && !values.value[f.key]) return;
    }
    ui.resolvePrompt({ ...values.value });
}

function cancel() {
    ui.resolvePrompt(null);
}
</script>

<template>
    <Modal v-if="ui.promptState" :title="ui.promptState.title" @close="cancel">
        <p v-if="ui.promptState.message" class="text-sm text-slate-600 mb-3">{{ ui.promptState.message }}</p>
        <form class="space-y-3" @submit.prevent="submit">
            <div v-for="f in ui.promptState.fields" :key="f.key">
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ f.label }}</label>
                <select v-if="f.type === 'select'" v-model="values[f.key]" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                    <option v-if="!f.required" value="">—</option>
                    <option v-for="opt in f.options" :key="opt.value ?? opt" :value="opt.value ?? opt">{{ opt.label ?? opt }}</option>
                </select>
                <textarea
                    v-else-if="f.type === 'textarea'"
                    v-model="values[f.key]"
                    rows="3"
                    class="w-full border border-slate-300 rounded px-3 py-2 text-sm"
                ></textarea>
                <input
                    v-else
                    v-model="values[f.key]"
                    :type="f.type || 'text'"
                    :step="f.step"
                    :placeholder="f.placeholder"
                    class="w-full border border-slate-300 rounded px-3 py-2 text-sm"
                />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900" @click="cancel">Cancel</button>
                <button type="submit" class="bg-brand-600 text-white text-sm px-4 py-2 rounded hover:bg-brand-700">Submit</button>
            </div>
        </form>
    </Modal>
</template>
