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
            class="w-full border border-slate-300 rounded px-3 py-2 text-sm"
            @input="onInput"
            @focus="open = results.length > 0"
        />
        <ul
            v-if="open && results.length"
            class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded shadow max-h-56 overflow-y-auto"
        >
            <li
                v-for="item in results"
                :key="item.id"
                class="px-3 py-2 text-sm hover:bg-slate-100 cursor-pointer"
                @click="select(item)"
            >{{ item.label }}</li>
        </ul>
    </div>
</template>
