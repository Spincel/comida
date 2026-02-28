<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import { 
    ChevronLeftIcon, 
    ChartBarIcon,
    UsersIcon,
    BuildingOfficeIcon,
    ShoppingBagIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    PrinterIcon,
    CalendarDaysIcon,
    ClipboardDocumentIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    providerStats: Array,
    orders: Object, // Paginated
    areas: Array,
    providers: Array,
    filters: Object,
});

const form = ref({
    area_id: props.filters.area_id || '',
    provider_id: props.filters.provider_id || '',
    meal_type: props.filters.meal_type || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const mealOptions = ['Desayuno', 'Comida', 'Cena', 'Extra'];

const applyFilters = () => {
    router.get(route('admin.reports'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    form.value = { area_id: '', provider_id: '', meal_type: '', start_date: '', end_date: '' };
    applyFilters();
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

// --- Export Logic ---
const showExportModal = ref(false);

const handleExport = (format) => {
    // We'll use a dynamic URL based on current filters
    const params = { ...form.value, format };
    
    // We need a specific route for global export that doesn't strictly require a provider in the URL
    // Or we can adapt generatePdfReport to handle missing provider.
    // For now, let's create a specific global export route or adjust the logic.
    
    // Technical Decision: I'll add a 'dashboard.reports.export' route.
    const url = route('admin.reports.export', params);
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Reportes Globales" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center">
                    <Link :href="route('dashboard')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                            Estadísticas y Reportes
                        </h2>
                    </div>
                </div>
                
                <button @click="showExportModal = true" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center shadow-lg shadow-indigo-100 dark:shadow-none transition-all">
                    <PrinterIcon class="h-4 w-4 mr-2" /> Exportar Resultados
                </button>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-12">
                
                <!-- ESTADÍSTICAS POR PROVEEDOR -->
                <section class="space-y-6">
                    <div class="flex items-center space-x-3 px-2">
                        <ChartBarIcon class="h-6 w-6 text-indigo-600" />
                        <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Desempeño por Proveedor</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div v-for="(stat, index) in providerStats" :key="stat.id" 
                             class="bg-white dark:bg-gray-800 rounded-[2rem] p-6 border-2 transition-all hover:scale-[1.02]"
                             :class="getProviderColor(index).border || 'border-gray-100 dark:border-gray-700'">
                            <div class="flex justify-between items-start mb-4">
                                <div class="h-10 w-10 rounded-xl flex items-center justify-center shadow-sm"
                                     :class="getProviderColor(index).bg + ' ' + getProviderColor(index).text">
                                    <ShoppingBagIcon class="h-5 w-5" />
                                </div>
                                <span class="text-[8px] font-black uppercase text-gray-400 tracking-widest">Rank #{{ index + 1 }}</span>
                            </div>
                            <h4 class="font-black text-lg text-gray-800 dark:text-white mb-4 uppercase tracking-tight leading-tight"
                                :class="getProviderColor(index).text">{{ stat.name }}</h4>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-end">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase">Total Pedidos</p>
                                    <p class="text-2xl font-black leading-none" :class="getProviderColor(index).text">{{ stat.total_orders }}</p>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="font-bold text-gray-400 uppercase">Preferencia</span>
                                    <span class="font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                          :class="mealTypeTagColors[stat.most_popular_meal] || 'bg-gray-100 text-gray-700'">
                                        {{ stat.most_popular_meal }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FILTROS AVANZADOS -->
                <section class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2 mb-8">
                        <FunnelIcon class="h-5 w-5 text-indigo-500" />
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300">Filtros de Reporte Global</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">Desde</label>
                            <input type="date" v-model="form.start_date" @change="applyFilters" class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">Hasta</label>
                            <input type="date" v-model="form.end_date" @change="applyFilters" class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">Área</label>
                            <select v-model="form.area_id" @change="applyFilters" class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">Proveedor</label>
                            <select v-model="form.provider_id" @change="applyFilters" class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">Sesión</label>
                            <select v-model="form.meal_type" @change="applyFilters" class="w-full rounded-xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option v-for="m in mealOptions" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="Object.values(form).some(v => v !== '')" class="mt-6 flex justify-end">
                        <button @click="clearFilters" class="text-[10px] font-black uppercase text-red-500 hover:text-red-600 flex items-center gap-1">
                            <XMarkIcon class="h-4 w-4" /> Limpiar Filtros
                        </button>
                    </div>
                </section>

                <!-- LISTADO GLOBAL DE PEDIDOS -->
                <section class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                                        <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Fecha / Tipo</th>
                                        <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Comensal / Área</th>
                                        <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Platillo / Proveedor</th>
                                        <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    <tr v-for="order in orders.data" :key="order.id" class="hover:bg-indigo-50/20 dark:hover:bg-indigo-900/5 transition-colors">
                                        <td class="p-6">
                                            <p class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ order.daily_menu.available_on }}</p>
                                            <span class="text-[9px] font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                                  :class="mealTypeTagColors[order.meal_type] || 'bg-indigo-50 text-indigo-500'">
                                                {{ order.meal_type }}
                                            </span>
                                        </td>
                                        <td class="p-6">
                                            <p class="font-black text-sm text-gray-800 dark:text-gray-200 uppercase tracking-tight">{{ order.user.name }}</p>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase flex items-center">
                                                <BuildingOfficeIcon class="h-3 w-3 mr-1" /> {{ order.user.area.name }}
                                            </p>
                                        </td>
                                        <td class="p-6">
                                            <p class="font-bold text-sm text-gray-800 dark:text-gray-200">{{ order.daily_menu.name }}</p>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ order.daily_menu.provider.name }}</p>
                                        </td>
                                        <td class="p-6">
                                            <div class="flex items-center">
                                                <div class="h-2 w-2 rounded-full mr-2" 
                                                     :class="order.status === 'submitted_by_manager' ? 'bg-green-500' : 'bg-orange-400'"></div>
                                                <span class="text-[9px] font-black uppercase tracking-widest"
                                                      :class="order.status === 'submitted_by_manager' ? 'text-green-600' : 'text-orange-500'">
                                                    {{ order.status === 'submitted_by_manager' ? 'Confirmado' : 'Pendiente' }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINACIÓN -->
                        <div v-if="orders.last_page > 1" class="p-8 bg-gray-50 dark:bg-gray-900/50 border-t dark:border-gray-700 flex justify-between items-center">
                            <div class="text-[10px] font-bold text-gray-400 uppercase">Página {{ orders.current_page }} de {{ orders.last_page }}</div>
                            <div class="flex gap-4">
                                <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-700">Anterior</Link>
                                <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-700">Siguiente</Link>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <ExportChoiceModal 
            :show="showExportModal"
            @close="showExportModal = false"
            @select="handleExport"
        />
    </AuthenticatedLayout>
</template>
