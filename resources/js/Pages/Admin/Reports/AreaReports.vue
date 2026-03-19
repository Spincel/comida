<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import { 
    ChevronLeftIcon, 
    ClipboardDocumentIcon,
    ArrowDownTrayIcon,
    CalendarDaysIcon,
    InformationCircleIcon,
    FunnelIcon,
    XMarkIcon,
    MagnifyingGlassIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sessions: Array,
    area: Object,
    providers: Array,
    filters: Object,
});

// --- Filter State ---
const form = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    meal_type: props.filters.meal_type || '',
    provider_id: props.filters.provider_id || '',
});

const mealOptions = ['Desayuno', 'Comida', 'Cena', 'Extra'];

const applyFilters = () => {
    router.get(route('area.reports'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    form.value = { start_date: '', end_date: '', meal_type: '', provider_id: '' };
    applyFilters();
};

// --- Export Logic ---
const showExportModal = ref(false);
const selectedSession = ref(null);

const openExportModal = (session) => {
    selectedSession.value = session;
    showExportModal.value = true;
};

const handleExport = (format) => {
    if (!selectedSession.value) return;
    
    const url = route('admin.orders.summary.pdf', { 
        provider: selectedSession.value.provider_id, 
        date: selectedSession.value.date,
        meal_type: selectedSession.value.meal_type,
        area_id: props.area.id,
        format: format,
        view_mode: 'detailed',
        sort: 'name'
    });
    window.open(url, '_blank');
};

// --- Unified Color Helpers ---
const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
    'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800',
    'Cena': 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800',
    'Extra': 'bg-teal-100 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-400 dark:border-teal-800',
};

const providerColors = [
    { text: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-900/30' },
    { text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-900/30' },
    { text: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-50 dark:bg-rose-900/30' },
    { text: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-900/30' },
];

const getProviderColor = (id) => providerColors[id % providerColors.length];
</script>

<template>
    <Head title="Reportes por Área" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center">
                    <Link :href="route('dashboard')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                            Reportes de Área
                        </h2>
                        <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest">{{ area.name }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- FILTROS AVANZADOS -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2 mb-6">
                        <FunnelIcon class="h-5 w-5 text-indigo-500" />
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300">Filtros de Búsqueda</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Rango de Fechas -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400">Desde</label>
                            <input type="date" v-model="form.start_date" @change="applyFilters"
                                   class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400">Hasta</label>
                            <input type="date" v-model="form.end_date" @change="applyFilters"
                                   class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500" />
                        </div>

                        <!-- Tipo de Sesión -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400">Tipo de Sesión</label>
                            <select v-model="form.meal_type" @change="applyFilters"
                                    class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500">
                                <option value="">Todos los tipos</option>
                                <option v-for="opt in mealOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>

                        <!-- Proveedor -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400">Proveedor</label>
                            <select v-model="form.provider_id" @change="applyFilters"
                                    class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500">
                                <option value="">Todos los proveedores</option>
                                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="Object.values(form).some(v => v !== '')" class="mt-6 flex justify-end">
                        <button @click="clearFilters" class="text-[10px] font-black uppercase text-red-500 hover:text-red-600 flex items-center gap-1">
                            <XMarkIcon class="h-4 w-4" /> Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- LISTADO DE REPORTES -->
                <div v-if="sessions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="session in sessions" :key="session.id" 
                         class="bg-white dark:bg-gray-800 rounded-[2rem] p-8 border border-gray-100 dark:border-gray-700 shadow-xl transition-all hover:scale-[1.02] flex flex-col group">
                        <div class="flex justify-between items-start mb-6">
                            <div class="h-14 w-14 rounded-3xl flex items-center justify-center shadow-lg transition-transform group-hover:rotate-12 shadow-indigo-100 dark:shadow-none"
                                 :class="getProviderColor(session.provider_id).bg + ' ' + getProviderColor(session.provider_id).text">
                                <ClipboardDocumentIcon class="h-8 w-8" />
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ session.date }}</p>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-widest mt-1 inline-block"
                                      :class="mealTypeTagColors[session.meal_type] || 'bg-indigo-50 text-indigo-500 border-indigo-100'">
                                    {{ session.meal_type }}
                                </span>
                            </div>
                        </div>
                        
                        <h5 class="font-black text-xl text-gray-900 dark:text-white mb-6 leading-tight uppercase tracking-tight flex-1"
                            :class="getProviderColor(session.provider_id).text">
                            {{ session.provider.name }}
                        </h5>

                        <div class="flex items-center text-xs text-gray-500 mb-8 space-x-4">
                            <div class="flex items-center">
                                <CalendarDaysIcon class="h-4 w-4 mr-1 text-gray-400" />
                                {{ new Date(session.date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }) }}
                            </div>
                            <div class="flex items-center">
                                <CheckBadgeIcon class="h-4 w-4 mr-1 text-green-500" />
                                <span class="font-bold uppercase tracking-tighter">Finalizado</span>
                            </div>
                        </div>
                        
                        <button @click="openExportModal(session)" 
                                class="w-full py-4 bg-gray-900 dark:bg-gray-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-600 transition-all flex items-center justify-center shadow-lg">
                            <ArrowDownTrayIcon class="h-4 w-4 mr-2" /> Exportar Reporte
                        </button>
                    </div>
                </div>
                
                <div v-else class="p-20 bg-white dark:bg-gray-800 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700 text-center transition-colors">
                    <MagnifyingGlassIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm mb-6">No se encontraron reportes con los filtros seleccionados</p>
                    
                    <Link :href="route('dashboard')" 
                        class="inline-flex items-center px-8 py-4 bg-indigo-600 dark:bg-indigo-500 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-500/20 active:scale-95">
                        <ArrowLeftIcon class="h-4 w-4 mr-2" /> Regresar al Dashboard
                    </Link>
                </div>

            </div>
        </div>

        <ExportChoiceModal 
            :show="showExportModal"
            @close="showExportModal = false"
            @select="handleExport"
        />
    </AuthenticatedLayout>
</template>
