<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: [String, Boolean, Number], default: '' },
});

// Keyword-based color mapping so any status string coming from the API
// (dozens of different enums across 18 modules) gets a sensible color
// without every page having to define its own mapping.
const GROUPS = {
    emerald: ['active', 'paid', 'completed', 'approved', 'available', 'settled', 'received', 'discharged', 'delivered', 'resulted', 'success', 'true', 'yes', 'read', 'ok'],
    amber: ['pending', 'scheduled', 'ordered', 'processing', 'partially-paid', 'partially_paid', 'draft', 'submitted', 'in-progress', 'in progress', 'reserved', 'cleaning', 'unread'],
    red: ['cancelled', 'canceled', 'inactive', 'rejected', 'critical', 'expired', 'overdue', 'false', 'no', 'maintenance', 'unpaid', 'low stock'],
    sky: ['triaged', 'in-treatment', 'sample-collected', 'admitted', 'referred', 'checked-in'],
    slate: ['registered', 'new', 'other'],
};

function colorFor(text) {
    const key = String(text).toLowerCase().trim();
    for (const [color, words] of Object.entries(GROUPS)) {
        if (words.includes(key)) return color;
    }
    return 'slate';
}

const classes = {
    emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    amber: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    red: 'bg-red-50 text-red-700 ring-red-600/20',
    sky: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    slate: 'bg-slate-100 text-slate-600 ring-slate-500/20',
};

const label = computed(() => {
    if (typeof props.value === 'boolean') return props.value ? 'Yes' : 'No';
    return String(props.value ?? '—');
});

const colorClass = computed(() => classes[colorFor(props.value)]);
</script>

<template>
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize ring-1 ring-inset" :class="colorClass">
        {{ label }}
    </span>
</template>
