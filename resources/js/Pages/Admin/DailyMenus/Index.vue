<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ScanMenuModal from '@/Pages/Admin/Partials/ScanMenuModal.vue';
import Modal from '@/Components/Modal.vue';
import { 
    ChevronLeftIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    SparklesIcon, 
    CheckBadgeIcon,
    CalendarDaysIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    CloudArrowUpIcon,
    NoSymbolIcon,
    EyeIcon,
    EyeSlashIcon,
    ClockIcon,
    BuildingStorefrontIcon,
    ListBulletIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    menus: Array,
    providers: Array,
    selectedDate: String,
    selectedProviderId: Number,
    providerDailyStatus: Object,
    auth: Object
});

const filterDate = ref(props.selectedDate);
const filterProviderId = ref(props.selectedProviderId || '');

watch([filterDate, filterProviderId], ([newDate, newProviderId]) => {
    router.get(route('daily-menus.index'), {
        date: newDate,
        provider_id: newProviderId,
    }, { preserveState: true, replace: true });
});

const deleteDailyMenu = (id) => {
    if (confirm('¿Estás seguro de eliminar este platillo?')) {
        router.delete(route('daily-menus.destroy', id), { preserveScroll: true });
    }
};

const toggleMenuStatus = (menu) => {
    const newStatus = menu.status === 'published' ? 'draft' : 'published';
    router.patch(route('daily-menus.updateStatus', menu.id), {
        status: newStatus
    }, { preserveScroll: true });
};

const showPublishAllModal = ref(false);
const publishAllDrafts = () => { if (!filterProviderId.value) return; showPublishAllModal.value = true; };

const confirmPublishAll = () => {
    router.post(route('daily-menus.publishAll'), { provider_id: filterProviderId.value, date: filterDate.value }, { 
        preserveScroll: true, onSuccess: () => { showPublishAllModal.value = false; }
    });
};

const currentProviderStatus = computed(() => props.providerDailyStatus?.status || 'closed');
const toggleProviderStatus = () => {
    if (!filterProviderId.value) return;
    const newStatus = currentProviderStatus.value === 'open' ? 'closed' : 'open';
    router.patch(route('daily-menus.updateProviderDailyStatus'), { provider_id: filterProviderId.value, date: filterDate.value, status: newStatus }, { preserveScroll: true });
};

// --- Scan Menu Modal Logic ---
const showScanMenuModal = ref(false);
const selectedProviderForScan = ref(null);
const openScanMenuModal = () => {
    if (!filterProviderId.value) return alert('Selecciona un proveedor primero.');
    selectedProviderForScan.value = props.providers.find(p => p.id === filterProviderId.value);
    showScanMenuModal.value = true;
};

const handleMenuScanned = () => { router.reload({ only: ['menus'] }); };
const hasDrafts = computed(() => props.menus.some(m => m.status === 'draft'));

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getDishEmoji = (name) => {
    if (!name) return '🍽️';
    const n = name.toLowerCase();
    if (n.includes('huevo') || n.includes('chilaquil') || n.includes('omelet') || n.includes('hot cake') || n.includes('waffle')) return '🍳';
    if (n.includes('cafe') || n.includes('café') || n.includes('jugo') || n.includes('leche') || n.includes('avena')) return '☕';
    if (n.includes('carne') || n.includes('res') || n.includes('arrachera') || n.includes('bistec') || n.includes('milanesa') || n.includes('costilla')) return '🥩';
    if (n.includes('pollo') || n.includes('pechuga') || n.includes('alita') || n.includes('fajita')) return '🍗';
    if (n.includes('pescado') || n.includes('camaron') || n.includes('camarón') || n.includes('atun') || n.includes('marisco')) return '🐟';
    if (n.includes('taco') || n.includes('quesadilla') || n.includes('burrito') || n.includes('flauta') || n.includes('gordita')) return '🌮';
    if (n.includes('pasta') || n.includes('espagueti') || n.includes('lasaña')) return '🍝';
    if (n.includes('ensalada') || n.includes('vegetal') || n.includes('verdura') || n.includes('fruta')) return '🥗';
    if (n.includes('sopa') || n.includes('caldo') || n.includes('crema') || n.includes('pozole') || n.includes('menudo')) return '🍲';
    if (n.includes('hamburguesa') || n.includes('sandwich') || n.includes('torta')) return '🥪';
    if (n.includes('postre') || n.includes('pastel') || n.includes('gelatina') || n.includes('flan')) return '🍰';
    return '🍽️';
};
</script>

