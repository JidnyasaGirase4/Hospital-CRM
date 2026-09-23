<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';
import PermissionGate from '../../components/PermissionGate.vue';

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

const statusColor = {
    available: 'text-emerald-600',
    occupied: 'text-red-600',
    reserved: 'text-amber-600',
    cleaning: 'text-brand-600',
    maintenance: 'text-slate-400',
};

onMounted(load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <RouterLink :to="{ name: 'ipd.hub' }" class="text-sm text-brand-600 hover:underline">← IPD</RouterLink>
            <PermissionGate permission="ipd.create">
                <div class="flex gap-3">
                    <button type="button" class="text-sm text-brand-600 hover:underline" @click="showWardForm = !showWardForm">+ Ward</button>
                    <button type="button" class="text-sm text-brand-600 hover:underline" @click="showRoomForm = !showRoomForm">+ Room</button>
                    <button type="button" class="text-sm text-brand-600 hover:underline" @click="showBedForm = !showBedForm">+ Bed</button>
                </div>
            </PermissionGate>
        </div>

        <form v-if="showWardForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addWard">
            <input v-model="wardForm.name" placeholder="Ward name" required class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <input v-model="wardForm.floor" placeholder="Floor" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <input v-model="wardForm.ward_type" placeholder="Type (General/ICU…)" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add Ward</button>
        </form>
        <form v-if="showRoomForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-2 sm:grid-cols-4 gap-2" @submit.prevent="addRoom">
            <select v-model="roomForm.ward_id" required class="border border-slate-300 rounded px-2 py-1.5 text-sm">
                <option value="">Select ward…</option>
                <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
            <input v-model="roomForm.room_number" placeholder="Room number" required class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <input v-model="roomForm.room_type" placeholder="Room type" class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add Room</button>
        </form>
        <form v-if="showBedForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-3 gap-2" @submit.prevent="addBed">
            <select v-model="bedForm.room_id" required class="border border-slate-300 rounded px-2 py-1.5 text-sm">
                <option value="">Select room…</option>
                <optgroup v-for="w in wards" :key="w.id" :label="w.name">
                    <option v-for="r in roomsForWard(w.id)" :key="r.id" :value="r.id">{{ r.room_number }}</option>
                </optgroup>
            </select>
            <input v-model="bedForm.bed_number" placeholder="Bed number" required class="border border-slate-300 rounded px-2 py-1.5 text-sm" />
            <button type="submit" class="bg-brand-600 text-white rounded text-sm px-3">Add Bed</button>
        </form>

        <div v-for="ward in wards" :key="ward.id" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-2">{{ ward.name }} <span class="text-slate-400 text-sm font-normal">({{ ward.ward_type || 'General' }}, floor {{ ward.floor || '—' }})</span></h3>
            <div v-for="room in roomsForWard(ward.id)" :key="room.id" class="mb-2 pl-3 border-l-2 border-slate-100">
                <p class="text-sm font-medium text-slate-600">Room {{ room.room_number }} <span class="text-slate-400 font-normal">({{ room.room_type || '—' }})</span></p>
                <div class="flex flex-wrap gap-2 mt-1">
                    <span v-for="bed in room.beds" :key="bed.id" class="text-xs border border-slate-200 rounded px-2 py-1 flex items-center gap-2">
                        {{ bed.bed_number }} — <span :class="statusColor[bed.status] || 'text-slate-500'">{{ bed.status }}</span>
                        <PermissionGate permission="beds.update">
                            <button v-if="bed.status === 'cleaning'" type="button" class="text-brand-600 hover:underline" @click="markCleaned(bed)">Mark Cleaned</button>
                        </PermissionGate>
                    </span>
                    <span v-if="!room.beds || room.beds.length === 0" class="text-xs text-slate-400">No beds</span>
                </div>
            </div>
            <p v-if="roomsForWard(ward.id).length === 0" class="text-sm text-slate-400">No rooms yet</p>
        </div>
        <p v-if="wards.length === 0" class="text-sm text-slate-400">No wards set up yet</p>
    </div>
</template>
