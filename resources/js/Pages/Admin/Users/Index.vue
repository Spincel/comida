<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, BuildingOfficeIcon, EnvelopeIcon, XMarkIcon,
    IdentificationIcon, PhotoIcon, MagnifyingGlassIcon, FunnelIcon, UserIcon, UsersIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    users: Object, areas: Array, filters: Object,
});

const search = ref(props.filters.search || '');
const areaFilter = ref(props.filters.area_id || '');
const roleFilter = ref(props.filters.role || '');
const areaSearch = ref('');
const showAreaResults = ref(false);

onMounted(() => {
    if (areaFilter.value) {
        const activeArea = props.areas.find(a => a.id == areaFilter.value);
        if (activeArea) areaSearch.value = activeArea.full_path;
    }
});

const filteredAreaOptions = computed(() => {
    if (!areaSearch.value) return props.areas;
    return props.areas.filter(a => a.full_path.toLowerCase().includes(areaSearch.value.toLowerCase()));
});

const selectAreaFilter = (area) => {
    if (!area) { areaFilter.value = ''; areaSearch.value = ''; } 
    else { areaFilter.value = area.id; areaSearch.value = area.full_path; }
    showAreaResults.value = false;
};

watch([search, areaFilter, roleFilter], ([newSearch, newArea, newRole]) => {
    router.get(route('users.index'), { search: newSearch, area_id: newArea, role: newRole }, { preserveState: true, preserveScroll: true, replace: true });
});

const showModal = ref(false), editingUser = ref(null), previewAvatar = ref(null), fileInput = ref(null);
const modalAreaSearch = ref(''), showModalAreaResults = ref(false);

const filteredModalAreaOptions = computed(() => {
    if (!modalAreaSearch.value) return props.areas;
    return props.areas.filter(a => a.full_path.toLowerCase().includes(modalAreaSearch.value.toLowerCase()));
});

const selectModalArea = (area) => {
    if (!area) { form.area_id = ''; modalAreaSearch.value = ''; } 
    else { form.area_id = area.id; modalAreaSearch.value = area.full_path; }
    showModalAreaResults.value = false;
};

const form = useForm({
    first_name: '', last_name: '', second_last_name: '', employee_number: '', username: '', email: '',
    password: '', password_confirmation: '', role: 'diner', area_id: '', avatar: null,
});

