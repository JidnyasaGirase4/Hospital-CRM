<script setup>
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
});

// Reference palette (dataviz skill): slots 1 & 3 validated as an all-pairs-safe
// categorical pair - blue for billed, aqua for collected.
const COLOR_BILLED = '#2a78d6';
const COLOR_COLLECTED = '#1baf7a';

const TYPE_LABELS = {
    opd: 'OPD',
    ipd: 'IPD',
    pharmacy: 'Pharmacy',
    laboratory: 'Laboratory',
    radiology: 'Radiology',
    ot: 'OT',
    other: 'Other',
};

function fmt(n) {
    return Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });
}

const items = computed(() =>
    (props.rows || [])
        .map((r) => ({
            type: r.type,
            label: TYPE_LABELS[r.type] || r.type,
            billed: Number(r.billed) || 0,
            collected: Number(r.collected) || 0,
            count: r.bill_count,
        }))
        .sort((a, b) => b.billed - a.billed)
);

const max = computed(() => Math.max(1, ...items.value.map((i) => Math.max(i.billed, i.collected))));

function pct(v) {
    return `${Math.max((v / max.value) * 100, v > 0 ? 2 : 0)}%`;
}
</script>

<template>
    <div v-if="items.length" class="space-y-4">
        <div class="flex items-center gap-4 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" :style="{ background: COLOR_BILLED }"></span>Billed</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" :style="{ background: COLOR_COLLECTED }"></span>Collected</span>
        </div>
        <div class="space-y-3.5">
            <div v-for="item in items" :key="item.type">
                <div class="flex items-baseline justify-between mb-1">
                    <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
                    <span class="text-[11px] text-slate-400">{{ item.count }} bill{{ item.count === 1 ? '' : 's' }}</span>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden" :title="`Billed: ${fmt(item.billed)}`">
                            <div class="h-full rounded-full transition-all" :style="{ width: pct(item.billed), background: COLOR_BILLED }"></div>
                        </div>
                        <span class="w-16 shrink-0 text-right text-[11px] tabular-nums text-slate-500">{{ fmt(item.billed) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden" :title="`Collected: ${fmt(item.collected)}`">
                            <div class="h-full rounded-full transition-all" :style="{ width: pct(item.collected), background: COLOR_COLLECTED }"></div>
                        </div>
                        <span class="w-16 shrink-0 text-right text-[11px] tabular-nums font-medium" :style="{ color: COLOR_COLLECTED }">{{ fmt(item.collected) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p v-else class="text-sm text-slate-400">No billing activity in this period</p>
</template>
