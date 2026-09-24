<script setup>
import HubGrid from '../../components/HubGrid.vue';

const links = [
    { to: 'wards.index', label: 'Wards, Rooms & Beds', desc: 'Manage bed inventory and availability', icon: 'bed' },
    { to: 'admissions.index', label: 'Admissions', desc: 'Patient admissions and discharges', icon: 'clipboard' },
];
</script>

<template>
    <HubGrid :links="links" />
</template>
