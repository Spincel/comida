<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    BuildingOfficeIcon,
    UserGroupIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    ChevronDoubleRightIcon,
    ArrowUpCircleIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    areas: Array,
    allAreas: Array, // For parent selection
    potentialManagers: Array,
});

const showModal = ref(false);
const editingArea = ref(null);
const search = ref('');
const parentSearch = ref('');
const showParentResults = ref(false);
const selectedRootId = ref('all');

const rootAreaColors = [
    { border: 'border-indigo-500', bg: 'bg-indigo-50/10', text: 'text-indigo-600', icon: 'bg-indigo-600', badge: 'bg-indigo-600' },
    { border: 'border-emerald-500', bg: 'bg-emerald-50/10', text: 'text-emerald-600', icon: 'bg-emerald-600', badge: 'bg-emerald-600' },
    { border: 'border-rose-500', bg: 'bg-rose-50/10', text: 'text-rose-600', icon: 'bg-rose-600', badge: 'bg-rose-600' },
    { border: 'border-amber-500', bg: 'bg-amber-50/10', text: 'text-amber-600', icon: 'bg-amber-600', badge: 'bg-amber-600' },
    { border: 'border-cyan-500', bg: 'bg-cyan-50/10', text: 'text-cyan-600', icon: 'bg-cyan-600', badge: 'bg-cyan-600' },
    { border: 'border-purple-500', bg: 'bg-purple-50/10', text: 'text-purple-600', icon: 'bg-purple-600', badge: 'bg-purple-600' },
    { border: 'border-orange-500', bg: 'bg-orange-50/10', text: 'text-orange-600', icon: 'bg-orange-600', badge: 'bg-orange-600' },
    { border: 'border-teal-500', bg: 'bg-teal-50/10', text: 'text-teal-600', icon: 'bg-teal-600', badge: 'bg-teal-600' },
    { border: 'border-pink-500', bg: 'bg-pink-50/10', text: 'text-pink-600', icon: 'bg-pink-600', badge: 'bg-pink-600' },
    { border: 'border-blue-500', bg: 'bg-blue-50/10', text: 'text-blue-600', icon: 'bg-blue-600', badge: 'bg-blue-600' },
];

const getRootColor = (id) => {
    // Find the index of this ID in the rootAreas list to ensure distinct colors for the first few items
    const index = rootAreas.value.findIndex(a => a.id === id);
    if (index === -1) return rootAreaColors[id % rootAreaColors.length];
    return rootAreaColors[index % rootAreaColors.length];
};

const rootAreas = computed(() => {
    return props.areas.filter(a => !a.parent_id).sort((a, b) => a.name.localeCompare(b.name));
});

const filteredAreas = computed(() => {
    let result = props.areas;

    // Search filter (Search bypasses the root-only view to find anything)
    if (search.value) {
        return result.filter(a => 
            a.name.toLowerCase().includes(search.value.toLowerCase()) ||
            a.parent?.name.toLowerCase().includes(search.value.toLowerCase()) ||
            a.full_path.toLowerCase().includes(search.value.toLowerCase())
        );
    }

    // Default View: Only Root Areas (Level 0)
    if (selectedRootId.value === 'all') {
        return rootAreas.value;
    }

    // Drill-down View: Root Area + All descendants
    const rootName = props.areas.find(r => r.id === selectedRootId.value)?.name;
    return result.filter(a => 
        a.id === selectedRootId.value || 
        a.full_path.startsWith(rootName + ' >')
    );
});

const filteredParentOptions = computed(() => {
    const options = props.allAreas.filter(area => {
        // Don't show current area or its children to prevent circular reference
        const isSelf = editingArea.value && area.id === editingArea.value.id;
        const isChild = editingArea.value && area.full_path.includes(editingArea.value.name);
        return !isSelf && !isChild;
    });

    if (!parentSearch.value) return options;
    return options.filter(a => a.full_path.toLowerCase().includes(parentSearch.value.toLowerCase()));
});

const selectParent = (area) => {
    if (!area) {
        form.parent_id = '';
        parentSearch.value = '';
    } else {
        form.parent_id = area.id;
        parentSearch.value = area.full_path;
    }
    showParentResults.value = false;
};

const form = useForm({
    name: '',
    parent_id: '',
    manager_id: '',
});

