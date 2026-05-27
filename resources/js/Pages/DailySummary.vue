<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    CalendarDaysIcon, HeartIcon, ShieldCheckIcon, UserGroupIcon, ArrowTrendingUpIcon, SparklesIcon, ChartBarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ stats: Object });

const mealTypeColors = { 'Desayuno': 'bg-amber-500', 'Comida': 'bg-indigo-600', 'Cena': 'bg-purple-700', 'Extra': 'bg-teal-600' };
const maxWeeklyCount = computed(() => Math.max(...props.stats.system.weekly_activity.map(d => d.count), 1));
const totalDailyOrders = computed(() => Object.values(props.stats.system.meal_distribution).reduce((a, b) => a + b, 0));

const mealPercentages = computed(() => {
    const res = [];
    Object.entries(props.stats.system.meal_distribution).forEach(([type, count]) => {
        res.push({ type, count, percent: totalDailyOrders.value > 0 ? (count / totalDailyOrders.value * 100).toFixed(1) : 0 });
    });
    return res;
});
</script>

<template>
    <Head title="Estadísticas V2.0" />

    <AuthenticatedLayout bento-tag="Análisis">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12 flex items-center gap-3 mb-4"><ArrowTrendingUpIcon class="h-6 w-6 text-indigo-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Resumen de Actividad</h3></div>

            <div class="lg:col-span-4 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800">
                <div class="h-12 w-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white mb-6 shadow-lg shadow-indigo-200 dark:shadow-none"><CalendarDaysIcon class="h-6 w-6" /></div>
                <p class="text-4xl font-black text-slate-800 dark:text-white leading-none">{{ stats.personal.monthly_count }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Pedidos realizados este mes</p>
            </div>

            <div class="lg:col-span-4 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800">
                <div class="h-12 w-12 bg-rose-500 rounded-2xl flex items-center justify-center text-white mb-6 shadow-lg shadow-rose-200 dark:shadow-none"><HeartIcon class="h-6 w-6" /></div>
                <p class="text-xl font-black text-slate-800 dark:text-white leading-none truncate uppercase tracking-tighter">{{ stats.personal.favorite_dish }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Platillo más solicitado</p>
            </div>

            <div class="lg:col-span-4 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800">
                <div class="h-12 w-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-white mb-6 shadow-lg shadow-emerald-200 dark:shadow-none"><ShieldCheckIcon class="h-6 w-6" /></div>
                <div class="flex items-end gap-3"><p class="text-4xl font-black text-slate-800 dark:text-white leading-none">{{ stats.personal.justification_rate }}%</p><div class="flex-1 h-2 bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden mb-1"><div class="h-full bg-emerald-500 rounded-full" :style="{ width: stats.personal.justification_rate + '%' }"></div></div></div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Porcentaje de Justificación</p>
            </div>

            <div class="lg:col-span-6 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-100 dark:border-gray-800 shadow-sm">
                <div class="flex items-center gap-4 mb-10"><div class="h-10 w-10 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center"><UserGroupIcon class="h-5 w-5 text-indigo-600" /></div><h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tight">Distribución por Turno</h4></div>
                <div class="space-y-6">
                    <div v-for="item in mealPercentages" :key="item.type" class="space-y-2">
                        <div class="flex justify-between items-center px-1"><span class="text-[10px] font-black uppercase text-slate-400">{{ item.type }}</span><span class="text-[10px] font-black text-indigo-600">{{ item.count }} p ({{ item.percent }}%)</span></div>
                        <div class="h-3 w-full bg-slate-50 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner"><div class="h-full transition-all duration-1000" :class="mealTypeColors[item.type] || 'bg-slate-400'" :style="{ width: item.percent + '%' }"></div></div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-6 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-100 dark:border-gray-800 shadow-sm">
                <div class="flex items-center gap-4 mb-10"><div class="h-10 w-10 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center"><ChartBarIcon class="h-5 w-5 text-emerald-600" /></div><h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tight">Frecuencia de Uso</h4></div>
                <div class="flex items-end justify-between h-48 gap-4 px-2">
                    <div v-for="day in stats.system.weekly_activity" :key="day.day" class="flex-1 flex flex-col items-center gap-3">
                        <div class="w-full bg-indigo-500 rounded-xl transition-all duration-500 hover:bg-indigo-400 shadow-sm" :style="{ height: (day.count / maxWeeklyCount * 100) + '%' }"></div>
                        <span class="text-[9px] font-black uppercase text-slate-400">{{ day.day }}</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-12 bg-indigo-600 rounded-[3rem] p-12 text-white relative overflow-hidden shadow-2xl transition-all hover:scale-[1.01]">
                <div class="absolute right-0 bottom-0 opacity-10"><SparklesIcon class="h-64 w-64 translate-x-20 translate-y-20 rotate-12" /></div>
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-10 text-center md:text-left">
                    <div><h4 class="text-4xl font-black uppercase tracking-tighter mb-4">Desempeño del Área</h4><p class="text-indigo-100 text-sm max-w-lg font-medium leading-relaxed">Actualmente el equipo registra una participación del <span class="text-white font-black">{{ stats.area.participation_rate }}%</span> en el sistema operativo.</p></div>
                    <div class="bg-white/20 backdrop-blur-xl p-8 rounded-[2.5rem] border border-white/20 shadow-inner flex flex-col items-center min-w-[180px]"><span class="text-6xl font-black">{{ stats.area.participation_rate }}%</span><span class="text-[10px] font-black uppercase tracking-[0.3em] mt-4 opacity-70">Participación</span></div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
