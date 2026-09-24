<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

const form = ref({
    employee_code: '',
    name: '',
    email: '',
    mobile: '',
    department_id: '',
    password: '',
    is_active: true,
    role_ids: [],
});
const roles = ref([]);
const errors = ref({});
const saving = ref(false);
const loadError = ref('');

async function loadRoles() {
    try {
        const { data } = await apiClient.get('/roles', { params: { per_page: 100 } });
        roles.value = data.data;
    } catch {
        roles.value = [];
    }
}

async function loadUser() {
    try {
        const { data } = await apiClient.get(`/users/${route.params.id}`);
        const u = data.data;
        form.value = {
            employee_code: u.employee_code ?? '',
            name: u.name ?? '',
            email: u.email ?? '',
            mobile: u.mobile ?? '',
            department_id: u.department?.id ?? '',
            password: '',
            is_active: u.is_active,
            role_ids: (u.roles || []).map((r) => r.id),
        };
    } catch (e) {
        loadError.value = e.response?.data?.message || 'Failed to load user';
    }
}

onMounted(() => {
    loadRoles();
    if (isEdit.value) loadUser();
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        if (isEdit.value) {
            const payload = { ...form.value };
            if (!payload.password) delete payload.password;
            delete payload.role_ids;
            await apiClient.put(`/users/${route.params.id}`, payload);
            await apiClient.post(`/users/${route.params.id}/roles`, { role_ids: form.value.role_ids });
        } else {
            await apiClient.post('/users', form.value);
        }
        router.push({ name: 'users.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">{{ isEdit ? 'Edit User' : 'New User' }}</h2>
        <p v-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Name</label>
                <input v-model="form.name" class="input" />
                <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="label">Employee code</label>
                <input v-model="form.employee_code" class="input" />
                <p v-if="errors.employee_code" class="field-error">{{ errors.employee_code[0] }}</p>
            </div>
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" class="input" />
                <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
            </div>
            <div>
                <label class="label">Mobile</label>
                <input v-model="form.mobile" class="input" />
            </div>
            <div>
                <label class="label">Department ID</label>
                <input v-model="form.department_id" type="number" class="input" />
            </div>
            <div>
                <label class="label">{{ isEdit ? 'New password (optional)' : 'Password' }}</label>
                <input v-model="form.password" type="password" class="input" />
                <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
            </div>
        </div>

        <div v-if="!isEdit || roles.length">
            <h3 class="card-title mb-3">Roles</h3>
            <div class="flex flex-wrap gap-3">
                <label v-for="role in roles" :key="role.id" class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-brand-300 hover:bg-brand-50/50">
                    <input type="checkbox" :value="role.id" v-model="form.role_ids" />
                    {{ role.name }}
                </label>
            </div>
        </div>

        <div v-if="isEdit" class="flex items-center gap-2">
            <input id="is_active" v-model="form.is_active" type="checkbox" />
            <label for="is_active" class="text-sm text-slate-700">Active</label>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'users.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
