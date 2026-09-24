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
    <form class="card mx-auto max-w-5xl space-y-5 p-6 sm:p-8" @submit.prevent="submit">
        <h2 class="border-b border-slate-100 pb-4 text-xl font-extrabold tracking-tight text-slate-900">{{ isEdit ? 'Edit Role' : 'New Role' }}</h2>
        <p v-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Name</label>
                <input v-model="form.name" class="input" />
                <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="label">Slug</label>
                <input v-model="form.slug" :disabled="isEdit" class="input" />
                <p v-if="errors.slug" class="field-error">{{ errors.slug[0] }}</p>
            </div>
        </div>
        <div>
            <label class="label">Description</label>
            <textarea v-model="form.description" rows="2" class="input"></textarea>
        </div>

        <div>
            <h3 class="card-title mb-3">Permissions</h3>
            <div v-for="(perms, module) in permissionsByModule" :key="module" class="mb-3">
                <p class="text-xs font-medium uppercase text-slate-400 mb-1">{{ module }}</p>
                <div class="flex flex-wrap gap-3">
                    <label v-for="perm in perms" :key="perm.id" class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-brand-300 hover:bg-brand-50/50">
                        <input type="checkbox" :value="perm.id" v-model="form.permission_ids" />
                        {{ perm.name }}
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <RouterLink :to="{ name: 'roles.index' }" class="btn btn-secondary">Cancel</RouterLink>
            <button type="submit" :disabled="saving" class="btn btn-primary">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </form>
</template>
