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
</script>

<template>
    <Head title="Estadísticas de Área" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <Link :href="route('dashboard')" class="p-3 bg-white dark:bg-gray-800 rounded-2xl border shadow-sm hover:bg-gray-50 transition-all">
                        <ArrowLeftIcon class="h-6 w-6 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 uppercase tracking-tighter leading-none">Estadísticas de Área</h2>
                        <p class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em] mt-2 leading-none">{{ stats.area_name }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-[85%] mx-auto space-y-10">
                
                <!-- KPI CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white dark:bg-gray-800 p-10 rounded-[3.5rem] shadow-2xl border-2 border-indigo-50 flex items-center gap-8">
                        <div class="h-20 w-20 bg-indigo-600 rounded-[2.5rem] flex items-center justify-center text-white shadow-2xl shadow-indigo-900/20">
                            <ChartBarIcon class="h-10 w-10" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Consumo Total</p>
                            <p class="text-5xl font-black text-gray-800 dark:text-white tracking-tighter">{{ stats.total_orders }}</p>
                            <p class="text-[9px] font-bold text-indigo-500 uppercase mt-2">Platillos servidos</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-10 rounded-[3.5rem] shadow-2xl border-2 border-emerald-50 flex items-center gap-8">
                        <div class="h-20 w-20 bg-emerald-600 rounded-[2.5rem] flex items-center justify-center text-white shadow-2xl shadow-emerald-900/20">
                            <CheckBadgeIcon class="h-10 w-10" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tasa Justificación</p>
                            <p class="text-5xl font-black text-gray-800 dark:text-white tracking-tighter">{{ stats.justification_rate }}%</p>
                            <p class="text-[9px] font-bold text-emerald-500 uppercase mt-2">Personal al corriente</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-10 rounded-[3.5rem] shadow-2xl border-2 border-rose-50 flex items-center gap-8">
                        <div class="h-20 w-20 bg-rose-600 rounded-[2.5rem] flex items-center justify-center text-white shadow-2xl shadow-rose-900/20">
                            <TrophyIcon class="h-10 w-10" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Platillo Estrella</p>
                            <p class="text-2xl font-black text-gray-800 dark:text-white tracking-tighter uppercase truncate">{{ stats.top_dishes[0]?.name || 'N/A' }}</p>
                            <p class="text-[9px] font-bold text-rose-500 uppercase mt-2">Favorito del equipo</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <!-- TOP DINERS -->
                    <div class="bg-white dark:bg-gray-800 rounded-[4rem] p-12 shadow-2xl border-2 border-gray-50">
                        <div class="flex items-center gap-6 mb-10">
                            <div class="h-14 w-14 bg-gray-900 rounded-2xl flex items-center justify-center text-white">
                                <UserGroupIcon class="h-8 w-8" />
                            </div>
                            <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Ranking de Comensales</h3>
                        </div>
                        <div class="space-y-6">
                            <div v-for="(diner, index) in stats.top_diners" :key="diner.id" 
                                 class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border-2 border-transparent hover:border-indigo-100 transition-all group">
                                <div class="flex items-center gap-6">
                                    <div class="h-12 w-12 rounded-2xl bg-white dark:bg-gray-800 flex items-center justify-center font-black text-lg shadow-md border text-gray-400 group-hover:text-indigo-600 transition-colors">
                                        #{{ index + 1 }}
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <img :src="diner.avatar_url" class="h-12 w-12 rounded-full border-2 border-white shadow-lg object-cover" />
                                        <p class="text-sm font-black text-gray-700 dark:text-gray-200 uppercase tracking-tight">{{ diner.name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-black text-indigo-600">{{ diner.orders_count }}</p>
                                    <p class="text-[8px] font-bold text-gray-400 uppercase">Pedidos</p>
                                </div>
                            </div>
                            <div v-if="stats.top_diners.length === 0" class="py-20 text-center italic text-gray-400">Sin datos registrados</div>
                        </div>
                    </div>

                    <!-- PROVIDER PREFERENCES -->
                    <div class="bg-white dark:bg-gray-800 rounded-[4rem] p-12 shadow-2xl border-2 border-gray-50">
                        <div class="flex items-center gap-6 mb-10">
                            <div class="h-14 w-14 bg-amber-600 rounded-2xl flex items-center justify-center text-white">
                                <BuildingStorefrontIcon class="h-8 w-8" />
                            </div>
                            <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Preferencia por Proveedor</h3>
                        </div>
                        <div class="space-y-6">
                            <div v-for="provider in stats.provider_preferences" :key="provider.name" 
                                 class="space-y-3">
                                <div class="flex justify-between items-end px-2">
                                    <p class="text-[11px] font-black text-gray-700 dark:text-gray-200 uppercase tracking-widest">{{ provider.name }}</p>
                                    <p class="text-sm font-black text-amber-600">{{ provider.total }} pedidos</p>
                                </div>
                                <div class="w-full h-4 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden border">
                                    <div class="h-full bg-amber-500 shadow-inner transition-all duration-1000" 
                                         :style="{ width: (provider.total / stats.total_orders * 100) + '%' }"></div>
                                </div>
                            </div>
                            <div v-if="stats.provider_preferences.length === 0" class="py-20 text-center italic text-gray-400">Sin datos registrados</div>
                        </div>
                    </div>
                </div>

                <!-- TOP DISHES FULL LIST -->
                <div class="bg-white dark:bg-gray-800 rounded-[4rem] p-12 shadow-2xl border-2 border-indigo-50">
                    <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-widest mb-10 border-b pb-6">Catálogo de Preferencias (Top Alimentos)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div v-for="dish in stats.top_dishes" :key="dish.name" 
                             class="p-6 bg-indigo-50/30 dark:bg-indigo-900/10 rounded-[2.5rem] border-2 border-indigo-100 dark:border-indigo-800 flex flex-col items-center text-center">
                            <div class="h-12 w-12 bg-white dark:bg-gray-800 rounded-2xl flex items-center justify-center shadow-lg mb-4">
                                <TrophyIcon class="h-6 w-6 text-rose-500" />
                            </div>
                            <p class="text-[10px] font-black text-gray-800 dark:text-white uppercase leading-tight mb-2 h-8 flex items-center">{{ dish.name }}</p>
                            <p class="text-3xl font-black text-indigo-600 tracking-tighter">{{ dish.total }}</p>
                            <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-1">Solicitudes</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
