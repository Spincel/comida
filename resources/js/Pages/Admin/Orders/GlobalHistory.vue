<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ChevronLeftIcon, 
    ClipboardDocumentListIcon,
    CalendarDaysIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    FunnelIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sessions: Object, // Paginated
    providers: Array,
    filters: Object,
});

const form = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    provider_id: props.filters.provider_id || '',
});

const applyFilters = () => {
    router.get(route('admin.history'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    form.value = { start_date: '', end_date: '', provider_id: '' };
    applyFilters();
};

const startDateInput = ref(null);
const endDateInput = ref(null);

const triggerPicker = (inputRef) => {
    if (inputRef && inputRef.showPicker) {
        inputRef.showPicker();
    }
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
    <Head title="Historial Global" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('dashboard')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                </Link>
                <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                    Historial de Sesiones
                </h2>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- FILTROS PREMIUM -->
                <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-gray-400 ml-2 tracking-widest leading-none">Desde la fecha:</label>
                            <div class="relative group cursor-pointer" @click="triggerPicker(startDateInput)">
                                <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition-colors pointer-events-none" />
                                <input type="date" ref="startDateInput" v-model="form.start_date" @change="applyFilters"
                                       class="w-full pl-12 pr-4 py-3.5 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-black text-gray-700 dark:text-white uppercase focus:border-indigo-500 focus:ring-0 transition-all cursor-pointer [color-scheme:light] dark:[color-scheme:dark]" />
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-gray-400 ml-2 tracking-widest leading-none">Hasta la fecha:</label>
                            <div class="relative group cursor-pointer" @click="triggerPicker(endDateInput)">
                                <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition-colors pointer-events-none" />
                                <input type="date" ref="endDateInput" v-model="form.end_date" @change="applyFilters"
                                       class="w-full pl-12 pr-4 py-3.5 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-black text-gray-700 dark:text-white uppercase focus:border-indigo-500 focus:ring-0 transition-all cursor-pointer [color-scheme:light] dark:[color-scheme:dark]" />
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-gray-400 ml-2 tracking-widest leading-none">Proveedor:</label>
                            <div class="relative group">
                                <BuildingStorefrontIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors pointer-events-none" />
                                <select v-model="form.provider_id" @change="applyFilters"
                                        class="w-full pl-12 pr-10 py-3.5 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-black text-gray-700 dark:text-white uppercase focus:border-indigo-500 focus:ring-0 transition-all appearance-none">
                                    <option value="">Todos los proveedores</option>
                                    <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div v-if="Object.values(form).some(v => v !== '')" class="mt-6 flex justify-end">
                        <button @click="clearFilters" class="text-[10px] font-black text-red-500 uppercase flex items-center gap-2 hover:scale-105 transition-transform">
                            <XMarkIcon class="h-4 w-4" /> Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- GRILLA DE SESIONES -->
                <div v-if="sessions.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="session in sessions.data" :key="session.id" 
                         class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border border-gray-100 dark:border-gray-700 shadow-xl transition-all hover:scale-[1.02] flex flex-col group">
                        
                        <div class="flex justify-between items-start mb-6">
                            <div class="h-14 w-14 rounded-3xl flex items-center justify-center shadow-sm transition-transform group-hover:rotate-12"
                                 :class="getProviderColor(session.provider_id).bg + ' ' + getProviderColor(session.provider_id).text">
                                <CalendarDaysIcon class="h-8 w-8" />
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ session.date }}</p>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                      :class="mealTypeTagColors[session.meal_type] || 'text-indigo-500 border-indigo-100'">
                                    {{ session.meal_type }}
                                </span>
                            </div>
                        </div>

                        <h5 class="font-black text-2xl text-gray-800 dark:text-white mb-6 uppercase tracking-tight leading-tight flex-1"
                            :class="getProviderColor(session.provider_id).text">
                            {{ session.provider.name }}
                        </h5>

                        <Link :href="route('admin.orders.summary', { provider: session.provider_id, date: session.date, meal_type: session.meal_type })" 
                              class="w-full py-4 bg-gray-900 dark:bg-gray-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 transition-all flex items-center justify-center shadow-lg">
                            Ver Resumen Detallado <ArrowRightIcon class="h-4 w-4 ml-2" />
                        </Link>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-800 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700 text-center">
                    <ClipboardDocumentListIcon class="h-16 w-16 text-gray-200 mx-auto mb-4" />
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm mb-6">No se encontraron sesiones en el historial</p>
                    
                    <Link :href="route('dashboard')" 
                        class="inline-flex items-center px-8 py-4 bg-indigo-600 dark:bg-indigo-500 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-500/20 active:scale-95">
                        <ArrowLeftIcon class="h-4 w-4 mr-2" /> Regresar al Dashboard
                    </Link>
                </div>

                <!-- PAGINACIÓN -->
                <div v-if="sessions.last_page > 1" class="flex justify-center pb-12 gap-2">
                    <Link v-for="link in sessions.links" :key="link.label"
                          :href="link.url"
                          v-html="link.label"
                          class="px-4 py-2 rounded-xl text-xs font-black uppercase transition-all"
                          :class="link.active ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-400 hover:bg-gray-50 border border-gray-100 dark:border-gray-700'"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
