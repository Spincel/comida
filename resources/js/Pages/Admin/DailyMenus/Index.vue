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
    EyeSlashIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    menus: Array,
    providers: Array,
    selectedDate: String,
    selectedProviderId: Number,
    providerDailyStatus: Object,
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
    if (confirm('¿Estás seguro de eliminar este platillo? Esta acción no se puede deshacer.')) {
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

const publishAllDrafts = () => {
    if (!filterProviderId.value) return;
    showPublishAllModal.value = true;
};

const confirmPublishAll = () => {
    router.post(route('daily-menus.publishAll'), {
        provider_id: filterProviderId.value,
        date: filterDate.value
    }, { 
        preserveScroll: true,
        onSuccess: () => {
            showPublishAllModal.value = false;
        }
    });
};

const currentProviderStatus = computed(() => props.providerDailyStatus?.status || 'closed');

const toggleProviderStatus = () => {
    if (!filterProviderId.value) return;
    const newStatus = currentProviderStatus.value === 'open' ? 'closed' : 'open';
    router.patch(route('daily-menus.updateProviderDailyStatus'), {
        provider_id: filterProviderId.value,
        date: filterDate.value,
        status: newStatus
    }, { preserveScroll: true });
};

// --- Scan Menu Modal Logic ---
const showScanMenuModal = ref(false);
const selectedProviderForScan = ref(null);

const openScanMenuModal = () => {
    if (!filterProviderId.value) return alert('Selecciona un proveedor primero.');
    selectedProviderForScan.value = props.providers.find(p => p.id === filterProviderId.value);
    showScanMenuModal.value = true;
};

const handleMenuScanned = () => {
    router.reload({ only: ['menus'] });
};

const hasDrafts = computed(() => props.menus.some(m => m.status === 'draft'));

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Catálogo de Menús" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center">
                    <Link :href="route('providers.index')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                            Catálogo de Menús
                        </h2>
                        <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Configuración de Platillos Diarios</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button v-if="hasDrafts" @click="publishAllDrafts"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-lg shadow-green-100 dark:shadow-none transition-all">
                        <CloudArrowUpIcon class="h-4 w-4 mr-2" stroke-width="3" /> Activar Todos
                    </button>
                    
                    <Link :href="route('daily-menus.create', { provider_id: filterProviderId, date: filterDate })" 
                          class="bg-gray-900 dark:bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-lg transition-all">
                        <PlusIcon class="h-4 w-4 mr-2" stroke-width="3" /> Crear Manualmente
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- BARRA DE FILTROS Y ESTADO -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                        <div class="md:col-span-3 space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest flex items-center">
                                <CalendarDaysIcon class="h-3 w-3 mr-1" /> Fecha de Disponibilidad
                            </label>
                            <input type="date" v-model="filterDate" 
                                   class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500" />
                        </div>

                        <div class="md:col-span-4 space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Proveedor Seleccionado</label>
                            <select v-model="filterProviderId" 
                                    class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500">
                                <option value="">Selecciona un proveedor...</option>
                                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div class="md:col-span-5 flex gap-3">
                            <button @click="openScanMenuModal" 
                                    class="flex-1 py-3 px-4 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-2 border-indigo-100 dark:border-indigo-800 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center">
                                <SparklesIcon class="h-4 w-4 mr-2" /> Escanear Menú con IA
                            </button>

                            <button v-if="filterProviderId" 
                                    @click="toggleProviderStatus"
                                    class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 transition-all flex items-center gap-2"
                                    :class="currentProviderStatus === 'open' 
                                        ? 'border-green-500 bg-green-50 text-green-600 dark:bg-green-900/20' 
                                        : 'border-gray-200 text-gray-400 hover:border-green-200'" >
                                <div class="h-2 w-2 rounded-full" :class="currentProviderStatus === 'open' ? 'bg-green-500 animate-pulse' : 'bg-gray-300'"></div>
                                {{ currentProviderStatus === 'open' ? 'Pedidos Abiertos' : 'Abrir Pedidos' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- LISTADO DE PLATILLOS -->
                <div v-if="menus.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="menu in menus" :key="menu.id" 
                         class="bg-white dark:bg-gray-800 rounded-[2rem] p-6 border-2 transition-all hover:shadow-xl group flex flex-col"
                         :class="menu.status === 'published' ? 'border-green-500/50 dark:border-green-500/30 shadow-green-50' : 'border-gray-100 dark:border-gray-700 opacity-80'">
                        
                        <div class="flex justify-between items-start mb-4">
                            <!-- BOTÓN DE ESTADO INDIVIDUAL -->
                            <button @click="toggleMenuStatus(menu)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border-2 transition-all group/btn"
                                    :class="menu.status === 'published' 
                                        ? 'bg-green-50 border-green-100 text-green-600 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400' 
                                        : 'bg-gray-50 border-gray-100 text-gray-400 dark:bg-gray-900 dark:border-gray-700'">
                                <EyeIcon v-if="menu.status === 'published'" class="h-3.5 w-3.5" />
                                <EyeSlashIcon v-else class="h-3.5 w-3.5" />
                                <span class="text-[9px] font-black uppercase tracking-widest">
                                    {{ menu.status === 'published' ? 'Activo' : 'Deshabilitado' }}
                                </span>
                            </button>
                            
                            <div class="flex gap-1">
                                <Link :href="route('daily-menus.edit', menu.id)" 
                                      class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-400 hover:text-indigo-600 rounded-xl transition-all shadow-sm">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </Link>
                                <button @click="deleteDailyMenu(menu.id)" 
                                        class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-400 hover:text-red-600 rounded-xl transition-all shadow-sm">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex-1">
                            <h4 class="font-black text-lg text-gray-800 dark:text-white uppercase tracking-tight leading-tight mb-2">{{ menu.name }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 italic line-clamp-2 mb-4">{{ menu.description || 'Sin descripción disponible.' }}</p>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t dark:border-gray-700">
                            <div class="flex justify-between items-end">
                                <div class="space-y-1">
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-[0.2em]">Registro creado el:</p>
                                    <div class="flex items-center text-[10px] font-bold text-gray-500">
                                        <CalendarDaysIcon class="h-3 w-3 mr-1 text-indigo-400" />
                                        {{ formatDate(menu.created_at) }}
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <!-- ETIQUETA DE POPULARIDAD -->
                                    <div v-if="menu.popularity_label" 
                                         class="inline-block px-2 py-0.5 rounded-lg border text-[7px] font-black uppercase tracking-widest mb-1 shadow-sm"
                                         :class="menu.popularity_color">
                                        {{ menu.popularity_label }}
                                    </div>
                                    <p class="text-[9px] font-black text-indigo-500 uppercase tracking-widest block">{{ menu.provider.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-800 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700 text-center transition-colors">
                    <MagnifyingGlassIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm">No hay platillos en este catálogo</p>
                    <p class="text-xs text-gray-300 dark:text-gray-500 mt-2">Prueba escaneando un menú o creando uno manualmente</p>
                </div>

            </div>
        </div>
        
        <ScanMenuModal
            :show="showScanMenuModal"
            :provider="selectedProviderForScan"
            :selectedDate="filterDate"
            @close="showScanMenuModal = false"
            @menuScanned="handleMenuScanned"
        />

        <!-- MODAL DE CONFIRMACIÓN PUBLICAR TODO -->
        <Modal :show="showPublishAllModal" @close="showPublishAllModal = false" max-width="md">
            <div class="p-8 text-center">
                <div class="mx-auto h-20 w-20 bg-green-50 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6 text-green-600 animate-bounce">
                    <CloudArrowUpIcon class="h-10 w-10" />
                </div>
                <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter mb-2">Activar Catálogo</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                    ¿Estás seguro de que deseas publicar todos los platillos actualmente deshabilitados? Se volverán visibles para los comensales de inmediato.
                </p>
                <div class="flex flex-col gap-3">
                    <button @click="confirmPublishAll" 
                            class="w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-green-100 dark:shadow-none">
                        Sí, Activar Todo
                    </button>
                    <button @click="showPublishAllModal = false" 
                            class="w-full py-3 bg-gray-50 dark:bg-gray-900 text-gray-400 dark:text-gray-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:text-gray-700 transition-all">
                        No, Cancelar
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.2);
    border-radius: 10px;
}
</style>
