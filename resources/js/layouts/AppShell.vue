<script setup>
import { computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import { NAV_MODULES } from '../router/modules';
import Breadcrumbs from '../components/Breadcrumbs.vue';
import ProfileDropdown from '../components/ProfileDropdown.vue';
import Icon from '../components/Icon.vue';

const auth = useAuthStore();
const ui = useUiStore();
const route = useRoute();
const router = useRouter();

function goBack() {
    if (window.history.state?.back) {
        router.back();
    } else {
        router.push('/');
    }
}

const visibleModules = computed(() => NAV_MODULES.filter((m) => auth.can(m.permission)));

// c = accent, c2 = lighter accent (used for the icon's gradient), cb = tint
// background for the active row. Deliberately no blue/indigo/sky/cyan - each
// section gets its own warm or earthy identity instead.
const GROUP_COLORS = {
    __top__: { c: '#16A34A', c2: '#4ADE80', cb: '#F0FDF4' },
    'Front Office': { c: '#EA580C', c2: '#FB923C', cb: '#FFF7ED' },
    Clinical: { c: '#E11D48', c2: '#FB7185', cb: '#FFF1F2' },
    'Pharmacy & Diagnostics': { c: '#7C3AED', c2: '#A78BFA', cb: '#F5F3FF' },
    Finance: { c: '#059669', c2: '#34D399', cb: '#ECFDF5' },
    Operations: { c: '#D97706', c2: '#FBBF24', cb: '#FFFBEB' },
    Insights: { c: '#0D9488', c2: '#2DD4BF', cb: '#F0FDFA' },
    Administration: { c: '#9333EA', c2: '#C084FC', cb: '#FAF5FF' },
};

function colorsFor(mod) {
    return GROUP_COLORS[mod.group || '__top__'] || GROUP_COLORS.__top__;
}

function rowStyle(mod) {
    const { c, c2, cb } = colorsFor(mod);
    return { '--c': c, '--c2': c2, '--cb': cb };
}

function isActive(mod) {
    if (mod.path === '/') return route.path === '/';
    return route.path === mod.path || route.path.startsWith(`${mod.path}/`);
}

const groupedModules = computed(() => {
    const groups = [];
    const order = [];
    for (const mod of visibleModules.value) {
        const key = mod.group || '__top__';
        if (!order.includes(key)) {
            order.push(key);
            groups.push({ key, label: mod.group, items: [] });
        }
        groups.find((g) => g.key === key).items.push(mod);
    }
    return groups;
});

watch(() => route.fullPath, () => {
    ui.sidebarOpen = false;
});
</script>

<template>
    <div class="h-screen flex bg-slate-100 overflow-hidden">
        <div v-if="ui.sidebarOpen" class="fixed inset-0 bg-slate-900/50 z-30 md:hidden" @click="ui.sidebarOpen = false"></div>

        <aside
            class="fixed md:static inset-y-0 left-0 z-40 w-72 shrink-0 bg-white border-r border-slate-200 flex flex-col transform transition-transform duration-200 md:translate-x-0"
            :class="ui.sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="px-5 py-5 flex items-center gap-3 border-b border-slate-100">
                <div class="brandmark w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                    <Icon name="heart" :size="18" class="text-white" />
                </div>
                <div class="min-w-0">
                    <p class="text-slate-900 font-extrabold leading-tight truncate tracking-tight">Hospital CRM</p>
                    <p class="text-[10.5px] text-slate-400 leading-tight font-semibold tracking-wide">STAFF CONSOLE</p>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 pl-4 pr-3 space-y-6">
                <div v-for="group in groupedModules" :key="group.key">
                    <p v-if="group.label" class="nav-tick mb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400" :style="rowStyle(group.items[0])">
                        {{ group.label }}
                    </p>
                    <RouterLink
                        v-for="mod in group.items"
                        :key="mod.path"
                        :to="mod.path"
                        class="nav-item"
                        :class="{ active: isActive(mod) }"
                        :style="rowStyle(mod)"
                    >
                        <span class="nav-icon">
                            <Icon :name="mod.icon" :size="15" />
                        </span>
                        <span class="truncate">{{ mod.label }}</span>
                    </RouterLink>
                </div>
            </nav>

            <div class="px-3 py-4 border-t border-slate-100">
                <ProfileDropdown />
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 min-h-0">
            <header class="h-16 shrink-0 border-b border-slate-200 bg-white/80 backdrop-blur flex items-center gap-3 px-4 md:px-6 z-20">
                <button type="button" class="md:hidden text-slate-500 hover:text-slate-800" aria-label="Open menu" @click="ui.sidebarOpen = true">
                    <Icon name="menu" :size="24" />
                </button>
                <button
                    v-if="route.path !== '/'"
                    type="button"
                    class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors shrink-0"
                    aria-label="Go back"
                    title="Back"
                    @click="goBack"
                >
                    <Icon name="arrow-left" :size="18" />
                </button>
                <div class="min-w-0 flex-1">
                    <Breadcrumbs />
                    <h1 class="text-base font-semibold text-slate-800 truncate">{{ $route.meta.title || 'Hospital CRM' }}</h1>
                </div>
                <RouterLink
                    :to="{ name: 'notifications.index' }"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 hover:bg-orange-50 hover:text-orange-600 transition-colors"
                    title="Notifications"
                >
                    <Icon name="bell" :size="20" />
                </RouterLink>
            </header>
            <main class="flex-1 min-h-0 overflow-y-auto p-4 md:p-6">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<style scoped>
.brandmark {
    background: linear-gradient(140deg, #fb923c, #e11d48);
    box-shadow: 0 6px 16px -4px rgba(225, 29, 72, 0.5);
}

.nav-tick::before {
    content: '';
    display: inline-block;
    width: 12px;
    height: 3px;
    border-radius: 3px;
    margin-right: 6px;
    background: linear-gradient(90deg, var(--c2, #cbd5e1), var(--c, #94a3b8));
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 7px 11px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 600;
    color: #475569;
    position: relative;
    margin-bottom: 2px;
    transition: background 0.16s ease, color 0.16s ease;
}

.nav-item:hover {
    background: var(--cb);
    color: var(--c);
}

.nav-item:hover .nav-icon {
    transform: translateY(-2px) scale(1.06);
}

.nav-item.active {
    background: var(--cb);
    color: var(--c);
    font-weight: 800;
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: -16px;
    top: 6px;
    bottom: 6px;
    width: 3.5px;
    border-radius: 0 4px 4px 0;
    background: var(--c);
}

.nav-icon {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    flex: none;
    color: #fff;
    background: linear-gradient(140deg, var(--c2, #94a3b8), var(--c, #64748b));
    box-shadow: 0 4px 10px -4px var(--c, #64748b), inset 0 1px 0 rgba(255, 255, 255, 0.32);
    transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.2s ease;
}

.nav-item.active .nav-icon {
    transform: scale(1.05);
    box-shadow: 0 6px 14px -4px var(--c, #64748b), inset 0 1px 0 rgba(255, 255, 255, 0.35);
}
</style>
