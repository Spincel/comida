<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ChartBarIcon, 
    UserGroupIcon, 
    TrophyIcon, 
    BuildingStorefrontIcon,
    ArrowLeftIcon,
    CheckBadgeIcon,
    InformationCircleIcon,
    ClockIcon,
    DocumentChartBarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object
});

const getDishEmoji = (name) => {
    if (!name) return '🍽️';
    const n = name.toLowerCase();
    if (n.includes('huevo') || n.includes('chilaquil') || n.includes('omelet') || n.includes('hot cake') || n.includes('waffle')) return '🍳';
    if (n.includes('cafe') || n.includes('café') || n.includes('jugo') || n.includes('leche') || n.includes('avena')) return '☕';
    if (n.includes('carne') || n.includes('res') || n.includes('arrachera') || n.includes('bistec') || n.includes('milanesa') || n.includes('costilla')) return '🥩';
    if (n.includes('pollo') || n.includes('pechuga') || n.includes('alita') || n.includes('fajita')) return '🍗';
    if (n.includes('pescado') || n.includes('camaron') || n.includes('camarón') || n.includes('atun') || n.includes('marisco')) return '🐟';
    if (n.includes('taco') || n.includes('quesadilla') || n.includes('burrito') || n.includes('flauta') || n.includes('gordita')) return '🌮';
    if (n.includes('pasta') || n.includes('espagueti') || n.includes('lasaña')) return '🍝';
    if (n.includes('ensalada') || n.includes('vegetal') || n.includes('verdura') || n.includes('fruta')) return '🥗';
    if (n.includes('sopa') || n.includes('caldo') || n.includes('crema') || n.includes('pozole') || n.includes('menudo')) return '🍲';
    if (n.includes('hamburguesa') || n.includes('sandwich') || n.includes('torta')) return '🥪';
    if (n.includes('postre') || n.includes('pastel') || n.includes('gelatina') || n.includes('flan')) return '🍰';
    return '🍽️';
};
</script>

