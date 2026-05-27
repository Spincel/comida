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

const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700', 'Comida': 'bg-indigo-100 text-indigo-700', 'Cena': 'bg-purple-100 text-purple-700', 'Extra': 'bg-teal-100 text-teal-700' };
</script>

<template>
    <Head title="Justificaciones V2.0" />

    <AuthenticatedLayout bento-tag="Auditoría">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 space-y-8">
                    <div class="flex items-center gap-3"><FunnelIcon class="h-6 w-6 text-indigo-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Periodo de Revisión</h3></div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Desde</label><input type="date" v-model="startDate" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black text-slate-600 dark:text-gray-300 shadow-inner" /></div>
                        <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Hasta</label><input type="date" v-model="endDate" @change="applyFilters" class="w-full px-6 py-4 bg-slate-50 dark:bg-gray-800 border-none rounded-2xl text-[11px] font-black text-slate-600 dark:text-gray-300 shadow-inner" /></div>
                    </div>
                </div>
            </div>

            <div v-if="sessions?.length > 0" class="lg:col-span-12 space-y-8">
                <div v-for="session in sessions" :key="session.id" class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-sm border border-slate-100 dark:border-gray-800 overflow-hidden">
                    <div class="p-8 bg-slate-50/50 dark:bg-gray-800/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border-b dark:border-gray-800">
                        <div class="flex items-center gap-6">
                            <div class="h-14 w-14 bg-white dark:bg-gray-800 rounded-2xl border shadow-sm flex flex-col items-center justify-center shrink-0">
                                <span class="text-[8px] font-black text-slate-400 uppercase">{{ new Date(session.date + 'T12:00:00').toLocaleDateString('es-ES', { month: 'short' }) }}</span>
                                <span class="text-xl font-black text-slate-800 dark:text-white">{{ new Date(session.date + 'T12:00:00').getDate() }}</span>
                            </div>
                            <div>
                                <h4 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tighter leading-tight">{{ session.provider_name }}</h4>
                                <div class="flex items-center gap-3 mt-2"><span class="px-2 py-0.5 rounded border text-[8px] font-black uppercase" :class="mealTypeTagColors[session.meal_type]">{{ session.meal_type }}</span><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ session.total_orders }} Pedidos registrados</p></div>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Progreso de Justificación</p>
                            <div class="w-48 h-2 bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden border dark:border-gray-700"><div class="h-full bg-emerald-500 transition-all duration-700" :style="{ width: (session.justified_count / session.total_orders * 100) + '%' }"></div></div>
                        </div>
                    </div>
                    
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="area in session.areas_detail" :key="area.area_id" class="col-span-full space-y-4">
                            <div class="flex items-center gap-2 mb-4"><div class="h-2 w-2 rounded-full bg-indigo-500"></div><h5 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ area.area_name }}</h5></div>
                            <div v-for="order in area.orders" :key="order.id" class="bg-slate-50/30 dark:bg-gray-800/30 p-4 rounded-2xl border border-slate-50 dark:border-gray-800 flex flex-col md:flex-row items-center gap-6 group hover:bg-white dark:hover:bg-gray-800 transition-all">
                                <div class="flex items-center gap-4 w-full md:w-1/3">
                                    <img :src="order.avatar_url" class="h-10 w-10 rounded-xl border-2 border-white dark:border-gray-700 shadow-sm" />
                                    <div class="min-w-0"><p class="text-xs font-black text-slate-800 dark:text-white uppercase truncate">{{ order.user_name }}</p><p class="text-[9px] font-bold text-indigo-500 uppercase tracking-tighter">{{ order.platillo }}</p></div>
                                </div>
                                <div class="flex-1 w-full relative">
                                    <textarea v-model="order.activity_performed" @blur="saveActivity(order.id, order.activity_performed)" placeholder="Justificación / Actividad realizada..." rows="1" class="w-full bg-white dark:bg-gray-900 border-none rounded-xl p-3 text-[10px] font-bold text-slate-600 dark:text-gray-300 shadow-inner focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                                    <div v-if="processing[order.id]" class="absolute right-3 top-1/2 -translate-y-1/2"><ArrowPathIcon class="h-4 w-4 text-indigo-500 animate-spin" /></div>
                                    <div v-else-if="order.activity_performed" class="absolute right-3 top-1/2 -translate-y-1/2"><CheckBadgeIcon class="h-4 w-4 text-emerald-500" /></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="lg:col-span-12 p-20 bg-white dark:bg-gray-900 rounded-[4rem] border-2 border-dashed text-center"><ClipboardDocumentCheckIcon class="h-16 w-16 text-slate-200 mx-auto mb-6" /><p class="text-slate-400 font-black uppercase tracking-widest">Sin sesiones pendientes de justificar</p></div>
        </div>
    </AuthenticatedLayout>
</template>
