<script setup>
import HubGrid from '../../components/HubGrid.vue';

const links = [
    { to: 'medicines.index', label: 'Medicines', desc: 'Medicine master, batches & stock', icon: 'beaker' },
    { to: 'suppliers.index', label: 'Suppliers', desc: 'Supplier directory', icon: 'truck' },
    { to: 'pharmacy-purchases.index', label: 'Purchases', desc: 'Stock-in via supplier purchases', icon: 'archive' },
    { to: 'pharmacy-sales.index', label: 'Sales', desc: 'Dispensing & pharmacy sales', icon: 'banknote' },
];
</script>

<template>
    <HubGrid :links="links" />
</template>
