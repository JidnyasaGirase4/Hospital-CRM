<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';
import Icon from '../../components/Icon.vue';

const wards = ref([]);
const rooms = ref([]);
const showWardForm = ref(false);
const showRoomForm = ref(false);
const showBedForm = ref(false);
const wardForm = ref({ name: '', floor: '', ward_type: '' });
const roomForm = ref({ ward_id: '', room_number: '', room_type: '' });
const bedForm = ref({ room_id: '', bed_number: '' });

function roomsForWard(wardId) {
    return rooms.value.filter((r) => r.ward_id === wardId);
}

async function load() {
    const [wardsRes, roomsRes] = await Promise.all([apiClient.get('/wards'), apiClient.get('/rooms')]);
    wards.value = wardsRes.data.data;
    rooms.value = roomsRes.data.data;
}

async function addWard() {
    await apiClient.post('/wards', wardForm.value);
    wardForm.value = { name: '', floor: '', ward_type: '' };
    showWardForm.value = false;
    load();
}

async function addRoom() {
    await apiClient.post('/rooms', roomForm.value);
    roomForm.value = { ward_id: '', room_number: '', room_type: '' };
    showRoomForm.value = false;
    load();
}

async function addBed() {
    await apiClient.post('/beds', bedForm.value);
    bedForm.value = { room_id: '', bed_number: '' };
    showBedForm.value = false;
    load();
}

async function markCleaned(bed) {
    await apiClient.patch(`/beds/${bed.id}/mark-cleaned`);
    load();
}

const bedPill = {
    available: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    occupied: 'bg-red-50 text-red-700 ring-red-200',
    reserved: 'bg-amber-50 text-amber-700 ring-amber-200',
    cleaning: 'bg-sky-50 text-sky-700 ring-sky-200',
    maintenance: 'bg-slate-100 text-slate-500 ring-slate-200',
};

const wardStats = (wardId) => {
    const beds = roomsForWard(wardId).flatMap((r) => r.beds || []);
    return { total: beds.length, free: beds.filter((b) => b.status === 'available').length };
};

onMounted(load);
</script>

<template>
    <div class="space-y-4">
        <div class="toolbar">
            <RouterLink :to="{ name: 'ipd.hub' }" class="btn btn-sm btn-soft">← IPD</RouterLink>
            <PermissionGate permission="beds.create">
                <div class="flex gap-3">
                    <button type="button" class="btn btn-sm btn-soft" @click="showWardForm = !showWardForm">+ Ward</button>
                    <button type="button" class="btn btn-sm btn-soft" @click="showRoomForm = !showRoomForm">+ Room</button>
                    <button type="button" class="btn btn-sm btn-soft" @click="showBedForm = !showBedForm">+ Bed</button>
                </div>
            </PermissionGate>
        </div>

        <form v-if="showWardForm" class="card p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addWard">
            <input v-model="wardForm.name" placeholder="Ward name" required class="input input-sm" />
            <input v-model="wardForm.floor" placeholder="Floor" class="input input-sm" />
            <input v-model="wardForm.ward_type" placeholder="Type (General/ICU…)" class="input input-sm" />
            <button type="submit" class="btn btn-primary btn-sm">Add Ward</button>
        </form>
        <form v-if="showRoomForm" class="card p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addRoom">
            <select v-model="roomForm.ward_id" required class="input input-sm">
                <option value="">Select ward…</option>
                <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
            <input v-model="roomForm.room_number" placeholder="Room number" required class="input input-sm" />
            <input v-model="roomForm.room_type" placeholder="Room type" class="input input-sm" />
            <button type="submit" class="btn btn-primary btn-sm">Add Room</button>
        </form>
        <form v-if="showBedForm" class="card p-4 grid grid-cols-1 sm:grid-cols-3 gap-2" @submit.prevent="addBed">
            <select v-model="bedForm.room_id" required class="input input-sm">
                <option value="">Select room…</option>
                <optgroup v-for="w in wards" :key="w.id" :label="w.name">
                    <option v-for="r in roomsForWard(w.id)" :key="r.id" :value="r.id">{{ r.room_number }}</option>
                </optgroup>
            </select>
            <input v-model="bedForm.bed_number" placeholder="Bed number" required class="input input-sm" />
            <button type="submit" class="btn btn-primary btn-sm">Add Bed</button>
        </form>

        <section v-for="ward in wards" :key="ward.id" class="card overflow-hidden">
            <header class="card-header">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">{{ ward.name }}</h3>
                    <p class="text-xs text-slate-500">{{ ward.ward_type || 'General' }} · floor {{ ward.floor || '—' }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ wardStats(ward.id).free }} / {{ wardStats(ward.id).total }} beds free</span>
            </header>
            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
            <div v-for="room in roomsForWard(ward.id)" :key="room.id" class="rounded-xl border border-slate-200 p-3.5">
                <p class="text-sm font-bold text-slate-700">Room {{ room.room_number }} <span class="font-medium text-slate-400">· {{ room.room_type || '—' }}</span></p>
                <div class="mt-2.5 flex flex-wrap gap-2">
                    <span v-for="bed in room.beds" :key="bed.id" class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="bedPill[bed.status] || bedPill.maintenance">
                        <Icon name="bed" :size="14" />{{ bed.bed_number }} <span class="font-medium capitalize opacity-80">{{ bed.status }}</span>
                        <PermissionGate permission="beds.update">
                            <button v-if="bed.status === 'cleaning'" type="button" class="btn btn-sm btn-soft" @click="markCleaned(bed)">Mark Cleaned</button>
                        </PermissionGate>
                    </span>
                    <span v-if="!room.beds || room.beds.length === 0" class="text-xs text-slate-400">No beds</span>
                </div>
            </div>
            <p v-if="roomsForWard(ward.id).length === 0" class="text-sm text-slate-400 md:col-span-2">No rooms yet</p>
            </div>
        </section>
        <p v-if="wards.length === 0" class="text-sm text-slate-400">No wards set up yet</p>
    </div>
</template>