<template>
    <Head title="Estadísticas de Área SICOA" />

    <AuthenticatedLayout bento-tag="Analítica">
        <div class="space-y-10">
            
            <!-- HEADER INFO -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        📊
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Estadísticas y Métricas de Consumo</h2>
                        <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">{{ stats.area_name }}</p>
                    </div>
                </div>
                <Link :href="route('dashboard')" class="px-5 py-2.5 bg-slate-50 dark:bg-gray-800 text-slate-600 dark:text-gray-300 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-200 dark:border-gray-700 transition-all cursor-pointer">
                    ← Volver
                </Link>
            </div>
            
            <!-- KPI CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center gap-6">
                    <div class="h-16 w-16 bg-gradient-to-tr from-tinto-900 to-tinto-800 rounded-2xl flex items-center justify-center text-oro-300 shadow-tinto-sm border border-oro-400/40 shrink-0">
                        <ChartBarIcon class="h-8 w-8" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Consumo Total</p>
                        <p class="text-4xl font-black text-tinto-900 dark:text-oro-300 tracking-tight">{{ stats.total_orders }}</p>
                        <p class="text-[9px] font-bold text-tinto-700 dark:text-oro-400 uppercase mt-1">Platillos solicitados</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center gap-6">
                    <div class="h-16 w-16 bg-gradient-to-tr from-nayarit-900 to-nayarit-700 rounded-2xl flex items-center justify-center text-white shadow-sm shrink-0">
                        <CheckBadgeIcon class="h-8 w-8 text-emerald-300" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tasa de Justificación</p>
                        <p class="text-4xl font-black text-nayarit-800 dark:text-emerald-400 tracking-tight">{{ stats.justification_rate }}%</p>
                        <p class="text-[9px] font-bold text-nayarit-700 dark:text-emerald-400 uppercase mt-1">Personal con motivo</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center gap-6">
                    <div class="h-16 w-16 bg-gradient-to-tr from-oro-700 to-oro-500 rounded-2xl flex items-center justify-center text-white shadow-oro-sm shrink-0">
                        <TrophyIcon class="h-8 w-8 text-amber-200" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Platillo Favorito</p>
                        <p class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase truncate">{{ stats.top_dishes[0]?.name || 'N/A' }}</p>
                        <p class="text-[9px] font-bold text-oro-700 dark:text-oro-400 uppercase mt-1">Más solicitado del área</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <!-- TOP DINERS -->
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="h-12 w-12 bg-tinto-50 dark:bg-tinto-950/60 border border-tinto-200 dark:border-tinto-800 rounded-2xl flex items-center justify-center text-tinto-800 dark:text-oro-300">
                            <UserGroupIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Ranking de Comensales</h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Personal con mayor frecuencia</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div v-for="(diner, index) in stats.top_diners" :key="diner.id" 
                             class="flex items-center justify-between p-4 bg-slate-50/70 dark:bg-gray-800/50 rounded-2xl border border-slate-200/60 dark:border-gray-700 transition-all hover:scale-[1.01] group">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center font-black text-sm shadow-2xs border border-slate-200 dark:border-gray-700 text-slate-500 group-hover:text-tinto-800 group-hover:border-tinto-300 transition-colors">
                                    #{{ index + 1 }}
                                </div>
                                <div class="flex items-center gap-3">
                                    <img v-if="diner.avatar_url" :src="diner.avatar_url" class="h-10 w-10 rounded-xl border border-slate-200 shadow-2xs object-cover" />
                                    <div v-else class="h-10 w-10 rounded-xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 flex items-center justify-center font-black text-xs border border-tinto-200">
                                        {{ diner.name?.substring(0,2) }}
                                    </div>
                                    <p class="text-xs font-black text-slate-700 dark:text-gray-200 uppercase tracking-tight">{{ diner.name }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-tinto-800 dark:text-oro-300">{{ diner.orders_count }}</p>
                                <p class="text-[8px] font-bold text-slate-400 uppercase">Pedidos</p>
                            </div>
                        </div>
                        <div v-if="stats.top_diners.length === 0" class="py-12 text-center italic text-slate-400 text-xs">Sin registros de comensales</div>
                    </div>
                </div>

                <!-- PROVIDER PREFERENCES -->
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="h-12 w-12 bg-oro-50 dark:bg-oro-950/60 border border-oro-200 dark:border-oro-800 rounded-2xl flex items-center justify-center text-oro-700 dark:text-oro-300">
                            <BuildingStorefrontIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Preferencia por Proveedor</h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Distribución de consumo</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div v-for="provider in stats.provider_preferences" :key="provider.name" 
                             class="space-y-2">
                            <div class="flex justify-between items-end px-1">
                                <p class="text-[11px] font-black text-slate-700 dark:text-gray-200 uppercase tracking-widest flex items-center gap-1.5">
                                    <span>👨‍🍳</span> {{ provider.name }}
                                </p>
                                <p class="text-xs font-black text-tinto-800 dark:text-oro-300">{{ provider.total }} pedidos</p>
                            </div>
                            <div class="w-full h-3 bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden border border-slate-200 dark:border-gray-700">
                                <div class="h-full bg-gradient-to-r from-tinto-800 via-oro-500 to-oro-400 rounded-full shadow-inner transition-all duration-1000" 
                                     :style="{ width: (stats.total_orders > 0 ? (provider.total / stats.total_orders * 100) : 0) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="stats.provider_preferences.length === 0" class="py-12 text-center italic text-slate-400 text-xs">Sin preferencias registradas</div>
                    </div>
                </div>
            </div>

            <!-- TOP DISHES FULL LIST -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight mb-8 border-b border-slate-100 dark:border-gray-800 pb-4 flex items-center gap-2">
                    <span>🏆</span> Platillos Más Solicitados por el Personal
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div v-for="dish in stats.top_dishes" :key="dish.name" 
                         class="p-6 bg-slate-50/70 dark:bg-gray-800/50 rounded-3xl border border-slate-200/70 dark:border-gray-700 flex flex-col items-center text-center transition-all hover:scale-105 group">
                        <div class="h-14 w-14 bg-tinto-50 dark:bg-tinto-950/60 border border-tinto-200 dark:border-tinto-800 rounded-2xl flex items-center justify-center text-2xl shadow-xs mb-3 group-hover:rotate-12 transition-transform">
                            {{ getDishEmoji(dish.name) }}
                        </div>
                        <p class="text-[10px] font-black text-slate-800 dark:text-white uppercase leading-tight mb-2 h-8 flex items-center justify-center">{{ dish.name }}</p>
                        <p class="text-3xl font-black text-tinto-900 dark:text-oro-300 tracking-tight">{{ dish.total }}</p>
                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1">Solicitudes</p>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
