<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    ChevronLeftIcon, PencilSquareIcon, CheckBadgeIcon, ClockIcon, CalendarDaysIcon,
    FunnelIcon, XMarkIcon, UserIcon, PhotoIcon, ArrowPathIcon, ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ sessions: Array, orders: Array, filters: Object, userRole: String });

const startDate = ref(props.filters.start_date), endDate = ref(props.filters.end_date);
const applyFilters = () => router.get(route('justification.index'), { start_date: startDate.value, end_date: endDate.value }, { preserveState: true, preserveScroll: true });

const processing = ref({});
const saveActivity = (id, text) => {
    processing.value[id] = true;
    router.put(route('orders.updateJustification', id), { activity_performed: text }, { preserveScroll: true, onFinish: () => processing.value[id] = false });
};

const mealTypeTagColors = { 
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300', 
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300', 
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300', 
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300' 
};
</script>

<template>
    <Head title="Justificaciones SICOA" />

    <AuthenticatedLayout bento-tag="Auditoría">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- HEADER / FILTRO -->
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                            📝
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Periodo de Justificación de Actividades</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Filtra por fechas para capturar o revisar justificaciones</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Fecha Inicio</label>
                            <input type="date" v-model="startDate" @change="applyFilters" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-gray-300 shadow-inner focus:border-tinto-700 focus:ring-tinto-700 cursor-pointer" />
                        </div>
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Fecha Fin</label>
                            <input type="date" v-model="endDate" @change="applyFilters" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-gray-300 shadow-inner focus:border-tinto-700 focus:ring-tinto-700 cursor-pointer" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="sessions?.length > 0" class="lg:col-span-12 space-y-8">
                <div v-for="session in sessions" :key="session.id" class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden">
                    <div class="p-8 bg-slate-50/50 dark:bg-gray-800/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border-b border-slate-100 dark:border-gray-800">
                        <div class="flex items-center gap-6">
                            <div class="h-16 w-16 bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-xs flex flex-col items-center justify-center shrink-0">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ new Date(session.date + 'T12:00:00').toLocaleDateString('es-ES', { month: 'short' }) }}</span>
                                <span class="text-2xl font-black text-slate-800 dark:text-white leading-none">{{ new Date(session.date + 'T12:00:00').getDate() }}</span>
                            </div>
                            <div>
                                <h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tight flex items-center gap-2">
                                    <span>👨‍🍳</span> {{ session.provider_name }}
                                </h4>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="px-2.5 py-0.5 rounded-lg border text-[9px] font-black uppercase shadow-2xs" :class="mealTypeTagColors[session.meal_type]">
                                        {{ session.meal_type }}
                                    </span>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ session.total_orders }} Pedidos registrados</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:items-end gap-2 w-full md:w-auto">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Progreso de Justificación ({{ session.justified_count }}/{{ session.total_orders }})</p>
                            <div class="w-full md:w-48 h-2.5 bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden border border-slate-200 dark:border-gray-700 shadow-inner">
                                <div class="h-full bg-gradient-to-r from-nayarit-700 to-emerald-500 transition-all duration-700 rounded-full" :style="{ width: (session.justified_count / session.total_orders * 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="area in session.areas_detail" :key="area.area_id" class="col-span-full space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="h-2 w-2 rounded-full bg-tinto-700 dark:bg-oro-400"></div>
                                <h5 class="text-[10px] font-black text-slate-600 dark:text-gray-300 uppercase tracking-widest">{{ area.area_name }}</h5>
                            </div>
                            <div v-for="order in area.orders" :key="order.id" class="bg-slate-50/60 dark:bg-gray-800/40 p-4 rounded-2xl border border-slate-200/60 dark:border-gray-800 flex flex-col md:flex-row items-center gap-6 group hover:bg-white dark:hover:bg-gray-800 transition-all">
                                <div class="flex items-center gap-4 w-full md:w-1/3">
                                    <img :src="order.avatar_url" class="h-10 w-10 rounded-xl border border-slate-200 dark:border-gray-700 shadow-2xs object-cover" />
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-slate-800 dark:text-white uppercase truncate">{{ order.user_name }}</p>
                                        <p class="text-[9px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-tight">🍽️ {{ order.platillo }}</p>
                                    </div>
                                </div>
                                <div class="flex-1 w-full relative">
                                    <textarea v-model="order.activity_performed" @blur="saveActivity(order.id, order.activity_performed)" placeholder="Escribe la labor que justifica el alimento..." rows="1" class="w-full bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl p-3 text-xs font-medium text-slate-700 dark:text-gray-200 shadow-inner focus:ring-1 focus:ring-tinto-700 focus:border-tinto-700 resize-none"></textarea>
                                    <div v-if="processing[order.id]" class="absolute right-3 top-1/2 -translate-y-1/2"><ArrowPathIcon class="h-4 w-4 text-tinto-700 dark:text-oro-400 animate-spin" /></div>
                                    <div v-else-if="order.activity_performed" class="absolute right-3 top-1/2 -translate-y-1/2"><CheckBadgeIcon class="h-4 w-4 text-emerald-600 dark:text-emerald-400" /></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="lg:col-span-12 p-20 bg-white dark:bg-gray-900 rounded-[4rem] border-2 border-dashed border-slate-200 dark:border-gray-800 text-center">
                <ClipboardDocumentCheckIcon class="h-16 w-16 text-slate-200 dark:text-gray-700 mx-auto mb-4" />
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Sin sesiones pendientes de justificar en este periodo</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
