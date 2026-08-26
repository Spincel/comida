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
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800',
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300 dark:border-tinto-800',
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-800',
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-800',
};

const providerColors = [
    { text: 'text-tinto-800 dark:text-oro-300', bg: 'bg-tinto-50 dark:bg-tinto-950/60' },
    { text: 'text-oro-800 dark:text-oro-300', bg: 'bg-oro-50 dark:bg-oro-950/60' },
    { text: 'text-nayarit-800 dark:text-emerald-300', bg: 'bg-emerald-50 dark:bg-emerald-950/60' },
    { text: 'text-amber-800 dark:text-amber-300', bg: 'bg-amber-50 dark:bg-amber-950/60' },
];

const getProviderColor = (id) => providerColors[id % providerColors.length];
</script>

<template>
    <Head title="Reportes por Área SICOA" />

    <AuthenticatedLayout bento-tag="Estadísticas">
        <div class="space-y-8">
            <!-- HEADER -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        📄
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Reportes de Consumo por Área</h2>
                        <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">{{ area.name }}</p>
                    </div>
                </div>
                <Link :href="route('dashboard')" class="px-5 py-2.5 bg-slate-50 dark:bg-gray-800 text-slate-600 dark:text-gray-300 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-200 dark:border-gray-700 transition-all cursor-pointer">
                    ← Volver
                </Link>
            </div>

            <!-- FILTROS AVANZADOS -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 space-y-6">
                <div class="flex items-center gap-2">
                    <FunnelIcon class="h-5 w-5 text-tinto-700 dark:text-oro-400" />
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-700 dark:text-gray-300">Filtros de Búsqueda</h3>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Desde</label>
                        <input type="date" v-model="form.start_date" @change="applyFilters"
                               class="w-full rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 text-xs font-bold text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner px-4 py-3" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Hasta</label>
                        <input type="date" v-model="form.end_date" @change="applyFilters"
                               class="w-full rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 text-xs font-bold text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner px-4 py-3" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Tipo de Sesión</label>
                        <select v-model="form.meal_type" @change="applyFilters"
                                class="w-full rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 text-xs font-bold uppercase text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner px-4 py-3">
                            <option value="">Todos los turnos</option>
                            <option v-for="opt in mealOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Proveedor</label>
                        <select v-model="form.provider_id" @change="applyFilters"
                                class="w-full rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 text-xs font-bold uppercase text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner px-4 py-3">
                            <option value="">Todos los proveedores</option>
                            <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>

                <div v-if="Object.values(form).some(v => v !== '')" class="flex justify-end pt-2 border-t border-slate-100 dark:border-gray-800">
                    <button @click="clearFilters" class="text-[10px] font-black uppercase text-rose-500 hover:text-rose-700 tracking-widest flex items-center gap-1 cursor-pointer">
                        <XMarkIcon class="h-4 w-4" /> Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- LISTADO DE REPORTES -->
            <div v-if="sessions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="session in sessions" :key="session.id" 
                     class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none transition-all hover:scale-[1.02] flex flex-col group">
                    <div class="flex justify-between items-start mb-6">
                        <div class="h-14 w-14 rounded-2xl flex items-center justify-center shadow-2xs border border-slate-200/80 dark:border-gray-700"
                             :class="getProviderColor(session.provider_id).bg + ' ' + getProviderColor(session.provider_id).text">
                            <ClipboardDocumentIcon class="h-7 w-7" />
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ session.date }}</p>
                            <span class="text-[9px] font-black px-2.5 py-0.5 rounded-lg border uppercase tracking-widest mt-1 inline-block shadow-2xs"
                                  :class="mealTypeTagColors[session.meal_type] || 'bg-tinto-50 text-tinto-800 border-tinto-200'">
                                {{ session.meal_type }}
                            </span>
                        </div>
                    </div>
                    
                    <h5 class="font-black text-lg text-slate-800 dark:text-white mb-6 leading-tight uppercase tracking-tight flex-1 flex items-center gap-1.5">
                        <span>👨‍🍳</span> {{ session.provider?.name }}
                    </h5>

                    <div class="flex items-center text-xs text-slate-500 mb-8 space-x-4">
                        <div class="flex items-center">
                            <CalendarDaysIcon class="h-4 w-4 mr-1 text-slate-400" />
                            {{ new Date(session.date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                        </div>
                        <div class="flex items-center">
                            <CheckBadgeIcon class="h-4 w-4 mr-1 text-nayarit-600" />
                            <span class="font-bold uppercase tracking-tighter text-nayarit-700 dark:text-emerald-400">Finalizado</span>
                        </div>
                    </div>
                    
                    <button @click="openExportModal(session)" 
                            class="w-full py-4 bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all flex items-center justify-center cursor-pointer shine-effect">
                        <ArrowDownTrayIcon class="h-4 w-4 mr-2 text-oro-300" /> Exportar Reporte
                    </button>
                </div>
            </div>
            
            <div v-else class="p-20 bg-white dark:bg-gray-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-gray-800 text-center shadow-inner">
                <MagnifyingGlassIcon class="h-12 w-12 text-slate-300 dark:text-gray-700 mx-auto mb-4" />
                <p class="text-slate-400 font-bold uppercase tracking-widest text-xs mb-6">No se encontraron reportes con los filtros seleccionados</p>
                
                <Link :href="route('dashboard')" 
                    class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-tinto-900 to-tinto-800 text-oro-300 border border-oro-400/40 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-tinto-sm hover:scale-105 active:scale-95 transition-all cursor-pointer">
                    <ArrowLeftIcon class="h-4 w-4 mr-2" /> Regresar al Dashboard
                </Link>
            </div>

        </div>

        <ExportChoiceModal 
            :show="showExportModal"
            @close="showExportModal = false"
            @select="handleExport"
        />
    </AuthenticatedLayout>
</template>
