<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import { 
    ChevronLeftIcon, ChartBarIcon, UsersIcon, BuildingOfficeIcon, ShoppingBagIcon, FunnelIcon, MagnifyingGlassIcon,
    XMarkIcon, PrinterIcon, CalendarDaysIcon, ClipboardDocumentIcon, ClockIcon, BuildingStorefrontIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ providerStats: Array, orders: Object, areas: Array, providers: Array, filters: Object });

const form = ref({ area_id: props.filters.area_id || '', provider_id: props.filters.provider_id || '', meal_type: props.filters.meal_type || '', start_date: props.filters.start_date || '', end_date: props.filters.end_date || '' });

const mealOptions = ['Desayuno', 'Comida', 'Cena', 'Extra'];
const applyFilters = () => router.get(route('admin.reports'), form.value, { preserveState: true, preserveScroll: true });
const clearFilters = () => { form.value = { area_id: '', provider_id: '', meal_type: '', start_date: '', end_date: '' }; applyFilters(); };

const mealTypeTagColors = { 
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800', 
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300 dark:border-tinto-800', 
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-800', 
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-800' 
};
const providerColors = [
    { text: 'text-tinto-800 dark:text-oro-300', bg: 'bg-tinto-50 dark:bg-tinto-950/60' }, 
    { text: 'text-oro-800 dark:text-oro-300', bg: 'bg-oro-50 dark:bg-oro-950/60' }, 
    { text: 'text-nayarit-800 dark:text-emerald-300', bg: 'bg-emerald-50 dark:bg-emerald-950/60' }, 
    { text: 'text-amber-800 dark:text-amber-300', bg: 'bg-amber-50 dark:bg-amber-950/60' }
];
const getProviderColor = (id) => providerColors[id % providerColors.length];

const showExportModal = ref(false);
const handleExport = (format) => { window.open(route('admin.reports.export', { ...form.value, format }), '_blank'); };
</script>

<template>
    <Head title="Reportes Operativos SICOA" />

    <AuthenticatedLayout bento-tag="Estadísticas">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12 flex justify-between items-center mb-2">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-tinto-50 dark:bg-tinto-950/60 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-tinto-800 dark:text-oro-400">
                        <ChartBarIcon class="h-5 w-5" />
                    </div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Análisis Operativo Global</h3>
                </div>
                <button @click="showExportModal = true" class="bg-gradient-to-r from-oro-600 to-oro-500 hover:from-oro-700 hover:to-oro-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-oro-sm border border-oro-300/40 transition-all active:scale-95 cursor-pointer">
                    <PrinterIcon class="h-4 w-4 mr-2" /> Exportar Reporte
                </button>
            </div>

            <!-- PROVIDER STATS -->
            <div v-for="(stat, index) in providerStats" :key="stat.id" class="lg:col-span-3 bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none hover:scale-[1.02] transition-all">
                <div class="flex justify-between items-start mb-6">
                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center shadow-2xs border border-slate-200/60 dark:border-gray-700" :class="getProviderColor(index).bg + ' ' + getProviderColor(index).text">
                        <ShoppingBagIcon class="h-6 w-6" />
                    </div>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Proveedor</span>
                </div>
                <h4 class="font-black text-base text-slate-800 dark:text-white uppercase tracking-tight mb-4 truncate flex items-center gap-1">
                    <span>👨‍🍳</span> {{ stat.name }}
                </h4>
                <div class="flex justify-between items-end">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Servido</p>
                    <p class="text-2xl font-black text-tinto-900 dark:text-oro-300">{{ stat.total_orders }}</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="lg:col-span-12 bg-white dark:bg-gray-900 rounded-[3rem] p-8 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none space-y-6">
                <div class="flex items-center gap-3">
                    <FunnelIcon class="h-5 w-5 text-tinto-700 dark:text-oro-400" />
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Parámetros de Auditoría y Filtro</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Fecha Inicio</label><input type="date" v-model="form.start_date" @change="applyFilters" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner" /></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Fecha Fin</label><input type="date" v-model="form.end_date" @change="applyFilters" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner" /></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Área Solicitante</label><select v-model="form.area_id" @change="applyFilters" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold uppercase text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner"><option value="">Todas las Áreas</option><option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Proveedor</label><select v-model="form.provider_id" @change="applyFilters" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold uppercase text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner"><option value="">Todos los Proveedores</option><option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Turno / Sesión</label><select v-model="form.meal_type" @change="applyFilters" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold uppercase text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner"><option value="">Todas las Sesiones</option><option v-for="m in mealOptions" :key="m" :value="m">{{ m }}</option></select></div>
                </div>
                <div v-if="Object.values(form).some(v => v)" class="flex justify-end">
                    <button @click="clearFilters" class="text-[10px] font-black uppercase text-rose-500 hover:text-rose-700 tracking-widest cursor-pointer">✕ Limpiar Filtros</button>
                </div>
            </div>

            <!-- TABLE -->
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 dark:bg-gray-800/70 border-b border-slate-200/80 dark:border-gray-800">
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Fecha / Tipo</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Comensal / Área</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Menú / Proveedor</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-tinto-50/20 dark:hover:bg-tinto-950/20 transition-all">
                                <td class="p-6">
                                    <p class="font-black text-sm text-slate-800 dark:text-gray-200">{{ order.daily_menu.available_on }}</p>
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded-md border uppercase shadow-2xs" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span>
                                </td>
                                <td class="p-6">
                                    <p class="font-black text-sm text-slate-800 dark:text-gray-200 uppercase tracking-tight">{{ order.user.name }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase flex items-center mt-0.5"><BuildingOfficeIcon class="h-3 w-3 mr-1" /> {{ order.user.area?.name }}</p>
                                </td>
                                <td class="p-6">
                                    <p class="font-black text-xs text-slate-800 dark:text-gray-300 uppercase leading-tight mb-1">{{ order.daily_menu.name }}</p>
                                    <p class="text-[9px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest flex items-center gap-1"><span>👨‍🍳</span> {{ order.daily_menu.provider?.name }}</p>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full" :class="order.status === 'submitted_by_manager' ? 'bg-emerald-500' : 'bg-amber-400'"></div>
                                        <span class="text-[9px] font-black uppercase tracking-widest" :class="order.status === 'submitted_by_manager' ? 'text-nayarit-700 dark:text-emerald-400' : 'text-amber-600'">{{ order.status === 'submitted_by_manager' ? 'Firmado' : 'Pendiente' }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="orders.last_page > 1" class="mt-8 flex justify-between items-center bg-white dark:bg-gray-900 p-6 rounded-2xl border border-slate-200/80 dark:border-gray-800 shadow-sm">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Página {{ orders.current_page }} de {{ orders.last_page }}</div>
                    <div class="flex gap-4">
                        <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="text-[10px] font-black uppercase text-tinto-800 dark:text-oro-300 hover:underline">← Anterior</Link>
                        <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="text-[10px] font-black uppercase text-tinto-800 dark:text-oro-300 hover:underline">Siguiente →</Link>
                    </div>
                </div>
            </div>
        </div>

        <ExportChoiceModal :show="showExportModal" @close="showExportModal = false" @select="handleExport" />
    </AuthenticatedLayout>
</template>
