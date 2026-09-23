<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

const form = ref({
    first_name: '',
    last_name: '',
    dob: '',
    gender: '',
    mobile: '',
    email: '',
    address_line: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    blood_group: '',
    allergies: '',
    insurance_provider: '',
    insurance_policy_number: '',
    is_active: true,
});
const errors = ref({});
const saving = ref(false);
const loadError = ref('');

async function loadPatient() {
    try {
        const { data } = await apiClient.get(`/patients/${route.params.id}`);
        const p = data.data;
        form.value = {
            first_name: p.first_name ?? '',
            last_name: p.last_name ?? '',
            dob: p.dob ?? '',
            gender: p.gender ?? '',
            mobile: p.mobile ?? '',
            email: p.email ?? '',
            address_line: p.address?.line ?? '',
            city: p.address?.city ?? '',
            state: p.address?.state ?? '',
            postal_code: p.address?.postal_code ?? '',
            country: p.address?.country ?? '',
            emergency_contact_name: p.emergency_contact?.name ?? '',
            emergency_contact_phone: p.emergency_contact?.phone ?? '',
            emergency_contact_relation: p.emergency_contact?.relation ?? '',
            blood_group: p.blood_group ?? '',
            allergies: p.allergies ?? '',
            insurance_provider: p.insurance?.provider ?? '',
            insurance_policy_number: p.insurance?.policy_number ?? '',
            is_active: p.is_active,
        };
    } catch (e) {
        loadError.value = e.response?.data?.message || 'Failed to load patient';
    }
}

onMounted(() => {
    if (isEdit.value) loadPatient();
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        if (isEdit.value) {
            await apiClient.put(`/patients/${route.params.id}`, form.value);
        } else {
            await apiClient.post('/patients', form.value);
        }
        router.push({ name: 'patients.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">{{ isEdit ? 'Edit Patient' : 'New Patient' }}</h2>
        <p v-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">First name</label>
                <input v-model="form.first_name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.first_name" class="text-xs text-red-600 mt-1">{{ errors.first_name[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Last name</label>
                <input v-model="form.last_name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Date of birth</label>
                <input v-model="form.dob" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Gender</label>
                <select v-model="form.gender" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                    <option value="">—</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mobile</label>
                <input v-model="form.mobile" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.mobile" class="text-xs text-red-600 mt-1">{{ errors.mobile[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input v-model="form.email" type="email" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-600 mb-2">Address</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input v-model="form.address_line" placeholder="Address line" class="border border-slate-300 rounded px-3 py-2 text-sm col-span-2" />
                <input v-model="form.city" placeholder="City" class="border border-slate-300 rounded px-3 py-2 text-sm" />
                <input v-model="form.state" placeholder="State" class="border border-slate-300 rounded px-3 py-2 text-sm" />
                <input v-model="form.postal_code" placeholder="Postal code" class="border border-slate-300 rounded px-3 py-2 text-sm" />
                <input v-model="form.country" placeholder="Country" class="border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-600 mb-2">Emergency contact</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <input v-model="form.emergency_contact_name" placeholder="Name" class="border border-slate-300 rounded px-3 py-2 text-sm" />
                <input v-model="form.emergency_contact_phone" placeholder="Phone" class="border border-slate-300 rounded px-3 py-2 text-sm" />
                <input v-model="form.emergency_contact_relation" placeholder="Relation" class="border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Blood group</label>
                <select v-model="form.blood_group" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                    <option value="">—</option>
                    <option v-for="bg in ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown']" :key="bg" :value="bg">{{ bg }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Allergies</label>
                <input v-model="form.allergies" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Insurance provider</label>
                <input v-model="form.insurance_provider" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Insurance policy number</label>
                <input v-model="form.insurance_policy_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
        </div>

        <div v-if="isEdit" class="flex items-center gap-2">
            <input id="is_active" v-model="form.is_active" type="checkbox" />
            <label for="is_active" class="text-sm text-slate-700">Active</label>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'patients.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
