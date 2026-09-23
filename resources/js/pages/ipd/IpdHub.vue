<script setup>
const links = [
    { to: 'wards.index', label: 'Wards, Rooms & Beds', desc: 'Manage bed inventory and availability' },
    { to: 'admissions.index', label: 'Admissions', desc: 'Patient admissions and discharges' },
];
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <RouterLink
            v-for="link in links"
            :key="link.to"
            :to="{ name: link.to }"
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:border-brand-300 hover:shadow-sm"
        >
            <h3 class="font-semibold text-slate-800">{{ link.label }}</h3>
            <p class="text-sm text-slate-500 mt-1">{{ link.desc }}</p>
        </RouterLink>
    </div>
</template>
