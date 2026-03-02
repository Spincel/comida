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
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    BuildingOfficeIcon,
    EnvelopeIcon,
    XMarkIcon,
    IdentificationIcon,
    PhotoIcon,
    MagnifyingGlassIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    users: Object, // Now a pagination object
    areas: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const areaFilter = ref(props.filters.area_id || '');
const roleFilter = ref(props.filters.role || '');

// New states for searchable area filter
const areaSearch = ref('');
const showAreaResults = ref(false);

// Initialize areaSearch if there's a filter active
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
    if (!area) {
        areaFilter.value = '';
        areaSearch.value = '';
    } else {
        areaFilter.value = area.id;
        areaSearch.value = area.full_path;
    }
    showAreaResults.value = false;
};

// Watch for filter changes to update the URL
watch([search, areaFilter, roleFilter], ([newSearch, newArea, newRole]) => {
    router.get(route('users.index'), { 
        search: newSearch, 
        area_id: newArea, 
        role: newRole 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

const showModal = ref(false);
const editingUser = ref(null);
const previewAvatar = ref(null);
const fileInput = ref(null);

// Search logic for Modal Area Selector
const modalAreaSearch = ref('');
const showModalAreaResults = ref(false);

const filteredModalAreaOptions = computed(() => {
    if (!modalAreaSearch.value) return props.areas;
    return props.areas.filter(a => a.full_path.toLowerCase().includes(modalAreaSearch.value.toLowerCase()));
});

const selectModalArea = (area) => {
    if (!area) {
        form.area_id = '';
        modalAreaSearch.value = '';
    } else {
        form.area_id = area.id;
        modalAreaSearch.value = area.full_path;
    }
    showModalAreaResults.value = false;
};

const form = useForm({
    first_name: '',
    last_name: '',
    second_last_name: '',
    employee_number: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'diner',
    area_id: '',
    avatar: null,
});

const openCreateModal = () => {
    editingUser.value = null;
    form.reset();
    modalAreaSearch.value = '';
    form.clearErrors();
    previewAvatar.value = null;
    if (fileInput.value) fileInput.value.value = '';
    showModal.value = true;
};

const openEditModal = (user) => {
    editingUser.value = user;
    form.first_name = user.first_name || '';
    form.last_name = user.last_name || '';
    form.second_last_name = user.second_last_name || '';
    form.employee_number = user.employee_number || '';
    form.username = user.username || '';
    form.email = user.email || '';
    form.role = user.role;
    form.area_id = user.area_id || '';
    modalAreaSearch.value = user.area?.full_path || '';
    form.password = '';
    form.password_confirmation = '';
    form.avatar = null;
    form.clearErrors();
    previewAvatar.value = user.avatar_url;
    if (fileInput.value) fileInput.value.value = '';
    showModal.value = true;
};

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            previewAvatar.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    if (editingUser.value) {
        // FIX: Laravel needs POST + _method=PUT for multipart/form-data (avatars)
        router.post(route('users.update', editingUser.value.id), {
            _method: 'put',
            ...form.data(),
        }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    delete form._method;
};

const deleteUser = (id) => {
    if (confirm('¿Estás seguro de eliminar este usuario?')) {
        router.delete(route('users.destroy', id), { preserveScroll: true });
    }
};

const roleLabels = {
    admin: 'Administrador',
    acquisitions_manager: 'Adquisiciones',
    area_manager: 'Gerente de Área',
    diner: 'Comensal',
};

const roleColors = {
    admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    acquisitions_manager: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    area_manager: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    diner: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
};

const areaColors = [
    'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
    'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
    'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800',
    'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
    'bg-teal-100 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-400 dark:border-teal-800',
    'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800',
    'bg-cyan-100 text-cyan-700 border-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-400 dark:border-cyan-800',
    'bg-pink-100 text-fuchsia-700 border-pink-200 dark:bg-pink-900/30 dark:text-pink-400 dark:border-pink-800',
];

const getAreaColor = (areaId) => {
    if (!areaId) return 'bg-gray-100 text-gray-500 border-gray-200';
    return areaColors[areaId % areaColors.length];
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Gestión de Usuarios
                    </h2>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Control de Accesos y Roles</p>
                </div>
                
                <button @click="openCreateModal" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center shadow-lg shadow-indigo-100 dark:shadow-none transition-all">
                    <PlusIcon class="h-5 w-5 mr-2" stroke-width="3" /> Nuevo Usuario
                </button>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- TOOLBAR: BÚSQUEDA Y FILTROS -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[3rem] shadow-2xl border border-gray-100 dark:border-gray-700 space-y-6">
                    <!-- Fila 1: Buscador Principal -->
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-6 w-6 text-indigo-500" />
                        </div>
                        <input type="text" 
                               v-model="search"
                               placeholder="Buscar por nombre, email, usuario, # empleado, área o rol..." 
                               class="block w-full pl-14 pr-6 py-4 bg-gray-50 dark:bg-gray-900 border-none rounded-[1.5rem] text-base focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white shadow-inner" />
                    </div>
                    
                    <!-- Fila 2: Filtros Específicos -->
                    <div class="flex flex-col md:flex-row gap-6 relative">
                        <!-- Overlay invisible para cerrar dropdowns -->
                        <div v-if="showAreaResults" @click="showAreaResults = false" class="fixed inset-0 z-[40]"></div>

                        <div class="flex-1 relative z-[45]">
                            <InputLabel value="Filtrar por Área" class="ml-4 mb-2 text-[10px] font-black uppercase text-gray-400 tracking-[0.2em]" />
                            <div class="relative">
                                <input type="text" 
                                       v-model="areaSearch"
                                       @focus="showAreaResults = true"
                                       placeholder="Escribe para buscar área..."
                                       class="w-full pl-12 pr-10 py-4 bg-gray-50 dark:bg-gray-900 border-none rounded-2xl text-xs font-bold uppercase tracking-widest focus:ring-2 focus:ring-indigo-500 dark:text-white shadow-sm transition-all" />
                                <BuildingOfficeIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-indigo-400 pointer-events-none" />
                                <div v-if="areaSearch" @click="selectAreaFilter(null)" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-red-500">
                                    <XMarkIcon class="h-4 w-4" />
                                </div>
                            </div>

                            <!-- Dropdown de resultados -->
                            <div v-if="showAreaResults" class="absolute z-[50] w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar">
                                <div v-if="filteredAreaOptions.length === 0" class="p-4 text-center text-xs text-gray-400 italic">No hay coincidencias</div>
                                <div v-for="area in filteredAreaOptions" 
                                     :key="'filter-area-' + area.id"
                                     @click="selectAreaFilter(area)"
                                     class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-colors border-b border-gray-50 dark:border-gray-700 last:border-0">
                                    <p class="text-[10px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-tight">{{ area.full_path }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 relative">
                            <InputLabel value="Filtrar por Rol" class="ml-4 mb-2 text-[10px] font-black uppercase text-gray-400 tracking-[0.2em]" />
                            <div class="relative">
                                <select v-model="roleFilter" 
                                        class="w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-900 border-none rounded-2xl text-xs font-bold uppercase tracking-widest focus:ring-2 focus:ring-indigo-500 appearance-none dark:text-white shadow-sm cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <option value="">Cualquier Rol</option>
                                    <option v-for="(label, value) in roleLabels" :key="value" :value="value">{{ label }}</option>
                                </select>
                                <FunnelIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-indigo-400 pointer-events-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-[3rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/50">
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Empleado</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Usuario / Email</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Rol / Área</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-indigo-50/20 dark:hover:bg-indigo-900/5 transition-colors">
                                    <td class="p-6">
                                        <div class="flex items-center">
                                            <img :src="user.avatar_url" class="h-10 w-10 rounded-full mr-4 border-2 border-white dark:border-gray-700 shadow-sm object-cover" alt="" />
                                            <div>
                                                <p class="font-black text-sm text-gray-800 dark:text-gray-200 uppercase tracking-tight">{{ user.name }}</p>
                                                <p v-if="user.employee_number" class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest flex items-center mt-0.5">
                                                    <IdentificationIcon class="h-3 w-3 mr-1" /> #{{ user.employee_number }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <p class="font-black text-xs text-gray-800 dark:text-gray-200 mb-1">@{{ user.username }}</p>
                                        <p v-if="user.email" class="text-[10px] font-bold text-gray-400 flex items-center lowercase">
                                            <EnvelopeIcon class="h-3 w-3 mr-1" /> {{ user.email }}
                                        </p>
                                        <span v-else class="text-[10px] text-gray-300 dark:text-gray-600 italic">Sin correo</span>
                                    </td>
                                    <td class="p-6">
                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest mb-2 inline-block shadow-sm" :class="roleColors[user.role]">
                                            {{ roleLabels[user.role] }}
                                        </span>
                                        <div v-if="user.area" class="flex items-center">
                                            <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border shadow-sm" :class="getAreaColor(user.area_id)">
                                                {{ user.area.name }}
                                            </span>
                                        </div>
                                        <span v-else class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic">Sin área</span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button @click="openEditModal(user)" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-400 hover:text-indigo-600 rounded-xl transition-all shadow-sm">
                                                <PencilSquareIcon class="h-5 w-5" />
                                            </button>
                                            <button v-if="$page.props.auth.user.id !== user.id" @click="deleteUser(user.id)" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-400 hover:text-red-600 rounded-xl transition-all shadow-sm">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="users.links" />

            </div>
        </div>

        <!-- MODAL CREAR/EDITAR -->
        <Modal :show="showModal" @close="closeModal" max-width="2xl" :overflowVisible="true">
            <!-- Overlay invisible para cerrar dropdown del buscador -->
            <div v-if="showModalAreaResults" @click="showModalAreaResults = false" class="fixed inset-0 z-[60]"></div>

            <div class="p-8 relative z-[65]">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">
                        {{ editingUser ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-6 w-6" /></button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- FOTO DE PERFIL -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative h-24 w-24 rounded-full overflow-hidden border-4 border-indigo-50 dark:border-indigo-900/50 shadow-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center cursor-pointer group"
                             @click="fileInput.click()">
                            <img v-if="previewAvatar" :src="previewAvatar" class="h-full w-full object-cover" />
                            <UserIcon v-else class="h-10 w-10 text-gray-300 dark:text-gray-600" />
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <PhotoIcon class="h-6 w-6 text-white" />
                            </div>
                        </div>
                        <p class="text-[10px] font-black uppercase text-gray-400 mt-3 tracking-widest cursor-pointer hover:text-indigo-500 transition-colors" @click="fileInput.click()">
                            Cambiar Foto de Perfil
                        </p>
                        <input type="file" ref="fileInput" @change="handleAvatarChange" class="hidden" accept="image/*" />
                        <InputError class="mt-2 text-center" :message="form.errors.avatar" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Nombre(s)" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput type="text" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.first_name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.first_name" />
                        </div>
                        <div>
                            <InputLabel value="Apellido Paterno" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput type="text" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.last_name" required />
                            <InputError class="mt-2" :message="form.errors.last_name" />
                        </div>
                        <div>
                            <InputLabel value="Apellido Materno" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput type="text" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.second_last_name" />
                            <InputError class="mt-2" :message="form.errors.second_last_name" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="No. de Empleado" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput type="text" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.employee_number" placeholder="Ej. 10452" />
                            <InputError class="mt-2" :message="form.errors.employee_number" />
                        </div>
                        <div>
                            <InputLabel value="Nombre de Usuario" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput type="text" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.username" placeholder="Opcional (Auto-generado)" />
                            <p class="text-[8px] text-gray-400 mt-1">Con esto podrá iniciar sesión.</p>
                            <InputError class="mt-2" :message="form.errors.username" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Correo Electrónico (Opcional)" class="text-[10px] font-black uppercase text-gray-400" />
                        <TextInput type="email" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.email" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t dark:border-gray-700">
                        <div>
                            <InputLabel value="Rol de Usuario" class="text-[10px] font-black uppercase text-gray-400" />
                            <select v-model="form.role" class="mt-1 block w-full rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                                <option v-for="(label, value) in roleLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>
                        <div class="relative">
                            <InputLabel value="Área Asignada" class="text-[10px] font-black uppercase text-gray-400" />
                            <div class="relative mt-1">
                                <TextInput 
                                    type="text" 
                                    class="block w-full !rounded-2xl text-sm pr-10" 
                                    v-model="modalAreaSearch"
                                    @focus="showModalAreaResults = true"
                                    placeholder="Buscar área por nombre..." 
                                />
                                <div v-if="modalAreaSearch" @click="selectModalArea(null)" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-red-500">
                                    <XMarkIcon class="h-4 w-4" />
                                </div>
                            </div>

                            <!-- Dropdown de resultados -->
                            <div v-if="showModalAreaResults" class="absolute z-[70] w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar">
                                <div v-if="filteredModalAreaOptions.length === 0" class="p-4 text-center text-xs text-gray-400 italic">No hay coincidencias</div>
                                <div v-for="area in filteredModalAreaOptions" 
                                     :key="'modal-area-' + area.id"
                                     @click="selectModalArea(area)"
                                     class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-colors border-b border-gray-50 dark:border-gray-700 last:border-0">
                                    <p class="text-[10px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-tight">{{ area.full_path }}</p>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.area_id" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel :value="editingUser ? 'Nueva Contraseña (Opcional)' : 'Contraseña'" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput type="password" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.password" :required="!editingUser" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel value="Confirmar Contraseña" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput type="password" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.password_confirmation" :required="!editingUser" />
                        </div>
                    </div>

                    <div class="pt-6">
                        <PrimaryButton class="w-full !rounded-2xl !py-4 justify-center text-xs tracking-widest" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ editingUser ? 'Actualizar Usuario' : 'Crear Usuario' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