const openCreateModal = () => {
    editingArea.value = null;
    form.reset();
    parentSearch.value = '';
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (area) => {
    editingArea.value = area;
    form.name = area.name;
    form.parent_id = area.parent_id || '';
    parentSearch.value = area.parent?.full_path || '';
    form.manager_id = area.manager_id || '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editingArea.value) {
        form.put(route('areas.update', editingArea.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('areas.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const deleteArea = (area) => {
    if (area.users_count > 0) {
        alert('No puedes eliminar un área que tiene empleados asignados.');
        return;
    }
    if (confirm('¿Estás seguro de eliminar esta área?')) {
        form.delete(route('areas.destroy', area.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Catálogo de Áreas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Catálogo de Áreas
                    </h2>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Gestión de Departamentos</p>
                </div>
                
                <button @click="openCreateModal" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center shadow-lg shadow-indigo-100 dark:shadow-none transition-all">
                    <PlusIcon class="h-5 w-5 mr-2" stroke-width="3" /> Nueva Área
                </button>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- BUSCADOR -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-700 space-y-6">
                    <div class="flex items-center gap-4">
                        <button v-if="selectedRootId !== 'all'" 
                                @click="selectedRootId = 'all'"
                                class="p-4 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-2xl border border-indigo-100 dark:border-indigo-800 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                            <ArrowLeftIcon class="h-6 w-6" />
                        </button>
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-6 w-6 text-indigo-500" />
                            </div>
                            <input type="text" 
                                   v-model="search"
                                   placeholder="Buscar área por nombre o dependencia..." 
                                   class="block w-full pl-14 pr-6 py-4 bg-gray-50 dark:bg-gray-900 border-none rounded-[1.5rem] text-base focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white" />
                        </div>
                    </div>

                    <!-- PESTAÑAS DINÁMICAS (ÓRGANOS PRINCIPALES) -->
                    <div v-if="rootAreas.length > 0" class="flex flex-wrap gap-2 pt-2 border-t border-gray-50 dark:border-gray-700">
                        <button @click="selectedRootId = 'all'" 
                                class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-[0.1em] transition-all border"
                                :class="selectedRootId === 'all' ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-100 dark:shadow-none' : 'bg-gray-50 dark:bg-gray-900 text-gray-400 border-gray-100 dark:border-gray-800 hover:border-indigo-300'">
                            Órganos Principales
                        </button>
                        <button v-for="root in rootAreas" :key="'tab-' + root.id"
                                @click="selectedRootId = root.id" 
                                class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-[0.1em] transition-all border"
                                :class="selectedRootId === root.id ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-100 dark:shadow-none' : 'bg-gray-50 dark:bg-gray-900 text-gray-400 border-gray-100 dark:border-gray-800 hover:border-indigo-300'">
                            {{ root.name }}
                        </button>
                    </div>
                </div>

                <!-- TÍTULO DE CONTEXTO -->
                <div v-if="selectedRootId !== 'all' && !search" class="px-4">
                    <h4 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight flex items-center">
                        <BuildingOfficeIcon class="h-6 w-6 mr-2 text-indigo-500" />
                        Rama Jerárquica: {{ props.areas.find(r => r.id === selectedRootId)?.name }}
                    </h4>
                </div>

                <div v-if="filteredAreas.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="area in filteredAreas" :key="area.id" 
                         @click="!area.parent_id && selectedRootId === 'all' ? selectedRootId = area.id : null"
                         class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-6 border-2 shadow-xl transition-all flex flex-col group relative overflow-hidden"
                         :class="[
                            !area.parent_id ? `${getRootColor(area.id).border} ${getRootColor(area.id).bg}` : 'border-gray-100 dark:border-gray-700',
                            !area.parent_id && selectedRootId === 'all' ? 'cursor-pointer hover:scale-[1.03]' : ''
                         ]">
                        
                        <!-- Dependencia Superior (Parent) -->
                        <div class="mb-4 h-6">
                            <span v-if="area.parent" class="flex items-center text-[7px] font-black text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded-lg border border-indigo-100 dark:border-indigo-800 uppercase tracking-widest w-fit truncate max-w-full">
                                <ArrowUpCircleIcon class="h-2.5 w-2.5 mr-1" />
                                {{ area.full_path.split(' > ').slice(0, -1).join(' > ') }}
                            </span>
                            <span v-else class="text-[8px] font-black text-white px-3 py-1 rounded-lg uppercase tracking-[0.2em] w-fit shadow-lg dark:shadow-none"
                                  :class="getRootColor(area.id).badge">
                                Órgano Principal
                            </span>
                        </div>

                        <!-- Acciones -->
                        <div class="absolute top-6 right-6 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click.stop="openEditModal(area)" class="p-2 bg-white dark:bg-gray-700 text-gray-400 hover:text-indigo-600 rounded-xl transition-all shadow-sm border border-gray-100 dark:border-gray-600">
                                <PencilSquareIcon class="h-4 w-4" />
                            </button>
                            <button @click.stop="deleteArea(area)" class="p-2 bg-white dark:bg-gray-700 text-gray-400 hover:text-red-600 rounded-xl transition-all shadow-sm border border-gray-100 dark:border-gray-600">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="h-12 w-12 rounded-2xl flex items-center justify-center mb-4 shadow-sm"
                             :class="!area.parent_id ? `${getRootColor(area.id).icon} text-white` : 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'">
                            <BuildingOfficeIcon class="h-6 w-6" />
                        </div>

                        <h3 class="font-black text-sm text-gray-900 dark:text-white uppercase tracking-tight leading-snug mb-4 min-h-[2.5rem] line-clamp-3">
                            {{ area.name }}
                        </h3>

                        <div class="mt-auto pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-col gap-3">
                            <div class="flex justify-between items-center w-full">
                                <div class="flex items-center text-[9px] font-black text-gray-500 uppercase tracking-widest">
                                    <UserGroupIcon class="h-3.5 w-3.5 mr-1.5 text-indigo-400" />
                                    {{ area.users_count }} Directos
                                </div>
                                <div v-if="area.manager" class="flex items-center text-[9px] font-black text-emerald-600 uppercase tracking-widest">
                                    <CheckBadgeIcon class="h-3.5 w-3.5 mr-1" /> Gestión
                                </div>
                            </div>
                            
                            <!-- TOTAL DE LA RAMA (Recursivo) -->
                            <div v-if="!area.parent_id" 
                                 class="bg-white/50 dark:bg-black/20 p-3 rounded-2xl flex items-center justify-between border border-white/50 dark:border-white/5 shadow-inner">
                                <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Total en Rama:</span>
                                <span class="text-xs font-black px-2.5 py-1 rounded-xl bg-gray-900 text-white dark:bg-indigo-500">
                                    {{ area.total_branch_users }} Colaboradores
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-800 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700 text-center">
                    <BuildingOfficeIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm">No hay áreas registradas</p>
                </div>

            </div>
        </div>

        <!-- MODAL CREAR/EDITAR -->
        <Modal :show="showModal" @close="closeModal" max-width="md" :overflowVisible="true">
            <!-- Overlay invisible para cerrar el dropdown del buscador -->
            <div v-if="showParentResults" @click="showParentResults = false" class="fixed inset-0 z-50"></div>
            
            <div class="p-8 relative z-[55]">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">
                        {{ editingArea ? 'Editar Área' : 'Crear Nueva Área' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-6 w-6" /></button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel value="Nombre del Área" class="text-[10px] font-black uppercase text-gray-400" />
                        <TextInput type="text" class="mt-1 block w-full !rounded-2xl text-sm" v-model="form.name" required autofocus placeholder="Ej. DIRECCIÓN DE TESORERÍA" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="relative">
                        <InputLabel value="Superior Jerárquico (Depende de:)" class="text-[10px] font-black uppercase text-gray-400" />
                        
                        <div class="relative mt-1">
                            <TextInput 
                                type="text" 
                                class="block w-full !rounded-2xl text-sm pr-10" 
                                v-model="parentSearch"
                                @focus="showParentResults = true"
                                placeholder="Escribe para buscar área superior..." 
                            />
                            <div v-if="parentSearch" @click="selectParent(null)" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-red-500">
                                <XMarkIcon class="h-4 w-4" />
                            </div>
                        </div>

                        <!-- Dropdown de resultados -->
                        <div v-if="showParentResults" class="absolute z-[60] w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar">
                            <div class="p-2 border-b border-gray-50 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                                <p class="text-[8px] font-black uppercase text-gray-400 px-2 tracking-widest">Resultados Sugeridos</p>
                            </div>
                            <div v-if="filteredParentOptions.length === 0" class="p-4 text-center text-xs text-gray-400 italic">
                                No se encontraron coincidencias
                            </div>
                            <div v-for="area in filteredParentOptions" 
                                 :key="'parent-opt-' + area.id"
                                 @click="selectParent(area)"
                                 class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-colors border-b border-gray-50 dark:border-gray-700 last:border-0">
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-tight">{{ area.full_path }}</p>
                            </div>
                            <div v-if="!parentSearch" @click="selectParent(null)" class="p-3 bg-red-50/30 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 cursor-pointer transition-colors text-center text-[10px] font-black uppercase tracking-widest">
                                Ninguno (Convertir en Raíz)
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.parent_id" />
                    </div>

                    <div class="pt-4">
                        <PrimaryButton class="w-full !rounded-2xl !py-4 justify-center text-xs tracking-widest" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ editingArea ? 'Guardar Cambios' : 'Crear Área' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