<template>
    <Head title="Catálogo de Platillos SICOA" />

    <AuthenticatedLayout bento-tag="Catálogos">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-9 space-y-8">
                <!-- FILTERS -->
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex flex-col md:flex-row items-end gap-6">
                    <div class="flex-1 w-full space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Establecimiento / Proveedor</label>
                        <select v-model="filterProviderId" class="w-full bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl py-4 px-6 text-xs font-bold uppercase text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner">
                            <option value="">Selecciona Proveedor...</option>
                            <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="flex-1 w-full space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Fecha de Menú</label>
                        <input type="date" v-model="filterDate" class="w-full bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl py-4 px-6 text-xs font-bold text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner" />
                    </div>
                    <div class="flex gap-3 shrink-0">
                        <button v-if="$page.props.system?.hasAi"
                                @click="openScanMenuModal" 
                                type="button"
                                class="bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white p-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect"
                                title="Importar con IA">
                            <SparklesIcon class="h-6 w-6 text-oro-300 animate-pulse" />
                            <span class="hidden sm:inline">IA Menú</span>
                        </button>
                        <Link :href="route('daily-menus.create', { provider_id: filterProviderId, date: filterDate })" 
                              class="bg-gradient-to-r from-oro-600 to-oro-500 hover:from-oro-700 hover:to-oro-600 text-white p-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-oro-sm border border-oro-300/40 hover:scale-105 active:scale-95 transition-all cursor-pointer"
                              title="Nuevo Platillo">
                            <PlusIcon class="h-6 w-6" stroke-width="3" />
                            <span class="hidden sm:inline">Agregar</span>
                        </Link>
                    </div>
                </div>

                <!-- MENU LIST -->
                <div v-if="menus.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div v-for="menu in menus" :key="menu.id" 
                         class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 border transition-all group flex flex-col relative overflow-hidden shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none hover:scale-[1.02]" 
                         :class="menu.status === 'published' ? 'border-emerald-500/30 dark:border-emerald-500/20' : 'border-slate-200/80 dark:border-gray-800 opacity-85'">
                        
                        <div class="flex justify-between items-start mb-4">
                            <button @click="toggleMenuStatus(menu)" 
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all cursor-pointer shadow-xs" 
                                    :class="menu.status === 'published' ? 'bg-emerald-50 border-emerald-200 text-nayarit-800 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-300' : 'bg-slate-50 border-slate-200 text-slate-400 dark:bg-gray-800 dark:border-gray-700'">
                                <EyeIcon v-if="menu.status === 'published'" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                <EyeSlashIcon v-else class="h-4 w-4" />
                                <span class="text-[9px] font-black uppercase tracking-widest">{{ menu.status === 'published' ? 'Activo' : 'Borrador' }}</span>
                            </button>
                            <div class="flex gap-1.5">
                                <Link :href="route('daily-menus.edit', menu.id)" class="p-2 bg-slate-50 dark:bg-gray-800 text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl transition-all border border-transparent hover:border-tinto-200 cursor-pointer">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </Link>
                                <button @click="deleteDailyMenu(menu.id)" class="p-2 bg-rose-50 dark:bg-rose-950/20 text-rose-300 hover:text-rose-600 rounded-xl transition-all border border-transparent hover:border-rose-100 cursor-pointer">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- PLATILLO HERO CON EMOJI DINÁMICO -->
                        <div class="flex items-start gap-3.5 mb-3 flex-1">
                            <div class="h-12 w-12 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 group-hover:rotate-6 transition-transform shadow-xs">
                                {{ getDishEmoji(menu.name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tight leading-snug mb-1">
                                    {{ menu.name }}
                                </h4>
                                <p class="text-xs text-slate-500 dark:text-gray-400 italic line-clamp-3 font-normal leading-relaxed">
                                    {{ menu.description || 'Sin descripción detallada.' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-gray-800">
                            <div class="flex justify-between items-center">
                                <div v-if="menu.popularity_label" class="px-2.5 py-1 rounded-lg border text-[8px] font-black uppercase tracking-widest shadow-2xs" :class="menu.popularity_color">
                                    {{ menu.popularity_label }}
                                </div>
                                <p class="text-[9px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest flex items-center gap-1">
                                    <span>👨‍🍳</span> {{ menu.provider?.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-gray-800 text-center shadow-inner">
                    <MagnifyingGlassIcon class="h-16 w-16 text-slate-300 dark:text-gray-700 mx-auto mb-4" />
                    <p class="text-slate-400 font-black uppercase tracking-[0.2em] text-xs">No se encontraron platillos para esta fecha y proveedor</p>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="lg:col-span-3 space-y-8">
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-8 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight mb-6 flex items-center gap-2">
                        <span>⚡</span> Estado Operativo
                    </h3>
                    <div class="space-y-6">
                        <div class="bg-slate-50/70 dark:bg-gray-800/50 p-6 rounded-3xl border border-slate-200/80 dark:border-gray-700">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Recepción de Pedidos</p>
                            <button @click="toggleProviderStatus" 
                                    class="w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all flex items-center justify-center gap-3 cursor-pointer shadow-sm" 
                                    :class="currentProviderStatus === 'open' ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 text-nayarit-800 dark:text-emerald-300' : 'border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-slate-400'">
                                <div class="h-2.5 w-2.5 rounded-full" :class="currentProviderStatus === 'open' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></div>
                                {{ currentProviderStatus === 'open' ? 'Abierto para Pedidos' : 'Cerrado' }}
                            </button>
                        </div>
                        <button v-if="hasDrafts" 
                                @click="publishAllDrafts" 
                                class="w-full py-5 bg-gradient-to-r from-nayarit-800 to-nayarit-700 hover:from-nayarit-900 hover:to-nayarit-800 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-md transition-all flex items-center justify-center cursor-pointer">
                            <CloudArrowUpIcon class="h-5 w-5 mr-2.5" stroke-width="2.5" /> Publicar Todo el Menú
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ScanMenuModal :show="showScanMenuModal" :provider="selectedProviderForScan" :selectedDate="filterDate" @close="showScanMenuModal = false" @menuScanned="handleMenuScanned" />

        <Modal :show="showPublishAllModal" @close="showPublishAllModal = false" max-width="md">
            <div class="p-10 text-center dark:bg-gray-900 transition-colors">
                <div class="mx-auto h-20 w-20 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center mb-8 text-emerald-600 animate-bounce shadow-inner">
                    <CloudArrowUpIcon class="h-10 w-10" />
                </div>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight mb-4">Activar Catálogo</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400 mb-8 leading-relaxed">¿Estás seguro de que deseas publicar todos los platillos actualmente en borrador para este día?</p>
                <div class="flex flex-col gap-3">
                    <button @click="confirmPublishAll" class="w-full py-4 bg-gradient-to-r from-nayarit-800 to-nayarit-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-md active:scale-95 cursor-pointer">Sí, Activar Todo</button>
                    <button @click="showPublishAllModal = false" class="w-full py-3.5 text-[10px] font-black uppercase text-slate-400 hover:text-slate-600 tracking-widest transition-colors cursor-pointer">No, Cancelar</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
