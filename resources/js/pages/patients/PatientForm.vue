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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">{{ isEdit ? 'Edit Patient' : 'New Patient' }}</h2>
        <p v-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">First name</label>
                <input v-model="form.first_name" class="input" />
                <p v-if="errors.first_name" class="field-error">{{ errors.first_name[0] }}</p>
            </div>
            <div>
                <label class="label">Last name</label>
                <input v-model="form.last_name" class="input" />
            </div>
            <div>
                <label class="label">Date of birth</label>
                <input v-model="form.dob" type="date" class="input" />
            </div>
            <div>
                <label class="label">Gender</label>
                <select v-model="form.gender" class="input">
                    <option value="">—</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="label">Mobile</label>
                <input v-model="form.mobile" class="input" />
                <p v-if="errors.mobile" class="field-error">{{ errors.mobile[0] }}</p>
            </div>
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" class="input" />
            </div>
        </div>

        <div>
            <h3 class="card-title mb-3">Address</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input v-model="form.address_line" placeholder="Address line" class="input col-span-2" />
                <input v-model="form.city" placeholder="City" class="input" />
                <input v-model="form.state" placeholder="State" class="input" />
                <input v-model="form.postal_code" placeholder="Postal code" class="input" />
                <input v-model="form.country" placeholder="Country" class="input" />
            </div>
        </div>

        <div>
            <h3 class="card-title mb-3">Emergency contact</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <input v-model="form.emergency_contact_name" placeholder="Name" class="input" />
                <input v-model="form.emergency_contact_phone" placeholder="Phone" class="input" />
                <input v-model="form.emergency_contact_relation" placeholder="Relation" class="input" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Blood group</label>
                <select v-model="form.blood_group" class="input">
                    <option value="">—</option>
                    <option v-for="bg in ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown']" :key="bg" :value="bg">{{ bg }}</option>
                </select>
            </div>
            <div>
                <label class="label">Allergies</label>
                <input v-model="form.allergies" class="input" />
            </div>
            <div>
                <label class="label">Insurance provider</label>
                <input v-model="form.insurance_provider" class="input" />
            </div>
            <div>
                <label class="label">Insurance policy number</label>
                <input v-model="form.insurance_policy_number" class="input" />
            </div>
        </div>

        <div v-if="isEdit" class="flex items-center gap-2">
            <input id="is_active" v-model="form.is_active" type="checkbox" />
            <label for="is_active" class="text-sm text-slate-700">Active</label>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'patients.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
