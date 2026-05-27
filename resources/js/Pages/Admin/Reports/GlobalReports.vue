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

const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700', 'Comida': 'bg-indigo-100 text-indigo-700', 'Cena': 'bg-purple-100 text-purple-700', 'Extra': 'bg-teal-100 text-teal-700' };
const providerColors = [{ text: 'text-indigo-600', bg: 'bg-indigo-50 dark:bg-indigo-900/30' }, { text: 'text-emerald-600', bg: 'bg-emerald-50 dark:bg-emerald-900/30' }, { text: 'text-rose-600', bg: 'bg-rose-50 dark:bg-rose-900/30' }, { text: 'text-amber-600', bg: 'bg-amber-50 dark:bg-amber-900/30' }];
const getProviderColor = (id) => providerColors[id % providerColors.length];

const showExportModal = ref(false);
const handleExport = (format) => { window.open(route('admin.reports.export', { ...form.value, format }), '_blank'); };
</script>

<template>
    <Head title="Reportes V2.0" />

    <AuthenticatedLayout bento-tag="Estadísticas">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12 flex justify-between items-center mb-4">
                <div class="flex items-center gap-3"><ChartBarIcon class="h-6 w-6 text-indigo-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Análisis Operativo Global</h3></div>
                <button @click="showExportModal = true" class="bg-slate-900 dark:bg-indigo-600 hover:bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-xl shadow-indigo-500/20 transition-all active:scale-95"><PrinterIcon class="h-4 w-4 mr-2" /> Exportar Datos</button>
            </div>

            <!-- PROVIDER STATS -->
            <div v-for="(stat, index) in providerStats" :key="stat.id" class="lg:col-span-3 bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-slate-100 dark:border-gray-800 shadow-sm">
                <div class="flex justify-between items-start mb-6"><div class="h-12 w-12 rounded-2xl flex items-center justify-center shadow-md" :class="getProviderColor(index).bg + ' ' + getProviderColor(index).text"><ShoppingBagIcon class="h-6 w-6" /></div><span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Establecimiento</span></div>
                <h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tighter mb-4 truncate">{{ stat.name }}</h4>
                <div class="flex justify-between items-end"><p class="text-[10px] font-bold text-slate-400 uppercase">Total Servido</p><p class="text-2xl font-black text-indigo-600">{{ stat.total_orders }}</p></div>
            </div>

            <!-- FILTERS -->
            <div class="lg:col-span-12 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-100 dark:border-gray-800 shadow-sm space-y-8">
                <div class="flex items-center gap-3"><FunnelIcon class="h-5 w-5 text-indigo-500" /><p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Parámetros de Auditoría</p></div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Inicio</label><input type="date" v-model="form.start_date" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 focus:ring-indigo-500 shadow-inner" /></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Fin</label><input type="date" v-model="form.end_date" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 focus:ring-indigo-500 shadow-inner" /></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Área</label><select v-model="form.area_id" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 focus:ring-indigo-500 shadow-inner"><option value="">Todas</option><option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Proveedor</label><select v-model="form.provider_id" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 focus:ring-indigo-500 shadow-inner"><option value="">Todos</option><option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Sesión</label><select v-model="form.meal_type" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 focus:ring-indigo-500 shadow-inner"><option value="">Todas</option><option v-for="m in mealOptions" :key="m" :value="m">{{ m }}</option></select></div>
                </div>
                <div v-if="Object.values(form).some(v => v)" class="flex justify-end"><button @click="clearFilters" class="text-[10px] font-black uppercase text-rose-500 hover:underline">Limpiar Filtros</button></div>
            </div>

            <!-- TABLE -->
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead><tr class="bg-slate-50/50 dark:bg-gray-800/50 border-b border-slate-100 dark:border-gray-800"><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Fecha / Tipo</th><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Comensal / Área</th><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Menú / Proveedor</th><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Estado</th></tr></thead>
                        <tbody class="divide-y border-slate-50 dark:divide-gray-800">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-indigo-50/20 dark:hover:bg-indigo-900/5 transition-all">
                                <td class="p-6"><p class="font-black text-sm text-slate-800 dark:text-gray-200">{{ order.daily_menu.available_on }}</p><span class="text-[8px] font-black px-2 py-0.5 rounded border uppercase" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span></td>
                                <td class="p-6"><p class="font-black text-sm text-slate-800 dark:text-gray-200 uppercase tracking-tight">{{ order.user.name }}</p><p class="text-[9px] font-bold text-slate-400 uppercase flex items-center"><BuildingOfficeIcon class="h-3 w-3 mr-1" /> {{ order.user.area.name }}</p></td>
                                <td class="p-6"><p class="font-black text-xs text-slate-700 dark:text-gray-300 uppercase leading-tight mb-1">{{ order.daily_menu.name }}</p><p class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">{{ order.daily_menu.provider.name }}</p></td>
                                <td class="p-6"><div class="flex items-center gap-2"><div class="h-2 w-2 rounded-full" :class="order.status === 'submitted_by_manager' ? 'bg-emerald-500' : 'bg-orange-400'"></div><span class="text-[9px] font-black uppercase tracking-widest" :class="order.status === 'submitted_by_manager' ? 'text-emerald-600' : 'text-orange-500'">{{ order.status === 'submitted_by_manager' ? 'Firmado' : 'Pendiente' }}</span></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination omitted for brevity if simple or implement full -->
                <div v-if="orders.last_page > 1" class="mt-8 flex justify-between items-center bg-white dark:bg-gray-900 p-6 rounded-2xl border"><div class="text-[10px] font-bold text-slate-400 uppercase">Pág {{ orders.current_page }} de {{ orders.last_page }}</div><div class="flex gap-4"><Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="text-[10px] font-black uppercase text-indigo-600">Anterior</Link><Link v-if="orders.next_page_url" :href="orders.next_page_url" class="text-[10px] font-black uppercase text-indigo-600">Siguiente</Link></div></div>
            </div>
        </div>

        <ExportChoiceModal :show="showExportModal" @close="showExportModal = false" @select="handleExport" />
    </AuthenticatedLayout>
</template>
