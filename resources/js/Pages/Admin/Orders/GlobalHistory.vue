<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    ChevronLeftIcon, ClockIcon, BuildingStorefrontIcon, CalendarDaysIcon, MagnifyingGlassIcon,
    FunnelIcon, XMarkIcon, ChevronRightIcon, DocumentChartBarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ sessions: Object, providers: Array, filters: Object });

const form = ref({ provider_id: props.filters.provider_id || '', start_date: props.filters.start_date || '', end_date: props.filters.end_date || '' });

const applyFilters = () => { router.get(route('admin.history'), form.value, { preserveState: true, preserveScroll: true }); };
const clearFilters = () => { form.value = { provider_id: '', start_date: '', end_date: '' }; applyFilters(); };

const mealTypeTagColors = { 
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800', 
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300 dark:border-tinto-800', 
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-800', 
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-800' 
};
</script>

<template>
    <Head title="Historial Global SICOA" />

    <AuthenticatedLayout bento-tag="Bitácora">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 space-y-8">
                    <div class="flex items-center gap-3">
                        <FunnelIcon class="h-6 w-6 text-tinto-700 dark:text-oro-400" />
                        <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Filtros de Historial Operativo</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Establecimiento</label>
                            <select v-model="form.provider_id" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold uppercase text-slate-700 dark:text-gray-200 shadow-inner appearance-none focus:ring-2 focus:ring-tinto-700">
                                <option value="">Todos los Proveedores</option>
                                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Fecha Inicio</label>
                            <input type="date" v-model="form.start_date" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-gray-200 shadow-inner focus:ring-2 focus:ring-tinto-700" />
                        </div>
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Fecha Fin</label>
                            <input type="date" v-model="form.end_date" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-gray-200 shadow-inner focus:ring-2 focus:ring-tinto-700" />
                        </div>
                        <div class="flex items-end">
                            <button v-if="Object.values(form).some(v => v)" @click="clearFilters" class="text-[10px] font-black uppercase text-rose-500 hover:text-rose-700 tracking-widest mb-4 cursor-pointer">✕ Limpiar Filtros</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="sessions.data.length > 0" class="lg:col-span-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="session in sessions.data" :key="session.id" class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none hover:scale-[1.03] transition-all group">
                    <div class="flex justify-between items-start mb-6">
                        <div class="h-14 w-14 bg-slate-50 dark:bg-gray-800 rounded-2xl flex flex-col items-center justify-center border border-slate-200 dark:border-gray-700 shadow-inner">
                            <span class="text-[8px] font-black text-slate-400 uppercase">{{ new Date(session.date + 'T12:00:00').toLocaleDateString('es-ES', { month: 'short' }) }}</span>
                            <span class="text-xl font-black text-tinto-900 dark:text-oro-300">{{ new Date(session.date + 'T12:00:00').getDate() }}</span>
                        </div>
                        <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase border shadow-2xs flex items-center gap-1.5" :class="mealTypeTagColors[session.meal_type]">
                            <span>{{ session.meal_type === 'Desayuno' ? '🍳' : (session.meal_type === 'Comida' ? '🍲' : '🌙') }}</span>
                            {{ session.meal_type }}
                        </span>
                    </div>
                    <h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tight mb-6 truncate flex items-center gap-1.5">
                        <span>👨‍🍳</span> {{ session.provider?.name }}
                    </h4>
                    <div class="pt-6 border-t border-slate-100 dark:border-gray-800 flex justify-between items-center">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Ver Monitor Detallado</p>
                        <Link :href="route('admin.orders.summary', { provider: session.provider_id, date: session.date, meal_type: session.meal_type })" class="h-10 w-10 bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 border border-oro-400/40 rounded-xl flex items-center justify-center shadow-tinto-sm active:scale-90 transition-all cursor-pointer">
                            <ChevronRightIcon class="h-5 w-5" />
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="lg:col-span-12 p-20 bg-white dark:bg-gray-900 rounded-[4rem] border border-dashed border-slate-200 dark:border-gray-800 text-center shadow-inner">
                <CalendarDaysIcon class="h-16 w-16 text-slate-300 dark:text-gray-700 mx-auto mb-4" />
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Sin registros históricos con los filtros seleccionados</p>
            </div>

            <div class="lg:col-span-12 mt-8">
                <div v-if="sessions.last_page > 1" class="flex justify-between items-center bg-white dark:bg-gray-900 p-6 rounded-3xl border border-slate-200/80 dark:border-gray-800 shadow-sm">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Página {{ sessions.current_page }} de {{ sessions.last_page }}</div>
                    <div class="flex gap-4">
                        <Link v-if="sessions.prev_page_url" :href="sessions.prev_page_url" class="text-[10px] font-black uppercase text-tinto-800 dark:text-oro-300 hover:underline">← Anterior</Link>
                        <Link v-if="sessions.next_page_url" :href="sessions.next_page_url" class="text-[10px] font-black uppercase text-tinto-800 dark:text-oro-300 hover:underline">Siguiente →</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
