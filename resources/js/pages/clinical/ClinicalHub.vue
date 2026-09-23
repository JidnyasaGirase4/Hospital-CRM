<script setup>
const links = [
    { to: 'opd-visits.index', label: 'OPD Visits', desc: 'Vitals, symptoms, diagnosis notes per visit' },
    { to: 'consultations.index', label: 'Consultations', desc: 'Doctor consultation records' },
    { to: 'diagnoses.index', label: 'Diagnoses', desc: 'Recorded diagnoses per patient' },
    { to: 'prescriptions.index', label: 'Prescriptions', desc: 'Medicines prescribed to patients' },
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
