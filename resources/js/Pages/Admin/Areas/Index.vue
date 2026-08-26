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
    PlusIcon, PencilSquareIcon, TrashIcon, BuildingOfficeIcon, UserGroupIcon, XMarkIcon,
    MagnifyingGlassIcon, ChevronDoubleRightIcon, ArrowUpCircleIcon, ArrowLeftIcon, CheckBadgeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ areas: Array, allAreas: Array, potentialManagers: Array });

const showModal = ref(false), editingArea = ref(null), search = ref(''), parentSearch = ref(''), showParentResults = ref(false), selectedRootId = ref('all');

const rootAreaColors = [
    { border: 'border-tinto-500/60 dark:border-tinto-700/60', bg: 'bg-tinto-50/30 dark:bg-tinto-950/30', text: 'text-tinto-800 dark:text-oro-300', icon: 'bg-gradient-to-tr from-tinto-900 to-tinto-800', badge: 'bg-tinto-900 text-oro-300 border border-oro-400/30' },
    { border: 'border-oro-500/60 dark:border-oro-700/60', bg: 'bg-oro-50/30 dark:bg-oro-950/30', text: 'text-oro-900 dark:text-oro-300', icon: 'bg-gradient-to-tr from-oro-700 to-oro-600', badge: 'bg-oro-700 text-white border border-oro-300/30' },
    { border: 'border-emerald-500/60 dark:border-emerald-700/60', bg: 'bg-emerald-50/30 dark:bg-emerald-950/30', text: 'text-nayarit-800 dark:text-emerald-300', icon: 'bg-gradient-to-tr from-nayarit-900 to-nayarit-700', badge: 'bg-nayarit-800 text-white' },
    { border: 'border-slate-300 dark:border-gray-700', bg: 'bg-slate-50/50 dark:bg-gray-800/40', text: 'text-slate-700 dark:text-gray-300', icon: 'bg-slate-700', badge: 'bg-slate-700 text-white' },
];

const getRootColor = (id) => {
    const idx = rootAreas.value.findIndex(a => a.id === id);
    return rootAreaColors[idx === -1 ? id % rootAreaColors.length : idx % rootAreaColors.length];
};

const rootAreas = computed(() => props.areas.filter(a => !a.parent_id).sort((a, b) => a.name.localeCompare(b.name)));

const filteredAreas = computed(() => {
    if (search.value) return props.areas.filter(a => a.name.toLowerCase().includes(search.value.toLowerCase()) || a.full_path.toLowerCase().includes(search.value.toLowerCase()));
    if (selectedRootId.value === 'all') return rootAreas.value;
    const rootName = props.areas.find(r => r.id === selectedRootId.value)?.name;
    return props.areas.filter(a => a.id === selectedRootId.value || a.full_path.startsWith(rootName + ' >'));
});

const filteredParentOptions = computed(() => {
    const options = props.allAreas.filter(a => !(editingArea.value && (a.id === editingArea.value.id || a.full_path.includes(editingArea.value.name))));
    return parentSearch.value ? options.filter(a => a.full_path.toLowerCase().includes(parentSearch.value.toLowerCase())) : options;
});

const selectParent = (a) => { if (!a) { form.parent_id = ''; parentSearch.value = ''; } else { form.parent_id = a.id; parentSearch.value = a.full_path; } showParentResults.value = false; };

const form = useForm({ name: '', parent_id: '', manager_id: '' });
const openCreateModal = () => { editingArea.value = null; form.reset(); parentSearch.value = ''; form.clearErrors(); showModal.value = true; };
const openEditModal = (a) => { editingArea.value = a; form.name = a.name; form.parent_id = a.parent_id || ''; parentSearch.value = a.parent?.full_path || ''; form.manager_id = a.manager_id || ''; showModal.value = true; };

const submit = () => {
    if (editingArea.value) form.put(route('areas.update', editingArea.value.id), { preserveScroll: true, onSuccess: () => closeModal() });
    else form.post(route('areas.store'), { preserveScroll: true, onSuccess: () => closeModal() });
};
const closeModal = () => { showModal.value = false; form.reset(); };
const deleteArea = (a) => { if (a.users_count > 0) return alert('Área con personal asignado.'); if (confirm('¿Eliminar área?')) form.delete(route('areas.destroy', a.id), { preserveScroll: true }); };
</script>

