<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';
import StatCard from '../../components/StatCard.vue';
import DataTable from '../../components/DataTable.vue';
import Icon from '../../components/Icon.vue';
import { formatMoney } from '../../utils/format';

const TYPE_LABELS = {
    opd: 'OPD',
    ipd: 'IPD',
    pharmacy: 'Pharmacy',
    laboratory: 'Laboratory',
    radiology: 'Radiology',
    ot: 'OT',
    other: 'Other',
};

const revenueColumns = [
    { key: 'type', label: 'Type', format: (r) => TYPE_LABELS[r.type] || r.type },
    { key: 'bill_count', label: 'Bills' },
    { key: 'billed', label: 'Billed' },
    { key: 'collected', label: 'Collected' },
];

const wardColumns = [
    { key: 'ward_name', label: 'Ward' },
    { key: 'total_beds', label: 'Total Beds' },
    { key: 'occupied_beds', label: 'Occupied' },
];

const from = ref('');
const to = ref('');
const revenue = ref(null);
const occupancy = ref(null);
const pharmacyStock = ref(null);
const labTurnaround = ref(null);
const loading = ref(true);

async function loadRevenue() {
    const { data } = await apiClient.get('/reports/revenue', { params: { from: from.value || undefined, to: to.value || undefined } });
    revenue.value = data.data;
}

async function loadLabTurnaround() {
    const { data } = await apiClient.get('/reports/lab-turnaround', { params: { from: from.value || undefined, to: to.value || undefined } });
    labTurnaround.value = data.data;
}

async function loadAll() {
    loading.value = true;
    await Promise.all([
        loadRevenue(),
        loadLabTurnaround(),
        apiClient.get('/reports/bed-occupancy').then(({ data }) => { occupancy.value = data.data; }),
        apiClient.get('/reports/pharmacy-stock').then(({ data }) => { pharmacyStock.value = data.data; }),
    ]);
    loading.value = false;
}

function applyFilter() {
    loadRevenue();
    loadLabTurnaround();
}

onMounted(loadAll);
</script>

<template>
    <div class="space-y-8">
        <div class="flex flex-wrap items-end gap-3 card p-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                <input v-model="from" type="date" class="input input-sm w-44" />
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                <input v-model="to" type="date" class="input input-sm w-44" />
            </div>
            <button type="button" class="btn btn-primary" @click="applyFilter">
                <Icon name="chart" :size="15" /> Apply
            </button>
        </div>

        <div v-if="revenue">
            <h2 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-brand-500"></span>
                Revenue <span class="font-normal text-slate-400">({{ revenue.period.from }} to {{ revenue.period.to }})</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <StatCard label="Total Billed" icon="banknote" color="sky" :value="formatMoney(revenue.total_billed)" />
                <StatCard label="Total Collected" icon="banknote" color="emerald" :value="formatMoney(revenue.total_collected)" />
            </div>
            <DataTable v-if="revenue.by_type?.length" :columns="revenueColumns" :rows="revenue.by_type" :loading="false" :pagination="null" />
        </div>

        <div v-if="occupancy">
            <h2 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-amber-500"></span>
                Bed Occupancy
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <StatCard label="Total Beds" icon="bed" color="brand" :value="occupancy.total_beds" />
                <StatCard label="Occupied" icon="bed" color="amber" :value="occupancy.occupied_beds" />
                <StatCard label="Occupancy Rate" icon="chart" color="emerald" :value="`${occupancy.occupancy_rate}%`" />
            </div>
            <DataTable v-if="occupancy.by_ward?.length" :columns="wardColumns" :rows="occupancy.by_ward" :loading="false" :pagination="null" />
        </div>

        <div v-if="pharmacyStock">
            <h2 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-red-500"></span>
                Pharmacy Low Stock
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Medicines ({{ pharmacyStock.low_stock_medicines.length }})</h3>
                    <ul class="text-sm divide-y divide-slate-100">
                        <li v-for="m in pharmacyStock.low_stock_medicines" :key="m.id" class="py-2 flex items-center justify-between">
                            <span class="text-slate-700">{{ m.name }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 ring-1 ring-inset ring-red-200">{{ m.stock_on_hand }} left</span>
                        </li>
                        <li v-if="pharmacyStock.low_stock_medicines.length === 0" class="text-slate-400 py-2 text-center">None</li>
                    </ul>
                </div>
                <div class="card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Inventory Items ({{ pharmacyStock.low_stock_inventory_items.length }})</h3>
                    <ul class="text-sm divide-y divide-slate-100">
                        <li v-for="i in pharmacyStock.low_stock_inventory_items" :key="i.id" class="py-2 flex items-center justify-between">
                            <span class="text-slate-700">{{ i.name }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 ring-1 ring-inset ring-red-200">{{ i.stock_on_hand }} left</span>
                        </li>
                        <li v-if="pharmacyStock.low_stock_inventory_items.length === 0" class="text-slate-400 py-2 text-center">None</li>
                    </ul>
                </div>
            </div>
        </div>

        <div v-if="labTurnaround">
            <h2 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-sky-500"></span>
                Lab Turnaround <span class="font-normal text-slate-400">({{ labTurnaround.period.from }} to {{ labTurnaround.period.to }})</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <StatCard label="Completed Items" icon="beaker" color="sky" :value="labTurnaround.completed_items" />
                <StatCard label="Avg Turnaround (hrs)" icon="beaker" color="brand" :value="labTurnaround.average_turnaround_hours ?? '—'" />
            </div>
        </div>
    </div>
</template>
