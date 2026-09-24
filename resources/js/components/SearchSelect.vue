<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: null },
    fetcher: { type: Function, required: true },
    placeholder: { type: String, default: 'Search…' },
});
const emit = defineEmits(['update:modelValue']);

const query = ref('');
const results = ref([]);
const open = ref(false);
let debounceTimer = null;

watch(
    () => props.modelValue,
    (value) => {
        if (!value) query.value = '';
    }
);

function onInput() {
    emit('update:modelValue', null);
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        if (!query.value) {
            results.value = [];
            open.value = false;
            return;
        }
        results.value = await props.fetcher(query.value);
        open.value = true;
    }, 300);
}

function select(item) {
    query.value = item.label;
    emit('update:modelValue', item.id);
    open.value = false;
    results.value = [];
}
</script>

<template>
    <div class="relative">
        <input
            v-model="query"
            type="text"
            :placeholder="placeholder"
            class="input"
            @input="onInput"
            @focus="open = results.length > 0"
        />
        <ul
            v-if="open && results.length"
            class="absolute z-20 mt-1.5 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl ring-1 ring-slate-900/5"
        >
            <li
                v-for="item in results"
                :key="item.id"
                class="cursor-pointer px-3.5 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700"
                @click="select(item)"
            >{{ item.label }}</li>
        </ul>
    </div>
</template>
