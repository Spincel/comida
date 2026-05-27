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
</script>

<template>
    <Head title="Catálogo V2.0" />

    <AuthenticatedLayout bento-tag="Catálogos">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-9 space-y-8">
                <!-- FILTERS -->
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-gray-800 flex flex-col md:flex-row items-end gap-6">
                    <div class="flex-1 w-full space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Establecimiento</label>
                        <select v-model="filterProviderId" class="w-full bg-slate-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 focus:ring-indigo-500 shadow-inner">
                            <option value="">Selecciona Proveedor...</option>
                            <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="flex-1 w-full space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4">Fecha</label>
                        <input type="date" v-model="filterDate" class="w-full bg-slate-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-[11px] font-black text-slate-600 dark:text-gray-300 focus:ring-indigo-500 shadow-inner" />
                    </div>
                    <div class="flex gap-2">
                        <button @click="openScanMenuModal" class="p-4 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-2xl hover:bg-indigo-600 hover:text-white transition-all shadow-md group"><SparklesIcon class="h-6 w-6 group-hover:animate-pulse" /></button>
                        <Link :href="route('daily-menus.create', { provider_id: filterProviderId, date: filterDate })" class="p-4 bg-slate-900 dark:bg-gray-700 text-white rounded-2xl hover:bg-indigo-600 transition-all shadow-md"><PlusIcon class="h-6 w-6" stroke-width="3" /></Link>
                    </div>
                </div>

                <!-- MENU LIST -->
                <div v-if="menus.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div v-for="menu in menus" :key="menu.id" class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 border-2 transition-all group flex flex-col relative overflow-hidden" :class="menu.status === 'published' ? 'border-emerald-500/20 dark:border-emerald-500/10 shadow-lg' : 'border-slate-100 dark:border-gray-800 opacity-80'">
                        <div class="flex justify-between items-start mb-6">
                            <button @click="toggleMenuStatus(menu)" class="flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all" :class="menu.status === 'published' ? 'bg-emerald-50 border-emerald-100 text-emerald-600 dark:bg-emerald-950/20 dark:border-emerald-900' : 'bg-slate-50 border-slate-100 text-slate-400 dark:bg-gray-800'"><EyeIcon v-if="menu.status === 'published'" class="h-4 w-4" /><EyeSlashIcon v-else class="h-4 w-4" /><span class="text-[9px] font-black uppercase tracking-widest">{{ menu.status === 'published' ? 'Activo' : 'Borrador' }}</span></button>
                            <div class="flex gap-2"><Link :href="route('daily-menus.edit', menu.id)" class="p-2 text-slate-300 hover:text-indigo-600 transition-colors"><PencilSquareIcon class="h-5 w-5" /></Link><button @click="deleteDailyMenu(menu.id)" class="p-2 text-slate-300 hover:text-rose-600 transition-colors"><TrashIcon class="h-5 w-5" /></button></div>
                        </div>
                        <div class="flex-1"><h4 class="font-black text-xl text-slate-800 dark:text-white uppercase tracking-tighter leading-tight mb-3">{{ menu.name }}</h4><p class="text-xs text-slate-500 dark:text-gray-400 italic line-clamp-3 mb-6">{{ menu.description || 'Sin descripción.' }}</p></div>
                        <div class="mt-4 pt-4 border-t border-slate-50 dark:border-gray-800"><div class="flex justify-between items-center"><div v-if="menu.popularity_label" class="px-2 py-0.5 rounded-lg border text-[8px] font-black uppercase tracking-widest shadow-sm" :class="menu.popularity_color">{{ menu.popularity_label }}</div><p class="text-[8px] font-black text-indigo-500 uppercase tracking-widest">{{ menu.provider.name }}</p></div></div>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-900 rounded-[4rem] border-2 border-dashed border-slate-100 dark:border-gray-800 text-center shadow-inner"><MagnifyingGlassIcon class="h-16 w-16 text-slate-200 dark:text-gray-800 mx-auto mb-6" /><p class="text-slate-400 font-black uppercase tracking-[0.3em] text-sm">No se encontraron platillos</p></div>
            </div>

            <!-- SIDEBAR -->
            <div class="lg:col-span-3 space-y-8">
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-10 shadow-xl border border-slate-100 dark:border-gray-800">
                    <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter mb-6">Estado</h3>
                    <div class="space-y-6">
                        <div class="bg-slate-50 dark:bg-gray-800 p-6 rounded-3xl border border-slate-100 dark:border-gray-700">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Recepción de Pedidos</p>
                            <button @click="toggleProviderStatus" class="w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 transition-all flex items-center justify-center gap-3" :class="currentProviderStatus === 'open' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600' : 'border-slate-200 text-slate-400'"><div class="h-2.5 w-2.5 rounded-full" :class="currentProviderStatus === 'open' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></div>{{ currentProviderStatus === 'open' ? 'Abierto' : 'Cerrado' }}</button>
                        </div>
                        <button v-if="hasDrafts" @click="publishAllDrafts" class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl transition-all flex items-center justify-center"><CloudArrowUpIcon class="h-5 w-5 mr-3" stroke-width="3" /> Publicar Todo</button>
                    </div>
                </div>
            </div>
        </div>

        <ScanMenuModal :show="showScanMenuModal" :provider="selectedProviderForScan" :selectedDate="filterDate" @close="showScanMenuModal = false" @menuScanned="handleMenuScanned" />

        <Modal :show="showPublishAllModal" @close="showPublishAllModal = false" max-width="md">
            <div class="p-10 text-center dark:bg-gray-900 transition-colors">
                <div class="mx-auto h-20 w-20 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center mb-8 text-emerald-600 animate-bounce shadow-inner"><CloudArrowUpIcon class="h-10 w-10" /></div>
                <h3 class="text-3xl font-black text-slate-800 dark:text-white uppercase tracking-tighter mb-4">Activar Catálogo</h3>
                <p class="text-sm text-slate-500 dark:text-gray-400 mb-10 leading-relaxed">¿Estás seguro de que deseas publicar todos los platillos actualmente en borrador?</p>
                <div class="flex flex-col gap-4">
                    <button @click="confirmPublishAll" class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-2xl active:scale-95">Sí, Activar Todo</button>
                    <button @click="showPublishAllModal = false" class="w-full py-4 text-[10px] font-black uppercase text-slate-400 hover:text-slate-600 tracking-widest transition-colors">No, Cancelar</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
