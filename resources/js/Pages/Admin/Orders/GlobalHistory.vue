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

const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700', 'Comida': 'bg-indigo-100 text-indigo-700', 'Cena': 'bg-purple-100 text-purple-700', 'Extra': 'bg-teal-100 text-teal-700' };
</script>

<template>
    <Head title="Historial V2.0" />

    <AuthenticatedLayout bento-tag="Bitácora">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 space-y-8">
                    <div class="flex items-center gap-3"><FunnelIcon class="h-6 w-6 text-indigo-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Filtros de Historial</h3></div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Establecimiento</label><select v-model="form.provider_id" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 shadow-inner appearance-none"><option value="">Todos</option><option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                        <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Desde</label><input type="date" v-model="form.start_date" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 shadow-inner" /></div>
                        <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Hasta</label><input type="date" v-model="form.end_date" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 shadow-inner" /></div>
                        <div class="flex items-end"><button v-if="Object.values(form).some(v => v)" @click="clearFilters" class="text-[10px] font-black uppercase text-rose-500 hover:underline mb-4">Limpiar</button></div>
                    </div>
                </div>
            </div>

            <div v-if="sessions.data.length > 0" class="lg:col-span-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="session in sessions.data" :key="session.id" class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-slate-100 dark:border-gray-800 shadow-sm hover:scale-105 transition-all group">
                    <div class="flex justify-between items-start mb-6">
                        <div class="h-14 w-14 bg-slate-50 dark:bg-gray-800 rounded-2xl flex flex-col items-center justify-center border shadow-inner">
                            <span class="text-[8px] font-black text-slate-400 uppercase">{{ new Date(session.date + 'T12:00:00').toLocaleDateString('es-ES', { month: 'short' }) }}</span>
                            <span class="text-xl font-black text-slate-800 dark:text-white">{{ new Date(session.date + 'T12:00:00').getDate() }}</span>
                        </div>
                        <span class="px-3 py-1 rounded-lg text-[8px] font-black uppercase border" :class="mealTypeTagColors[session.meal_type]">{{ session.meal_type }}</span>
                    </div>
                    <h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tighter mb-6 truncate">{{ session.provider.name }}</h4>
                    <div class="pt-6 border-t dark:border-gray-800 flex justify-between items-center">
                        <p class="text-[9px] font-black text-slate-400 uppercase">Ver detalles</p>
                        <Link :href="route('admin.orders.summary', { provider: session.provider_id, date: session.date, meal_type: session.meal_type })" class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg active:scale-90 transition-all"><ChevronRightIcon class="h-5 w-5" /></Link>
                    </div>
                </div>
            </div>

            <div v-else class="lg:col-span-12 p-20 bg-white dark:bg-gray-900 rounded-[4rem] border-2 border-dashed text-center">
                <CalendarDaysIcon class="h-16 w-16 text-slate-200 mx-auto mb-6" />
                <p class="text-slate-400 font-black uppercase tracking-widest">Sin registros históricos</p>
            </div>

            <div class="lg:col-span-12 mt-8">
                <div v-if="sessions.last_page > 1" class="flex justify-between items-center bg-white dark:bg-gray-900 p-6 rounded-3xl border"><div class="text-[10px] font-bold text-slate-400 uppercase">Pág {{ sessions.current_page }} de {{ sessions.last_page }}</div><div class="flex gap-4"><Link v-if="sessions.prev_page_url" :href="sessions.prev_page_url" class="text-[10px] font-black uppercase text-indigo-600">Ant</Link><Link v-if="sessions.next_page_url" :href="sessions.next_page_url" class="text-[10px] font-black uppercase text-indigo-600">Sig</Link></div></div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
