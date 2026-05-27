<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { TrashIcon, CalendarDaysIcon, UserIcon, InformationCircleIcon, BuildingStorefrontIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ logs: Object });

const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700', 'Comida': 'bg-indigo-100 text-indigo-700', 'Cena': 'bg-purple-100 text-purple-700', 'Extra': 'bg-teal-100 text-teal-700' };
const formatDate = (d) => new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
const formatTime = (d) => new Date(d).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Auditoría V2.0" />

    <AuthenticatedLayout bento-tag="Auditoría">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-12 flex items-center gap-3 mb-4"><TrashIcon class="h-6 w-6 text-rose-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Registro de Eliminaciones</h3></div>

            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead><tr class="bg-slate-50/50 dark:bg-gray-800/50 border-b dark:border-gray-800"><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Fecha/Hora Log</th><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Responsable</th><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Sesión Eliminada</th><th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Motivo</th></tr></thead>
                        <tbody class="divide-y border-slate-50 dark:divide-gray-800">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-rose-50/10 dark:hover:bg-rose-900/5 transition-all">
                                <td class="p-6"><p class="font-black text-sm text-slate-800 dark:text-gray-200">{{ formatDate(log.created_at) }}</p><p class="text-[9px] font-bold text-slate-400 uppercase flex items-center mt-1"><ClockIcon class="h-3 w-3 mr-1" /> {{ formatTime(log.created_at) }}</p></td>
                                <td class="p-6"><div class="flex items-center gap-3"><div class="h-8 w-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-[10px] border">{{ log.user_name.charAt(0) }}</div><span class="text-xs font-black text-slate-700 dark:text-gray-300 uppercase">{{ log.user_name }}</span></div></td>
                                <td class="p-6"><div class="flex flex-col gap-1"><p class="text-xs font-black text-slate-800 dark:text-white uppercase">{{ log.provider_name }}</p><div class="flex items-center gap-2"><span class="px-2 py-0.5 rounded border text-[8px] font-black uppercase" :class="mealTypeTagColors[log.meal_type]">{{ log.meal_type }}</span><span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">{{ formatDate(log.date) }}</span></div></div></td>
                                <td class="p-6"><div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900 rounded-xl max-w-xs"><p class="text-[10px] text-rose-700 dark:text-rose-400 font-medium italic leading-relaxed">"{{ log.reason }}"</p></div></td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="logs.data.length === 0" class="p-20 text-center"><TrashIcon class="h-16 w-16 text-slate-100 mx-auto mb-6" /><p class="text-slate-400 font-black uppercase tracking-widest text-sm">Sin registros de auditoría</p></div>
                </div>
                <div class="mt-8"><Pagination :links="logs.links" /></div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
