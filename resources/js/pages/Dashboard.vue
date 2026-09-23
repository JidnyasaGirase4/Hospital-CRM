<script setup>
import { ref, computed, onMounted } from 'vue';
import apiClient from '../api/client';
import { useAuthStore } from '../stores/auth';
import StatCard from '../components/StatCard.vue';
import PermissionGate from '../components/PermissionGate.vue';
import Icon from '../components/Icon.vue';
import RevenueByTypeChart from '../components/charts/RevenueByTypeChart.vue';

const auth = useAuthStore();

const stats = ref({
    patients: null,
    appointments: null,
    revenue: null,
    occupancy: null,
    lowStock: null,
});
const loading = ref(true);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
});

const quickActions = [
    { label: 'New Patient', to: { name: 'patients.create' }, icon: 'user', permission: 'patients.create' },
    { label: 'New Appointment', to: { name: 'appointments.create' }, icon: 'calendar', permission: 'appointments.create' },
    { label: 'New Bill', to: { name: 'bills.create' }, icon: 'banknote', permission: 'billing.create' },
    { label: 'New Admission', to: { name: 'admissions.create' }, icon: 'bed', permission: 'ipd.admit' },
];

async function safe(fn) {
    try {
        return await fn();
    } catch {
        return null;
    }
}

async function load() {
    loading.value = true;

    const [patients, appointments, revenue, occupancy, pharmacyStock] = await Promise.all([
        safe(async () => (await apiClient.get('/patients', { params: { per_page: 1 } })).data.meta?.pagination?.total),
        safe(async () => (await apiClient.get('/appointments', { params: { per_page: 1 } })).data.meta?.pagination?.total),
        safe(async () => (await apiClient.get('/reports/revenue')).data.data),
        safe(async () => (await apiClient.get('/reports/bed-occupancy')).data.data),
        safe(async () => (await apiClient.get('/reports/pharmacy-stock')).data.data),
    ]);

    stats.value = {
        patients,
        appointments,
        revenue,
        occupancy,
        lowStock: pharmacyStock
            ? (pharmacyStock.low_stock_medicines?.length || 0) + (pharmacyStock.low_stock_inventory_items?.length || 0)
            : null,
    };

    loading.value = false;
}

onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ greeting }}, {{ auth.user?.name?.split(' ')[0] }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">Here's what's happening across the hospital today.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <PermissionGate v-for="action in quickActions" :key="action.label" :permission="action.permission">
                    <RouterLink
                        :to="action.to"
                        class="inline-flex items-center gap-1.5 bg-white border border-slate-200 text-slate-700 text-sm font-medium px-3.5 py-2 rounded-xl hover:border-brand-300 hover:text-brand-600 hover:shadow-md hover:-translate-y-0.5 shadow-sm transition-all"
                    >
                        <Icon :name="action.icon" :size="16" /> {{ action.label }}
                    </RouterLink>
                </PermissionGate>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatCard
                label="Total Patients"
                icon="user"
                color="brand"
                :value="loading ? '…' : (stats.patients ?? 'No access')"
            />
            <StatCard
                label="Total Appointments"
                icon="calendar"
                color="sky"
                :value="loading ? '…' : (stats.appointments ?? 'No access')"
            />
            <StatCard
                label="Revenue Collected"
                icon="banknote"
                color="emerald"
                :value="loading ? '…' : (stats.revenue ? stats.revenue.total_collected : 'No access')"
                :hint="stats.revenue ? `Billed: ${stats.revenue.total_billed}` : 'This month'"
            />
            <StatCard
                label="Bed Occupancy"
                icon="bed"
                color="amber"
                :value="loading ? '…' : (stats.occupancy ? `${stats.occupancy.occupancy_rate}%` : 'No access')"
                :hint="stats.occupancy ? `${stats.occupancy.occupied_beds}/${stats.occupancy.total_beds} beds occupied` : ''"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                        <Icon name="chart" :size="16" />
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-700">Revenue by Bill Type</h2>
                        <p v-if="stats.revenue?.period" class="text-[11px] text-slate-400">{{ stats.revenue.period.from }} → {{ stats.revenue.period.to }}</p>
                    </div>
                </div>
                <RevenueByTypeChart v-if="!loading" :rows="stats.revenue?.by_type || []" />
                <p v-else class="text-sm text-slate-400">Loading…</p>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                            <Icon name="alert" :size="16" />
                        </div>
                        <h2 class="text-sm font-semibold text-slate-700">Low Stock Alerts</h2>
                    </div>
                    <p class="text-2xl font-bold text-slate-900">
                        {{ loading ? '…' : (stats.lowStock ?? 'No access') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Medicines + inventory items below reorder level</p>
                    <RouterLink v-if="stats.lowStock" :to="{ name: 'reports.index' }" class="inline-block mt-3 text-xs text-brand-600 hover:underline">View details →</RouterLink>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                            <Icon name="bed" :size="16" />
                        </div>
                        <h2 class="text-sm font-semibold text-slate-700">Occupancy by Ward</h2>
                    </div>
                    <ul v-if="stats.occupancy?.by_ward?.length" class="text-sm text-slate-600 space-y-3">
                        <li v-for="ward in stats.occupancy.by_ward" :key="ward.ward_name">
                            <div class="flex justify-between mb-1">
                                <span class="font-medium text-slate-700">{{ ward.ward_name }}</span>
                                <span class="text-slate-400 tabular-nums">{{ ward.occupied_beds }}/{{ ward.total_beds }}</span>
                            </div>
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden" :title="`${ward.ward_name}: ${ward.occupied_beds} of ${ward.total_beds} beds occupied`">
                                <div
                                    class="h-full bg-gradient-to-r from-brand-400 to-brand-600 rounded-full transition-all"
                                    :style="{ width: `${ward.total_beds ? Math.max((ward.occupied_beds / ward.total_beds) * 100, ward.occupied_beds > 0 ? 3 : 0) : 0}%` }"
                                ></div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-slate-400">No ward data available</p>
                </div>
            </div>
        </div>
    </div>
</template>
