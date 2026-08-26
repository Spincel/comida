<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    UserPlusIcon, PencilSquareIcon, TrashIcon, BuildingOfficeIcon, UserIcon, PhotoIcon, 
    XMarkIcon, UserGroupIcon, ShieldCheckIcon, CheckBadgeIcon, NoSymbolIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ team: Array, area: Object });

const showModal = ref(false), editingUser = ref(null), previewAvatar = ref(null), fileInput = ref(null);

const form = useForm({ first_name: '', last_name: '', second_last_name: '', avatar: null });

const openCreateModal = () => { editingUser.value = null; form.reset(); form.clearErrors(); previewAvatar.value = null; showModal.value = true; };
const openEditModal = (u) => { editingUser.value = u; form.first_name = u.first_name; form.last_name = u.last_name; form.second_last_name = u.second_last_name; previewAvatar.value = u.avatar_url; showModal.value = true; };

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        const reader = new FileReader();
        reader.onload = (ev) => previewAvatar.value = ev.target.result;
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    if (editingUser.value) {
        form.post(route('team.update', editingUser.value.id), { _method: 'put', forceFormData: true, preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        form.post(route('team.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => closeModal() });
    }
};

const toggleStatus = (id) => router.patch(route('team.toggleStatus', id), {}, { preserveScroll: true });
const closeModal = () => { showModal.value = false; form.reset(); };
</script>

<template>
    <Head title="Mi Plantilla SICOA" />

    <AuthenticatedLayout bento-tag="Plantilla">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12 flex justify-between items-center bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-tinto-50 dark:bg-tinto-950/60 border border-tinto-200 dark:border-tinto-800 rounded-2xl flex items-center justify-center">
                        <UserGroupIcon class="h-6 w-6 text-tinto-700 dark:text-oro-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Mi Equipo de Trabajo</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ area.name }}</p>
                    </div>
                </div>
                <button @click="openCreateModal" class="bg-gradient-to-r from-oro-600 to-oro-500 hover:from-oro-700 hover:to-oro-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-oro-sm border border-oro-300/40 transition-all hover:scale-105 active:scale-95 cursor-pointer">
                    <UserPlusIcon class="h-4 w-4 mr-2" stroke-width="4" /> Nuevo Comensal
                </button>
            </div>

            <div v-if="team.length > 0" class="lg:col-span-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="u in team" :key="u.id" class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none flex flex-col group transition-all hover:scale-[1.02]" :class="u.status === 'inactive' ? 'opacity-60 grayscale' : ''">
                    <div class="flex justify-between items-start mb-6">
                        <img :src="u.avatar_url" class="h-16 w-16 rounded-[1.5rem] border-2 border-white dark:border-gray-700 shadow-md object-cover group-hover:scale-110 transition-transform" />
                        <div class="flex gap-2">
                            <button @click="openEditModal(u)" class="p-2 bg-slate-50 dark:bg-gray-800 text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl transition-all border border-slate-200 dark:border-gray-700 cursor-pointer"><PencilSquareIcon class="h-4 w-4" /></button>
                            <button @click="toggleStatus(u.id)" class="p-2 rounded-xl transition-all border cursor-pointer" :class="u.status === 'active' ? 'bg-emerald-50 text-nayarit-700 border-emerald-200 hover:bg-rose-50 hover:text-rose-600' : 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-emerald-50 hover:text-nayarit-700'"><CheckBadgeIcon v-if="u.status === 'active'" class="h-4 w-4" /><NoSymbolIcon v-else class="h-4 w-4" /></button>
                        </div>
                    </div>
                    <h3 class="font-black text-sm text-slate-800 dark:text-gray-200 uppercase tracking-tight leading-snug mb-2">{{ u.name }}</h3>
                    <div class="mt-auto pt-4 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between">
                        <p class="text-[9px] font-black uppercase tracking-widest" :class="u.status === 'active' ? 'text-nayarit-700 dark:text-emerald-400' : 'text-rose-500'">{{ u.status === 'active' ? '✓ En Plantilla' : '✕ Inactivo' }}</p>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">#{{ u.employee_number }}</span>
                    </div>
                </div>
            </div>

            <div v-else class="lg:col-span-12 p-20 bg-white dark:bg-gray-900 rounded-[4rem] border border-dashed border-slate-200 dark:border-gray-800 text-center shadow-inner">
                <UserIcon class="h-16 w-16 text-slate-300 dark:text-gray-700 mx-auto mb-4" />
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">No hay integrantes en tu plantilla actualmente</p>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal" max-width="md">
            <div class="p-10 dark:bg-gray-900 rounded-[2.5rem]">
                <div class="flex items-center gap-4 mb-8">
                    <div class="h-14 w-14 bg-gradient-to-tr from-tinto-900 to-tinto-800 rounded-2xl flex items-center justify-center text-oro-300 shadow-tinto-sm border border-oro-400/40">
                        <UserPlusIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ editingUser ? 'Editar Datos' : 'Nuevo Registro' }}</h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Personal de {{ area.name }}</p>
                    </div>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative h-24 w-24 rounded-3xl overflow-hidden border-4 border-slate-100 shadow-md bg-slate-50 flex items-center justify-center cursor-pointer group" @click="fileInput.click()">
                            <img v-if="previewAvatar" :src="previewAvatar" class="h-full w-full object-cover" />
                            <UserIcon v-else class="h-10 w-10 text-slate-300" />
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <PhotoIcon class="h-6 w-6 text-white" />
                            </div>
                        </div>
                        <input type="file" ref="fileInput" @change="handleAvatarChange" class="hidden" accept="image/*" />
                        <p class="text-[9px] font-black uppercase text-tinto-800 dark:text-oro-400 mt-3 tracking-widest cursor-pointer hover:underline" @click="fileInput.click()">Cambiar Fotografía</p>
                    </div>
                    <div class="space-y-4">
                        <div><InputLabel value="Nombre(s)" class="text-[10px] uppercase font-black ml-2 text-slate-400" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.first_name" required /></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><InputLabel value="A. Paterno" class="text-[10px] uppercase font-black ml-2 text-slate-400" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.last_name" required /></div>
                            <div><InputLabel value="A. Materno" class="text-[10px] uppercase font-black ml-2 text-slate-400" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.second_last_name" /></div>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white text-[11px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect" :disabled="form.processing">Guardar Registro</button>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