const openCreateModal = () => { editingUser.value = null; form.reset(); modalAreaSearch.value = ''; form.clearErrors(); previewAvatar.value = null; showModal.value = true; };
const openEditModal = (u) => {
    editingUser.value = u;
    form.first_name = u.first_name || ''; form.last_name = u.last_name || ''; form.second_last_name = u.second_last_name || '';
    form.employee_number = u.employee_number || ''; form.username = u.username || ''; form.email = u.email || '';
    form.role = u.role; form.area_id = u.area_id || ''; modalAreaSearch.value = u.area?.full_path || '';
    form.password = ''; form.password_confirmation = ''; form.avatar = null; form.clearErrors(); previewAvatar.value = u.avatar_url;
    showModal.value = true;
};

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
        router.post(route('users.update', editingUser.value.id), { _method: 'put', ...form.data() }, { forceFormData: true, preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        form.post(route('users.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => closeModal() });
    }
};

const closeModal = () => { showModal.value = false; form.reset(); };
const deleteUser = (id) => { if (confirm('¿Eliminar usuario?')) router.delete(route('users.destroy', id), { preserveScroll: true }); };

const roleLabels = { admin: 'Administrador', acquisitions_manager: 'Adquisiciones', area_manager: 'Gerente de Área', diner: 'Comensal' };
const roleColors = { 
    admin: 'bg-indigo-100 text-indigo-700', 
    acquisitions_manager: 'bg-emerald-100 text-emerald-700', 
    area_manager: 'bg-amber-100 text-amber-700', 
    diner: 'bg-slate-100 text-slate-500' 
};
const areaColors = ['bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-rose-100 text-rose-700', 'bg-amber-100 text-amber-700'];
const getAreaColor = (id) => id ? areaColors[id % areaColors.length] : 'bg-slate-50 text-slate-400';
</script>

<template>
    <Head title="Usuarios V2.0" />

    <AuthenticatedLayout bento-tag="Usuarios">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- SEARCH & FILTERS -->
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 space-y-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="relative flex-1 w-full">
                            <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 h-6 w-6 text-indigo-500" />
                            <input type="text" v-model="search" placeholder="Buscar usuarios, correos, áreas..." 
                                   class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white shadow-inner" />
                        </div>
                        <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-xl shadow-indigo-500/20 transition-all hover:scale-105 active:scale-95 shrink-0">
                            <PlusIcon class="h-4 w-4 mr-2" stroke-width="4" /> Nuevo Registro
                        </button>
                    </div>

                    <div class="flex flex-col md:flex-row gap-6 relative z-40">
                        <div class="flex-1 relative">
                            <label class="ml-4 mb-2 text-[10px] font-black uppercase text-slate-400 tracking-widest block">Filtrar por Área</label>
                            <input type="text" v-model="areaSearch" @focus="showAreaResults = true" placeholder="Buscar área..."
                                   class="w-full pl-6 pr-10 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-xs font-bold uppercase focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all shadow-inner" />
                            <div v-if="showAreaResults" class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border rounded-2xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar">
                                <div @click="selectAreaFilter(null)" class="p-3 hover:bg-slate-50 cursor-pointer text-[10px] font-black uppercase text-slate-400">Ver Todas</div>
                                <div v-for="a in filteredAreaOptions" :key="a.id" @click="selectAreaFilter(a)" class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer border-b last:border-0"><p class="text-[10px] font-bold text-slate-700 dark:text-gray-200 uppercase">{{ a.full_path }}</p></div>
                            </div>
                        </div>
                        <div class="flex-1">
                            <label class="ml-4 mb-2 text-[10px] font-black uppercase text-slate-400 tracking-widest block">Filtrar por Rol</label>
                            <select v-model="roleFilter" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-xs font-bold uppercase focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all shadow-inner appearance-none">
                                <option value="">Cualquier Rol</option>
                                <option v-for="(l, v) in roleLabels" :key="v" :value="v">{{ l }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- USERS TABLE -->
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-gray-800/50 border-b border-slate-100 dark:border-gray-800">
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Identidad</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Acceso</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Organización</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y border-slate-50 dark:divide-gray-800">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-indigo-50/20 dark:hover:bg-indigo-900/5 transition-all group">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <img :src="u.avatar_url" class="h-12 w-12 rounded-2xl border-2 border-white dark:border-gray-700 shadow-md object-cover group-hover:scale-110 transition-transform" />
                                        <div>
                                            <p class="font-black text-sm text-slate-800 dark:text-gray-200 uppercase tracking-tight">{{ u.name }}</p>
                                            <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest">#{{ u.employee_number || 'S/N' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <p class="font-black text-xs text-slate-700 dark:text-gray-300">@{{ u.username }}</p>
                                    <p class="text-[10px] text-slate-400 lowercase">{{ u.email || 'sin email' }}</p>
                                </td>
                                <td class="p-6">
                                    <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest mb-2 inline-block border shadow-sm" :class="roleColors[u.role]">{{ roleLabels[u.role] }}</span>
                                    <div class="text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-tighter">{{ u.area?.name || 'Sin área' }}</div>
                                </td>
                                <td class="p-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openEditModal(u)" class="p-2.5 bg-slate-50 dark:bg-gray-800 text-slate-400 hover:text-indigo-600 rounded-xl transition-all border border-transparent hover:border-indigo-100"><PencilSquareIcon class="h-5 w-5" /></button>
                                        <button v-if="user.id !== u.id" @click="deleteUser(u.id)" class="p-2.5 bg-rose-50 dark:bg-rose-950/20 text-rose-300 hover:text-rose-600 rounded-xl transition-all border border-transparent hover:border-rose-100"><TrashIcon class="h-5 w-5" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-8"><Pagination :links="users.links" /></div>
            </div>
        </div>

        <!-- MODAL -->
        <Modal :show="showModal" @close="closeModal" max-width="2xl">
            <div class="p-10 dark:bg-gray-900 relative">
                <div class="flex justify-between items-center mb-10">
                    <div class="flex items-center gap-6">
                        <div class="h-16 w-16 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-xl"><UsersIcon class="h-8 w-8" /></div>
                        <div><h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">{{ editingUser ? 'Actualizar Perfil' : 'Alta de Usuario' }}</h3><p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Gestión Maestra de Personal</p></div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex flex-col items-center mb-8">
                        <div class="relative h-28 w-28 rounded-3xl overflow-hidden border-4 border-slate-50 dark:border-gray-800 shadow-2xl bg-slate-100 dark:bg-gray-800 flex items-center justify-center cursor-pointer group" @click="fileInput.click()">
                            <img v-if="previewAvatar" :src="previewAvatar" class="h-full w-full object-cover" />
                            <UserIcon v-else class="h-12 w-12 text-slate-300" />
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"><PhotoIcon class="h-8 w-8 text-white" /></div>
                        </div>
                        <input type="file" ref="fileInput" @change="handleAvatarChange" class="hidden" accept="image/*" />
                        <p class="text-[9px] font-black uppercase text-indigo-500 mt-4 tracking-[0.2em] cursor-pointer hover:underline" @click="fileInput.click()">Subir nueva fotografía</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div><InputLabel value="Nombre(s)" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.first_name" required /></div>
                        <div><InputLabel value="A. Paterno" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.last_name" required /></div>
                        <div><InputLabel value="A. Materno" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.second_last_name" /></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><InputLabel value="No. Empleado" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.employee_number" /></div>
                        <div><InputLabel value="Nombre de Usuario" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.username" /></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t dark:border-gray-800">
                        <div><InputLabel value="Asignar Rol" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><select v-model="form.role" class="w-full rounded-2xl border-gray-300 dark:bg-gray-800 dark:text-gray-300 text-sm"><option v-for="(l, v) in roleLabels" :key="v" :value="v">{{ l }}</option></select></div>
                        <div class="relative">
                            <InputLabel value="Departamento / Área" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" />
                            <TextInput type="text" class="w-full !rounded-2xl" v-model="modalAreaSearch" @focus="showModalAreaResults = true" placeholder="Escribe para buscar..." />
                            <div v-if="showModalAreaResults" class="absolute z-[70] w-full mt-2 bg-white dark:bg-gray-800 border rounded-2xl shadow-2xl max-h-48 overflow-y-auto"><div v-for="a in filteredModalAreaOptions" :key="a.id" @click="selectModalArea(a)" class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer border-b last:border-0"><p class="text-[9px] font-bold uppercase">{{ a.full_path }}</p></div></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><InputLabel value="Contraseña" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><TextInput type="password" class="w-full !rounded-2xl" v-model="form.password" :required="!editingUser" /></div>
                        <div><InputLabel value="Confirmar" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2" /><TextInput type="password" class="w-full !rounded-2xl" v-model="form.password_confirmation" :required="!editingUser" /></div>
                    </div>

                    <PrimaryButton class="w-full !rounded-2xl !py-5 justify-center text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-indigo-500/20 transition-all active:scale-95" :disabled="form.processing">{{ editingUser ? 'Actualizar Datos' : 'Registrar Usuario' }}</PrimaryButton>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
