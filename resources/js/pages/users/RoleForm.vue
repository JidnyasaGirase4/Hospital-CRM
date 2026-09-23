<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/client';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

const form = ref({ name: '', slug: '', description: '', permission_ids: [] });
const permissionsByModule = ref({});
const errors = ref({});
const saving = ref(false);
const loadError = ref('');

async function loadPermissions() {
    const { data } = await apiClient.get('/permissions');
    const grouped = {};
    for (const p of data.data) {
        grouped[p.module || 'other'] ??= [];
        grouped[p.module || 'other'].push(p);
    }
    permissionsByModule.value = grouped;
}

async function loadRole() {
    try {
        const { data } = await apiClient.get(`/roles/${route.params.id}`);
        const r = data.data;
        form.value = {
            name: r.name ?? '',
            slug: r.slug ?? '',
            description: r.description ?? '',
            permission_ids: (r.permissions || []).map((p) => p.id),
        };
    } catch (e) {
        loadError.value = e.response?.data?.message || 'Failed to load role';
    }
}

onMounted(() => {
    loadPermissions();
    if (isEdit.value) loadRole();
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        if (isEdit.value) {
            await apiClient.put(`/roles/${route.params.id}`, {
                name: form.value.name,
                slug: form.value.slug,
                description: form.value.description,
            });
            await apiClient.post(`/roles/${route.params.id}/permissions`, { permission_ids: form.value.permission_ids });
        } else {
            await apiClient.post('/roles', form.value);
        }
        router.push({ name: 'roles.index' });
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-800">{{ isEdit ? 'Edit Role' : 'New Role' }}</h2>
        <p v-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input v-model="form.name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
                <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
                <input v-model="form.slug" :disabled="isEdit" class="w-full border border-slate-300 rounded px-3 py-2 text-sm disabled:bg-slate-100" />
                <p v-if="errors.slug" class="text-xs text-red-600 mt-1">{{ errors.slug[0] }}</p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-600 mb-2">Permissions</h3>
            <div v-for="(perms, module) in permissionsByModule" :key="module" class="mb-3">
                <p class="text-xs font-medium uppercase text-slate-400 mb-1">{{ module }}</p>
                <div class="flex flex-wrap gap-3">
                    <label v-for="perm in perms" :key="perm.id" class="flex items-center gap-2 text-sm border border-slate-200 rounded px-3 py-1.5">
                        <input type="checkbox" :value="perm.id" v-model="form.permission_ids" />
                        {{ perm.name }}
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <RouterLink :to="{ name: 'roles.index' }" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 bg-brand-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
