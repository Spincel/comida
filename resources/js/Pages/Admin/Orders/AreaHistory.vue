<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    ChevronLeftIcon, 
    ClockIcon,
    UserGroupIcon,
    CheckBadgeIcon,
    CalendarDaysIcon,
    ClipboardDocumentListIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    orders: Object, // Paginated results
    area: Object,
});

// Group paginated data by date and meal type for the "Report style" cards
const groupedOrders = computed(() => {
    const groups = {};
    props.orders.data.forEach(order => {
        const key = `${order.daily_menu.available_on}_${order.meal_type}_${order.daily_menu.provider.name}`;
        if (!groups[key]) {
            groups[key] = {
                date: order.daily_menu.available_on,
                meal_type: order.meal_type,
                provider_id: order.daily_menu.provider_id,
                provider_name: order.daily_menu.provider.name,
                items: [],
                dish_counts: {}
            };
        }
        groups[key].items.push(order);
        
        const dishName = order.daily_menu.name;
        groups[key].dish_counts[dishName] = (groups[key].dish_counts[dishName] || 0) + 1;
    });
    return Object.values(groups).sort((a, b) => b.date.localeCompare(a.date));
});

// --- Unified Color Helpers ---
const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800',
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300 dark:border-tinto-800',
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-800',
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-800',
};

const providerColors = [
    { text: 'text-tinto-800 dark:text-oro-300', bg: 'bg-tinto-50 dark:bg-tinto-950/60' },
    { text: 'text-oro-800 dark:text-oro-300', bg: 'bg-oro-50 dark:bg-oro-950/60' },
    { text: 'text-nayarit-800 dark:text-emerald-300', bg: 'bg-emerald-50 dark:bg-emerald-950/60' },
    { text: 'text-amber-800 dark:text-amber-300', bg: 'bg-amber-50 dark:bg-amber-950/60' },
];

const getProviderColor = (id) => providerColors[id % providerColors.length];
</script>

<template>
    <Head title="Historial del Área SICOA" />

    <AuthenticatedLayout bento-tag="Bitácora">
        <div class="space-y-8">
            <!-- HEADER INFO CARD -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        🏢
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Historial de Comedor</h2>
                        <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">{{ area.name }}</p>
                    </div>
                </div>
                <Link :href="route('dashboard')" class="px-5 py-2.5 bg-slate-50 dark:bg-gray-800 text-slate-600 dark:text-gray-300 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-200 dark:border-gray-700 transition-all cursor-pointer">
                    ← Volver
                </Link>
            </div>
            
            <div v-if="groupedOrders.length > 0" class="grid grid-cols-1 gap-8">
                <div v-for="(group, gIdx) in groupedOrders" :key="gIdx" 
                     class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden flex flex-col transition-all hover:scale-[1.01]">
                    
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-8">
                            <div class="flex items-center space-x-4">
                                <div class="h-14 w-14 rounded-2xl flex items-center justify-center shadow-xs border border-slate-200/80 dark:border-gray-700"
                                     :class="getProviderColor(group.provider_id).bg + ' ' + getProviderColor(group.provider_id).text">
                                    <CalendarDaysIcon class="h-7 w-7" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight leading-none">
                                        {{ group.date }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-[9px] font-black px-2.5 py-0.5 rounded-lg border uppercase tracking-widest shadow-2xs"
                                              :class="mealTypeTagColors[group.meal_type] || 'bg-tinto-50 text-tinto-800'">
                                            {{ group.meal_type }}
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                            <span>👨‍🍳</span> {{ group.provider_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-black text-tinto-900 dark:text-oro-300 leading-none">{{ group.items.length }}</p>
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1">Pedidos Totales</p>
                            </div>
                        </div>

                        <!-- Resumen de Platillos -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
                            <div v-for="(count, dishName) in group.dish_counts" :key="dishName" 
                                 class="flex justify-between items-center p-3.5 bg-slate-50/70 dark:bg-gray-800/50 rounded-2xl border border-slate-200/60 dark:border-gray-700">
                                <span class="text-xs font-bold text-slate-700 dark:text-gray-300 truncate mr-2">{{ dishName }}</span>
                                <span class="font-black text-xs text-tinto-800 dark:text-oro-300 bg-white dark:bg-gray-900 px-2.5 py-1 rounded-lg shadow-2xs border border-slate-200 dark:border-gray-700">{{ count }}</span>
                            </div>
                        </div>

                        <!-- Lista de Personas -->
                        <div class="space-y-3">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-gray-800 pb-2">Detalle de Comensales:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="order in group.items" :key="order.id" 
                                     class="flex items-center p-4 bg-white dark:bg-gray-800/80 border border-slate-200/80 dark:border-gray-700 rounded-2xl shadow-2xs">
                                    <div class="h-10 w-10 rounded-xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 flex items-center justify-center border border-tinto-200 dark:border-tinto-800 mr-3 shadow-2xs font-black text-xs uppercase">
                                        {{ order.user?.name.substring(0,2) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-black text-slate-800 dark:text-gray-200 truncate">{{ order.user?.name }}</p>
                                        <p class="text-[9px] font-bold text-tinto-700 dark:text-oro-400 uppercase truncate">{{ order.daily_menu?.name }}</p>
                                        <div v-if="order.status === 'submitted_by_manager'" class="flex items-center mt-1">
                                            <CheckBadgeIcon class="h-3 w-3 text-nayarit-600 mr-1" />
                                            <span class="text-[8px] font-black text-nayarit-700 uppercase tracking-tighter">Confirmado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div class="flex justify-between items-center py-4 px-2">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Página {{ orders.current_page }} de {{ orders.last_page }}
                    </div>
                    <div class="flex gap-4">
                        <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="text-[10px] font-black uppercase text-tinto-800 dark:text-oro-300 hover:underline">← Anterior</Link>
                        <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="text-[10px] font-black uppercase text-tinto-800 dark:text-oro-300 hover:underline">Siguiente →</Link>
                    </div>
                </div>
            </div>

            <div v-else class="text-center p-20 bg-white dark:bg-gray-900 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-dashed border-slate-200 dark:border-gray-800">
                <ClockIcon class="h-16 w-16 text-slate-300 dark:text-gray-700 mx-auto mb-4" />
                <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No hay historial disponible para esta área</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
