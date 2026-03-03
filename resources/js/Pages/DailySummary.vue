<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    CalendarDaysIcon, 
    HeartIcon, 
    ShieldCheckIcon, 
    UserGroupIcon,
    ArrowTrendingUpIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object
});

const mealTypeColors = {
    'Desayuno': 'bg-amber-500',
    'Comida': 'bg-indigo-600',
    'Cena': 'bg-purple-700',
    'Extra': 'bg-teal-600',
};

const maxWeeklyCount = computed(() => {
    return Math.max(...props.stats.system.weekly_activity.map(d => d.count), 1);
});

// Calculate SVG Pie/Donut (simulated simple distribution)
const totalDailyOrders = computed(() => {
    return Object.values(props.stats.system.meal_distribution).reduce((a, b) => a + b, 0);
});

const mealPercentages = computed(() => {
    const res = [];
    Object.entries(props.stats.system.meal_distribution).forEach(([type, count]) => {
        res.push({
            type,
            count,
            percent: totalDailyOrders.value > 0 ? (count / totalDailyOrders.value * 100).toFixed(1) : 0
        });
    });
    return res;
});
</script>

<template>
    <Head title="Resumen Diario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-white uppercase tracking-tight leading-none">Resumen Diario</h2>
                    <p class="text-xs font-bold text-gray-400 uppercase mt-2 tracking-widest">Estadísticas e impacto en el sistema</p>
                </div>
                <div class="h-12 w-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center border border-indigo-100 dark:border-indigo-800">
                    <ArrowTrendingUpIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-[85%] mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <!-- PERSONAL STATS -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-xl border-2 border-gray-50 dark:border-gray-700 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
                    <div class="relative z-10">
                        <div class="h-12 w-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white mb-6 shadow-lg shadow-indigo-200 dark:shadow-none">
                            <CalendarDaysIcon class="h-6 w-6" />
                        </div>
                        <p class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ stats.personal.monthly_count }}</p>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2">Pedidos este mes</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-xl border-2 border-gray-50 dark:border-gray-700 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-rose-50 dark:bg-rose-900/20 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
                    <div class="relative z-10">
                        <div class="h-12 w-12 bg-rose-500 rounded-2xl flex items-center justify-center text-white mb-6 shadow-lg shadow-rose-200 dark:shadow-none">
                            <HeartIcon class="h-6 w-6" />
                        </div>
                        <p class="text-xl font-black text-gray-900 dark:text-white leading-none truncate uppercase tracking-tight">{{ stats.personal.favorite_dish }}</p>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2">Tu platillo estrella</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-xl border-2 border-gray-50 dark:border-gray-700 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
                    <div class="relative z-10">
                        <div class="h-12 w-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-white mb-6 shadow-lg shadow-emerald-200 dark:shadow-none">
                            <ShieldCheckIcon class="h-6 w-6" />
                        </div>
                        <div class="flex items-end gap-2">
                            <p class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ stats.personal.justification_rate }}%</p>
                            <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden mb-1">
                                <div class="h-full bg-emerald-500 rounded-full" :style="{ width: stats.personal.justification_rate + '%' }"></div>
                            </div>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2">Tasa de Justificación</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- CHART: MEAL DISTRIBUTION -->
                <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-50 dark:border-gray-700">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="h-10 w-10 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center"><UserGroupIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" /></div>
                        <h3 class="font-black text-lg text-gray-800 dark:text-white uppercase tracking-tight">Distribución de Hoy</h3>
                    </div>

                    <div class="space-y-6">
                        <div v-for="item in mealPercentages" :key="item.type" class="space-y-2">
                            <div class="flex justify-between items-center px-1">
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ item.type }}</span>
                                <span class="text-[10px] font-black text-indigo-600">{{ item.count }} pedidos ({{ item.percent }}%)</span>
                            </div>
                            <div class="h-4 w-full bg-gray-100 dark:bg-gray-900 rounded-full overflow-hidden flex shadow-inner">
                                <div class="h-full transition-all duration-1000" :class="mealTypeColors[item.type] || 'bg-gray-400'" :style="{ width: item.percent + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="mealPercentages.length === 0" class="text-center py-10 text-gray-400 italic text-xs uppercase tracking-widest">No hay datos para mostrar hoy</div>
                    </div>
                </div>

                <!-- CHART: WEEKLY ACTIVITY -->
                <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-50 dark:border-gray-700">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="h-10 w-10 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center"><CalendarDaysIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" /></div>
                        <h3 class="font-black text-lg text-gray-800 dark:text-white uppercase tracking-tight">Actividad Semanal</h3>
                    </div>

                    <div class="flex items-end justify-between h-48 gap-2 px-2">
                        <div v-for="day in stats.system.weekly_activity" :key="day.day" class="flex-1 flex flex-col items-center gap-3 group">
                            <div class="w-full bg-indigo-500 rounded-t-xl transition-all duration-500 group-hover:bg-indigo-400 shadow-lg shadow-indigo-100 dark:shadow-none" 
                                 :style="{ height: (day.count / maxWeeklyCount * 100) + '%' }"></div>
                            <span class="text-[9px] font-black uppercase text-gray-400 group-hover:text-indigo-600 transition-colors">{{ day.day }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AREA INSIGHT -->
            <div class="mt-8 bg-indigo-900 rounded-[3rem] p-10 text-white relative overflow-hidden shadow-2xl">
                <div class="absolute right-0 bottom-0 opacity-10"><SparklesIcon class="h-64 w-64 translate-x-20 translate-y-20 rotate-12" /></div>
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div>
                        <h4 class="text-3xl font-black uppercase tracking-tighter leading-none mb-4">Impacto en tu Área</h4>
                        <p class="text-indigo-200 text-sm max-w-md">Hoy el <span class="text-white font-black">{{ stats.area.participation_rate }}%</span> de tu equipo ha utilizado el sistema correctamente. ¡Mantengamos el ritmo!</p>
                    </div>
                    <div class="flex flex-col items-center bg-white/10 backdrop-blur-xl p-6 rounded-3xl border border-white/20">
                        <span class="text-5xl font-black leading-none">{{ stats.area.participation_rate }}%</span>
                        <span class="text-[8px] font-black uppercase tracking-[0.3em] mt-3 opacity-60">Participación</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
