<script setup>
import HubGrid from '../../components/HubGrid.vue';

const links = [
    { to: 'opd-visits.index', label: 'OPD Visits', desc: 'Vitals, symptoms, diagnosis notes per visit', icon: 'clipboard' },
    { to: 'consultations.index', label: 'Consultations', desc: 'Doctor consultation records', icon: 'users' },
    { to: 'diagnoses.index', label: 'Diagnoses', desc: 'Recorded diagnoses per patient', icon: 'shield-check' },
    { to: 'prescriptions.index', label: 'Prescriptions', desc: 'Medicines prescribed to patients', icon: 'document' },
];
</script>

<template>
    <HubGrid :links="links" />
</template>
