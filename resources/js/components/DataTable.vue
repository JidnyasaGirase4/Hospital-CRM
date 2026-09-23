<script setup>
import Badge from './Badge.vue';
import Icon from './Icon.vue';

defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    pagination: { type: Object, default: null },
});

defineEmits(['page-change']);
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-b from-slate-50 to-slate-50/60 text-slate-500 text-left border-b border-slate-200">
                <tr>
                    <th v-for="col in columns" :key="col.key" class="px-4 py-3.5 font-bold text-[11px] uppercase tracking-wider">{{ col.label }}</th>
                    <th v-if="$slots.actions" class="px-4 py-3.5 font-bold text-[11px] uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-if="loading">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-14 text-center text-slate-400">
                        <div class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-brand-500" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Loading…
                        </div>
                    </td>
                </tr>
                <tr v-else-if="rows.length === 0">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-16 text-center text-slate-400">
                        <div class="inline-flex flex-col items-center gap-2">
                            <div class="w-11 h-11 rounded-full bg-slate-50 text-slate-300 flex items-center justify-center">
                                <Icon name="archive" :size="20" />
                            </div>
                            <span class="text-sm">No records found</span>
                        </div>
                    </td>
                </tr>
                <template v-else>
                    <tr v-for="(row, i) in rows" :key="row.id" class="transition-colors hover:bg-brand-50/50" :class="i % 2 === 1 ? 'bg-slate-50/40' : ''">
                        <td v-for="col in columns" :key="col.key" class="px-4 py-3 text-slate-700">
                            <slot :name="`cell-${col.key}`" :row="row">
                                <Badge v-if="col.badge" :value="col.format ? col.format(row) : row[col.key]" />
                                <template v-else>{{ col.format ? col.format(row) : (row[col.key] ?? '—') }}</template>
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-3 text-right">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        </div>
        <div v-if="pagination && pagination.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 py-3.5 border-t border-slate-100 text-sm text-slate-500 bg-slate-50/50">
            <span>Page <span class="font-semibold text-slate-700">{{ pagination.current_page }}</span> of {{ pagination.last_page }} <span class="text-slate-400">({{ pagination.total }} total)</span></span>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 bg-white rounded-lg font-medium hover:border-brand-300 hover:text-brand-600 hover:shadow-sm disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500 disabled:hover:shadow-none transition-all"
                    :disabled="pagination.current_page <= 1"
                    @click="$emit('page-change', pagination.current_page - 1)"
                ><Icon name="arrow-left" :size="14" /> Prev</button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 bg-white rounded-lg font-medium hover:border-brand-300 hover:text-brand-600 hover:shadow-sm disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500 disabled:hover:shadow-none transition-all"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="$emit('page-change', pagination.current_page + 1)"
                >Next <Icon name="arrow-left" :size="14" class="rotate-180" /></button>
            </div>
        </div>
    </div>
</template>
