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
    { bg: 'bg-tinto-50 dark:bg-tinto-950/60', text: 'text-tinto-800 dark:text-oro-300', border: 'border-tinto-200 dark:border-tinto-800' },
    { bg: 'bg-oro-50 dark:bg-oro-950/60', text: 'text-oro-800 dark:text-oro-300', border: 'border-oro-200 dark:border-oro-800' },
    { bg: 'bg-emerald-50 dark:bg-emerald-950/60', text: 'text-nayarit-800 dark:text-emerald-300', border: 'border-emerald-200 dark:border-emerald-800' },
    { bg: 'bg-amber-50 dark:bg-amber-950/60', text: 'text-amber-800 dark:text-amber-300', border: 'border-amber-200 dark:border-amber-800' },
];

const getProviderColor = (index) => providerColors[index % providerColors.length];

const isProfileComplete = (provider) => {
    return !!(provider.name && provider.address && provider.contact_person && provider.contact_phone && provider.contact_email);
};
</script>

<template>
    <Head title="Proveedores SICOA" />

    <AuthenticatedLayout bento-tag="Gestión">
        <!-- MAIN BENTO CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- LEFT COLUMN: PROVIDER LIST -->
            <div class="lg:col-span-8 space-y-8">
                
                <div class="flex justify-between items-center bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 bg-tinto-50 dark:bg-tinto-950/60 border border-tinto-200 dark:border-tinto-800 rounded-2xl flex items-center justify-center">
                            <BuildingStorefrontIcon class="h-6 w-6 text-tinto-700 dark:text-oro-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Catálogo de Proveedores</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ providers.length }} entidades gastronómicas registradas</p>
                        </div>
                    </div>
                    <Link :href="route('providers.create')" 
                            class="bg-gradient-to-r from-oro-600 to-oro-500 hover:from-oro-700 hover:to-oro-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] flex items-center shadow-oro-sm border border-oro-300/40 transition-all hover:scale-105 active:scale-95 cursor-pointer">
                        <PlusIcon class="h-4 w-4 mr-2" stroke-width="4" /> Nuevo Proveedor
                    </Link>
                </div>

                <div v-if="providers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div v-for="(provider, index) in providers" :key="provider.id" 
                            class="bg-white dark:bg-gray-900 rounded-[3rem] p-8 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none transition-all hover:scale-[1.02] group relative overflow-hidden">
                        
                        <div class="flex justify-between items-start mb-6">
                            <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center shadow-sm border transition-transform group-hover:scale-110"
                                    :class="getProviderColor(index).bg + ' ' + getProviderColor(index).text + ' ' + getProviderColor(index).border">
                                <span class="text-3xl">👨‍🍳</span>
                            </div>
                            <div class="flex gap-2">
                                <Link :href="route('providers.edit', provider.id)" class="p-2.5 bg-slate-50 dark:bg-gray-800 rounded-xl text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 border border-transparent hover:border-tinto-200 transition-all cursor-pointer">
                                    <PencilSquareIcon class="h-5 w-5" />
                                </Link>
                                <button @click="deleteProvider(provider.id)" class="p-2.5 bg-rose-50 dark:bg-rose-950/20 rounded-xl text-rose-300 hover:text-rose-600 border border-transparent hover:border-rose-100 transition-all cursor-pointer">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <h3 class="font-black text-2xl text-slate-800 dark:text-white uppercase tracking-tight leading-tight mb-6">{{ provider.name }}</h3>

                        <div class="grid grid-cols-1 gap-4 mb-8">
                            <div v-if="provider.address" class="flex items-start bg-slate-50/70 dark:bg-gray-800/50 p-3 rounded-2xl border border-slate-200/60 dark:border-gray-800">
                                <MapPinIcon class="h-4 w-4 mr-3 shrink-0 text-tinto-700 dark:text-oro-400" />
                                <span class="text-[10px] font-bold text-slate-600 dark:text-gray-300 uppercase tracking-tighter leading-tight">{{ provider.address }}</span>
                            </div>
                            <div class="flex gap-2">
                                <div v-if="provider.contact_phone" class="flex-1 flex items-center bg-slate-50/70 dark:bg-gray-800/50 p-3 rounded-2xl border border-slate-200/60 dark:border-gray-800">
                                    <PhoneIcon class="h-4 w-4 mr-2 shrink-0 text-tinto-700 dark:text-oro-400" />
                                    <span class="text-[10px] font-black text-slate-600 dark:text-gray-300 tracking-widest">{{ provider.contact_phone }}</span>
                                </div>
                                <div v-if="provider.contact_email" class="flex-1 flex items-center bg-slate-50/70 dark:bg-gray-800/50 p-3 rounded-2xl border border-slate-200/60 dark:border-gray-800">
                                    <EnvelopeIcon class="h-4 w-4 mr-2 shrink-0 text-tinto-700 dark:text-oro-400" />
                                    <span class="text-[10px] font-bold text-slate-600 dark:text-gray-300 lowercase truncate">{{ provider.contact_email }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <Link :href="route('daily-menus.index', { provider_id: provider.id })" 
                                    class="w-full py-4 bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-tinto-sm border border-oro-400/40 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center cursor-pointer shine-effect">
                                <ClipboardDocumentListIcon class="h-4 w-4 mr-2.5 text-oro-300" /> Ver Menús y Platillos
                            </Link>
                            <button @click="openScanModal(provider)"
                                    class="w-full py-4 bg-white dark:bg-gray-800 border border-oro-400/50 text-tinto-900 dark:text-oro-300 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-oro-50/60 dark:hover:bg-oro-950/30 transition-all flex items-center justify-center cursor-pointer">
                                <PhotoIcon class="h-4 w-4 mr-2.5 text-oro-500" /> Escaneo IA de Menú
                            </button>
                        </div>

                        <div class="absolute bottom-6 right-8">
                            <div class="h-2.5 w-2.5 rounded-full shadow-md" :class="isProfileComplete(provider) ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500 animate-pulse'" :title="isProfileComplete(provider) ? 'Perfil Completo' : 'Perfil Incompleto'"></div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-900 rounded-[4rem] border border-dashed border-slate-200 dark:border-gray-800 text-center shadow-inner">
                    <BuildingStorefrontIcon class="h-16 w-16 text-slate-300 dark:text-gray-700 mx-auto mb-6" />
                    <p class="text-slate-400 font-black uppercase tracking-[0.3em] text-sm">No hay proveedores registrados aún</p>
                </div>
            </div>

            <!-- RIGHT COLUMN: SIDEBAR STATS & ACTIONS -->
            <div class="lg:col-span-4 space-y-8">
                
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-8 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <div class="flex items-center gap-3.5 mb-4">
                        <div class="h-10 w-10 rounded-xl bg-oro-50 dark:bg-oro-950/60 text-oro-700 dark:text-oro-300 border border-oro-200 dark:border-oro-800 flex items-center justify-center">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Integridad de Datos</h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Validación de expedientes</p>
                        </div>
                    </div>

                    <div class="space-y-6 mt-6">
                        <div class="bg-slate-50/70 dark:bg-gray-800/50 p-6 rounded-3xl border border-slate-200/80 dark:border-gray-700">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Progreso de Perfiles</p>
                            <div class="flex justify-between items-end mb-2">
                                <p class="text-2xl font-black text-tinto-800 dark:text-oro-300">{{ providers.filter(p => isProfileComplete(p)).length }} / {{ providers.length }}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Completos</p>
                            </div>
                            <div class="h-2 w-full bg-slate-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-tinto-700 to-oro-500 rounded-full transition-all duration-700" :style="{ width: (providers.length > 0 ? (providers.filter(p => isProfileComplete(p)).length / providers.length * 100) : 0) + '%' }"></div>
                            </div>
                        </div>

                        <div class="bg-tinto-50/60 dark:bg-tinto-950/30 p-5 rounded-2xl border border-tinto-200/60 dark:border-tinto-900/40">
                            <div class="flex items-center gap-3">
                                <InformationCircleIcon class="h-5 w-5 text-tinto-700 dark:text-oro-400 shrink-0" />
                                <p class="text-[9px] font-bold text-tinto-900 dark:text-oro-300 leading-snug uppercase tracking-widest">IA de escaneo lista para procesar menús en imagen o PDF.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-tinto-950 via-tinto-900 to-slate-950 rounded-[3rem] p-8 text-white shadow-tinto border border-oro-500/30">
                    <h4 class="text-base font-black uppercase tracking-tight mb-4 flex items-center gap-2">
                        <span>🏛️</span> Accesos Rápidos
                    </h4>
                    <div class="grid grid-cols-1 gap-3">
                        <Link :href="route('dashboard')" class="flex items-center gap-3 bg-white/10 hover:bg-white/20 p-4 rounded-2xl border border-white/20 transition-all cursor-pointer">
                            <ArrowLeftIcon class="h-5 w-5 text-oro-300" />
                            <span class="text-[10px] font-black uppercase tracking-widest text-white">Volver al Panel Central</span>
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
