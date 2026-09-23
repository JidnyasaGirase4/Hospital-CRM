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
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">{{ isEdit ? 'Edit User' : 'New User' }}</h2>
        <p v-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input v-model="form.name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Employee code</label>
                <input v-model="form.employee_code" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.employee_code" class="text-xs text-red-600 mt-1">{{ errors.employee_code[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input v-model="form.email" type="email" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mobile</label>
                <input v-model="form.mobile" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Department ID</label>
                <input v-model="form.department_id" type="number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ isEdit ? 'New password (optional)' : 'Password' }}</label>
                <input v-model="form.password" type="password" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password[0] }}</p>
            </div>
        </div>

        <div v-if="!isEdit || roles.length">
            <h3 class="text-sm font-semibold text-slate-600 mb-2">Roles</h3>
            <div class="flex flex-wrap gap-3">
                <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 text-sm border border-slate-200 rounded px-3 py-1.5">
                    <input type="checkbox" :value="role.id" v-model="form.role_ids" />
                    {{ role.name }}
                </label>
            </div>
        </div>

        <div v-if="isEdit" class="flex items-center gap-2">
            <input id="is_active" v-model="form.is_active" type="checkbox" />
            <label for="is_active" class="text-sm text-slate-700">Active</label>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'users.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
