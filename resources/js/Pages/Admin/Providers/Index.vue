<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ScanMenuModal from '@/Pages/Admin/Partials/ScanMenuModal.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    ClipboardDocumentListIcon, 
    PhotoIcon,
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    BuildingStorefrontIcon,
    ChevronDownIcon,
    ShieldCheckIcon,
    ListBulletIcon,
    ArrowLeftIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    providers: Array,
    auth: Object
});

const showScanModal = ref(false);
const selectedProviderForScan = ref(null);

const openScanModal = (provider) => {
    selectedProviderForScan.value = provider;
    showScanModal.value = true;
};

const deleteProvider = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar este proveedor?')) {
        router.delete(route('providers.destroy', id), {
            preserveScroll: true,
        });
    }
};

const providerColors = [
    { bg: 'bg-indigo-50 dark:bg-indigo-900/20', text: 'text-indigo-600 dark:text-indigo-400' },
    { bg: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-600 dark:text-emerald-400' },
    { bg: 'bg-rose-50 dark:bg-rose-950/20', text: 'text-rose-600 dark:text-rose-400' },
    { bg: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-600 dark:text-amber-400' },
];

const getProviderColor = (index) => providerColors[index % providerColors.length];

const isProfileComplete = (provider) => {
    return !!(provider.name && provider.address && provider.contact_person && provider.contact_phone && provider.contact_email);
};
</script>

<template>
    <Head title="Proveedores V2.0" />

    <AuthenticatedLayout bento-tag="Gestión">
        <!-- MAIN BENTO CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- LEFT COLUMN: PROVIDER LIST -->
            <div class="lg:col-span-8 space-y-8">
                
                <div class="flex justify-between items-center bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-gray-800 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center">
                            <ListBulletIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter">Catálogo de Proveedores</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ providers.length }} entidades registradas</p>
                        </div>
                    </div>
                    <Link :href="route('providers.create')" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] flex items-center shadow-xl shadow-indigo-500/20 transition-all hover:scale-105 active:scale-95">
                        <PlusIcon class="h-4 w-4 mr-2" stroke-width="4" /> Nuevo Proveedor
                    </Link>
                </div>

                <div v-if="providers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div v-for="(provider, index) in providers" :key="provider.id" 
                            class="bg-white dark:bg-gray-900 rounded-[3rem] p-8 border border-slate-100 dark:border-gray-800 shadow-xl transition-all group relative overflow-hidden">
                        
                        <div class="flex justify-between items-start mb-6">
                            <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center shadow-lg transition-transform group-hover:scale-110"
                                    :class="getProviderColor(index).bg + ' ' + getProviderColor(index).text">
                                <BuildingStorefrontIcon class="h-9 w-9" />
                            </div>
                            <div class="flex gap-2">
                                <Link :href="route('providers.edit', provider.id)" class="p-2.5 bg-slate-50 dark:bg-gray-800 rounded-xl text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 border border-transparent hover:border-indigo-100 transition-all">
                                    <PencilSquareIcon class="h-5 w-5" />
                                </Link>
                                <button @click="deleteProvider(provider.id)" class="p-2.5 bg-rose-50 dark:bg-rose-950/20 rounded-xl text-rose-300 hover:text-rose-600 border border-transparent hover:border-rose-100 transition-all">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <h3 class="font-black text-2xl text-slate-800 dark:text-white uppercase tracking-tighter leading-tight mb-6">{{ provider.name }}</h3>

                        <div class="grid grid-cols-1 gap-4 mb-8">
                            <div v-if="provider.address" class="flex items-start bg-slate-50 dark:bg-gray-800/50 p-3 rounded-2xl border border-slate-100 dark:border-gray-800">
                                <MapPinIcon class="h-4 w-4 mr-3 shrink-0 text-slate-400" />
                                <span class="text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-tighter leading-tight">{{ provider.address }}</span>
                            </div>
                            <div class="flex gap-2">
                                <div v-if="provider.contact_phone" class="flex-1 flex items-center bg-slate-50 dark:bg-gray-800/50 p-3 rounded-2xl border border-slate-100 dark:border-gray-800">
                                    <PhoneIcon class="h-4 w-4 mr-2 shrink-0 text-slate-400" />
                                    <span class="text-[10px] font-black text-slate-500 dark:text-gray-400 tracking-widest">{{ provider.contact_phone }}</span>
                                </div>
                                <div v-if="provider.contact_email" class="flex-1 flex items-center bg-slate-50 dark:bg-gray-800/50 p-3 rounded-2xl border border-slate-100 dark:border-gray-800">
                                    <EnvelopeIcon class="h-4 w-4 mr-2 shrink-0 text-slate-400" />
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-gray-400 lowercase truncate">{{ provider.contact_email }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <Link :href="route('daily-menus.index', { provider_id: provider.id })" 
                                    class="w-full py-4 bg-slate-900 dark:bg-gray-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.3em] hover:bg-indigo-600 transition-all flex items-center justify-center shadow-lg active:scale-95">
                                <ClipboardDocumentListIcon class="h-4 w-4 mr-3" /> Ver Menús
                            </Link>
                            <button @click="openScanModal(provider)"
                                    class="w-full py-4 bg-white dark:bg-gray-800 border-2 border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-2xl text-[10px] font-black uppercase tracking-[0.3em] hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all flex items-center justify-center active:scale-95">
                                <PhotoIcon class="h-4 w-4 mr-3" /> Escaneo IA
                            </button>
                        </div>

                        <div class="absolute bottom-6 right-8">
                            <div class="h-2 w-2 rounded-full shadow-lg" :class="isProfileComplete(provider) ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500 animate-pulse'"></div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-900 rounded-[4rem] border-2 border-dashed border-slate-100 dark:border-gray-800 text-center shadow-inner">
                    <BuildingStorefrontIcon class="h-16 w-16 text-slate-200 dark:text-gray-800 mx-auto mb-6" />
                    <p class="text-slate-400 font-black uppercase tracking-[0.3em] text-sm">No hay proveedores registrados aún</p>
                </div>
            </div>

            <!-- RIGHT COLUMN: SIDEBAR STATS & ACTIONS -->
            <div class="lg:col-span-4 space-y-8">
                
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-10 shadow-xl border border-slate-100 dark:border-gray-800">
                    <div class="flex items-center gap-4 mb-4">
                        <ShieldCheckIcon class="h-6 w-6 text-orange-500" />
                        <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter">Integridad</h3>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 leading-relaxed uppercase tracking-widest mb-10">Los perfiles completos aseguran pagos exactos.</p>

                    <div class="space-y-6">
                        <div class="bg-slate-50 dark:bg-gray-800 p-6 rounded-3xl border border-slate-100 dark:border-gray-700">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Progreso de Perfiles</p>
                            <div class="flex justify-between items-end mb-2">
                                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ providers.filter(p => isProfileComplete(p)).length }} / {{ providers.length }}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Completos</p>
                            </div>
                            <div class="h-1.5 w-full bg-slate-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500" :style="{ width: (providers.length > 0 ? (providers.filter(p => isProfileComplete(p)).length / providers.length * 100) : 0) + '%' }"></div>
                            </div>
                        </div>

                        <div class="bg-orange-50 dark:bg-orange-950/20 p-6 rounded-3xl border border-orange-100 dark:border-orange-900/30">
                            <div class="flex items-center gap-4">
                                <InformationCircleIcon class="h-6 w-6 text-orange-500" />
                                <p class="text-[10px] font-bold text-orange-700 dark:text-orange-400 leading-snug uppercase tracking-widest">IA de escaneo optimizada.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-600 rounded-[3rem] p-10 text-white shadow-2xl shadow-indigo-900/20">
                    <h4 class="text-lg font-black uppercase tracking-tighter mb-8 ml-2">Navegación</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <Link :href="route('dashboard')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                            <ArrowLeftIcon class="h-6 w-6" />
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]">Dashboard</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <ScanMenuModal 
            :show="showScanModal"
            :provider="selectedProviderForScan"
            :selectedDate="new Date().toLocaleDateString('en-CA')"
            @close="showScanModal = false"
        />
    </AuthenticatedLayout>
</template>