<template>
    <Head title="Áreas y Estructura SICOA" />

    <AuthenticatedLayout bento-tag="Organización">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 space-y-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="relative flex-1 w-full">
                            <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 h-6 w-6 text-tinto-700 dark:text-oro-400" />
                            <input type="text" v-model="search" placeholder="Buscar área o departamento..." 
                                   class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-tinto-700 transition-all dark:text-white shadow-inner font-medium" />
                        </div>
                        <button @click="openCreateModal" class="bg-gradient-to-r from-oro-600 to-oro-500 hover:from-oro-700 hover:to-oro-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-oro-sm border border-oro-300/40 transition-all hover:scale-105 active:scale-95 shrink-0 cursor-pointer">
                            <PlusIcon class="h-4 w-4 mr-2" stroke-width="4" /> Nueva Área
                        </button>
                    </div>

                    <div v-if="rootAreas.length > 0 && !search" class="flex flex-wrap gap-2 pt-4 border-t border-slate-100 dark:border-gray-800">
                        <button @click="selectedRootId = 'all'" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest border transition-all cursor-pointer" :class="selectedRootId === 'all' ? 'bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 border-oro-400/40 shadow-sm' : 'bg-slate-50 dark:bg-gray-800 text-slate-400 border-slate-200 dark:border-gray-700'">Estructura Raíz</button>
                        <button v-for="r in rootAreas" :key="r.id" @click="selectedRootId = r.id" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest border transition-all cursor-pointer" :class="selectedRootId === r.id ? 'bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 border-oro-400/40 shadow-sm' : 'bg-slate-50 dark:bg-gray-800 text-slate-400 border-slate-200 dark:border-gray-700'">{{ r.name }}</button>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="a in filteredAreas" :key="a.id" @click="!a.parent_id && selectedRootId === 'all' ? selectedRootId = a.id : null"
                     class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 border shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none transition-all flex flex-col group relative overflow-hidden"
                     :class="[!a.parent_id ? `${getRootColor(a.id).border} ${getRootColor(a.id).bg}` : 'border-slate-200/80 dark:border-gray-800', !a.parent_id && selectedRootId === 'all' ? 'cursor-pointer hover:scale-[1.03]' : '']">
                    
                    <div class="mb-4 h-6">
                        <span v-if="a.parent" class="text-[8px] font-black text-tinto-800 dark:text-oro-300 bg-tinto-50 dark:bg-tinto-950/60 px-2.5 py-1 rounded-lg border border-tinto-200 dark:border-tinto-800 uppercase tracking-widest truncate block max-w-full">↑ {{ a.parent.name }}</span>
                        <span v-else class="text-[8px] font-black px-3 py-1 rounded-lg uppercase tracking-widest shadow-sm" :class="getRootColor(a.id).badge">Órgano Principal</span>
                    </div>

                    <div class="absolute top-6 right-6 flex gap-2 opacity-0 group-hover:opacity-100 transition-all">
                        <button @click.stop="openEditModal(a)" class="p-2 bg-white dark:bg-gray-800 text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 cursor-pointer"><PencilSquareIcon class="h-4 w-4" /></button>
                        <button @click.stop="deleteArea(a)" class="p-2 bg-white dark:bg-gray-800 text-slate-400 hover:text-rose-600 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 cursor-pointer"><TrashIcon class="h-4 w-4" /></button>
                    </div>

                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center mb-4 shadow-sm border border-black/5" :class="!a.parent_id ? `${getRootColor(a.id).icon} text-white` : 'bg-tinto-50 dark:bg-tinto-950/60 text-tinto-700 dark:text-oro-400 border-tinto-200 dark:border-tinto-800'">
                        <span class="text-xl">🏢</span>
                    </div>

                    <h3 class="font-black text-sm text-slate-800 dark:text-gray-200 uppercase tracking-tight leading-snug mb-6 line-clamp-2">{{ a.name }}</h3>

                    <div class="mt-auto pt-4 border-t border-slate-100 dark:border-gray-800 flex flex-col gap-3">
                        <div class="flex justify-between items-center"><div class="flex items-center text-[9px] font-black text-slate-400 uppercase tracking-widest"><UserGroupIcon class="h-3.5 w-3.5 mr-1.5" />{{ a.users_count }} Directos</div><CheckBadgeIcon v-if="a.manager" class="h-4 w-4 text-emerald-500" /></div>
                        <div v-if="!a.parent_id" class="bg-white/60 dark:bg-black/20 p-3 rounded-2xl flex items-center justify-between border border-slate-200/60 dark:border-white/5"><span class="text-[8px] font-black text-slate-400 uppercase">Rama Total:</span><span class="text-xs font-black text-tinto-800 dark:text-oro-300">{{ a.total_branch_users }} personas</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Modal :show="showModal" @close="closeModal" max-width="md">
            <div class="p-10 dark:bg-gray-900 rounded-[2.5rem]">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 bg-gradient-to-tr from-tinto-900 to-tinto-800 rounded-2xl flex items-center justify-center text-oro-300 shadow-tinto-sm border border-oro-400/40"><BuildingOfficeIcon class="h-7 w-7" /></div>
                        <div><h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ editingArea ? 'Editar Área' : 'Nueva Área' }}</h3><p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Estructura Organizacional</p></div>
                    </div>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div><InputLabel value="Nombre del Área" class="text-[10px] uppercase font-black ml-2 text-slate-400" /><TextInput type="text" class="w-full !rounded-2xl" v-model="form.name" required /></div>
                    <div class="relative">
                        <InputLabel value="Depende de (Área Padre)" class="text-[10px] uppercase font-black ml-2 text-slate-400" />
                        <TextInput type="text" class="w-full !rounded-2xl" v-model="parentSearch" @focus="showParentResults = true" placeholder="Buscar área superior..." />
                        <div v-if="showParentResults" class="absolute z-[60] w-full mt-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl shadow-2xl max-h-48 overflow-y-auto"><div v-for="p in filteredParentOptions" :key="p.id" @click="selectParent(p)" class="p-3 hover:bg-tinto-50 dark:hover:bg-tinto-950/40 cursor-pointer border-b dark:border-gray-700"><p class="text-[9px] font-bold uppercase text-slate-700 dark:text-gray-200">{{ p.full_path }}</p></div><div @click="selectParent(null)" class="p-3 text-rose-500 text-center text-[9px] font-black uppercase hover:bg-rose-50 dark:hover:bg-rose-950/30 cursor-pointer">Ninguno (Órgano Raíz)</div></div>
                    </div>
                    <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white text-[11px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect" :disabled="form.processing">Guardar Área</button>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
