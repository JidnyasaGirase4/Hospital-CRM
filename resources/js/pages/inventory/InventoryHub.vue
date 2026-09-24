<script setup>
import HubGrid from '../../components/HubGrid.vue';

const links = [
    { to: 'inventory-items.index', label: 'Inventory Items', desc: 'Non-medicine stock items & stock movements', icon: 'archive' },
    { to: 'purchase-orders.index', label: 'Purchase Orders', desc: 'Orders placed with suppliers', icon: 'document' },
    { to: 'suppliers.index', label: 'Suppliers', desc: 'Supplier directory (shared with Pharmacy)', icon: 'truck' },
];
</script>

<template>
    <HubGrid :links="links" />
</template>
