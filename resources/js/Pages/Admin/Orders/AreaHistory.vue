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
</script>

<template>
    <Head title="Historial de Pedidos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('dashboard')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                </Link>
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Historial del Área
                    </h2>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest">{{ area.name }}</p>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div v-if="groupedOrders.length > 0" class="grid grid-cols-1 gap-8">
                    <div v-for="(group, gIdx) in groupedOrders" :key="gIdx" 
                         class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col transition-all hover:scale-[1.01]">
                        
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-8">
                                <div class="flex items-center space-x-4">
                                    <div class="h-14 w-14 rounded-3xl flex items-center justify-center shadow-lg transition-transform group-hover:rotate-12"
                                         :class="getProviderColor(group.provider_id).bg + ' ' + getProviderColor(group.provider_id).text">
                                        <CalendarDaysIcon class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight leading-none">
                                            {{ group.date }}
                                        </h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                                  :class="mealTypeTagColors[group.meal_type] || 'bg-indigo-50 text-indigo-500'">
                                                {{ group.meal_type }}
                                            </span>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                                                  :class="getProviderColor(group.provider_id).text">
                                                {{ group.provider_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ group.items.length }}</p>
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mt-1">Pedidos Totales</p>
                                </div>
                            </div>

                            <!-- Resumen de Platillos (Estilo Reporte) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
                                <div v-for="(count, dishName) in group.dish_counts" :key="dishName" 
                                     class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate mr-2">{{ dishName }}</span>
                                    <span class="font-black text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800 h-6 w-6 flex items-center justify-center rounded-lg shadow-sm border dark:border-gray-700">{{ count }}</span>
                                </div>
                            </div>

                            <!-- Lista de Personas -->
                            <div class="space-y-3">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 border-b dark:border-gray-700 pb-2">Detalle de Comensales:</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="order in group.items" :key="order.id" 
                                         class="flex items-center p-4 bg-white dark:bg-gray-800 border-2 border-gray-50 dark:border-gray-700 rounded-2xl shadow-sm">
                                        <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center border-2 border-white dark:border-gray-600 mr-3 shadow-sm font-black text-gray-400 text-xs uppercase">
                                            {{ order.user.name.substring(0,2) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-black text-gray-800 dark:text-gray-200 truncate">{{ order.user.name }}</p>
                                            <p class="text-[9px] font-bold text-indigo-500 uppercase truncate">{{ order.daily_menu.name }}</p>
                                            <div v-if="order.status === 'submitted_by_manager'" class="flex items-center mt-1">
                                                <CheckBadgeIcon class="h-3 w-3 text-green-500 mr-1" />
                                                <span class="text-[8px] font-black text-green-600 uppercase tracking-tighter">Confirmado</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div class="flex justify-between items-center py-4 px-2">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            Página {{ orders.current_page }} de {{ orders.last_page }}
                        </div>
                        <div class="flex gap-4">
                            <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-700">Anterior</Link>
                            <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-700">Siguiente</Link>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center p-20 bg-white dark:bg-gray-800 rounded-[3rem] shadow-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <ClockIcon class="h-16 w-16 text-gray-300 mx-auto mb-6" />
                    <p class="text-xl font-black text-gray-400 uppercase tracking-widest">No hay historial disponible</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.2);
    border-radius: 10px;
}
</style>
