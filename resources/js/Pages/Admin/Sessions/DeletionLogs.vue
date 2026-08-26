<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { TrashIcon, CalendarDaysIcon, UserIcon, InformationCircleIcon, BuildingStorefrontIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ logs: Object });

const mealTypeTagColors = { 
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300', 
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300', 
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300', 
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300' 
};
const formatDate = (d) => new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
const formatTime = (d) => new Date(d).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Bitácora de Cancelaciones SICOA" />

    <AuthenticatedLayout bento-tag="Auditoría">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- HEADER -->
            <div class="lg:col-span-12 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 border border-rose-200 dark:border-rose-800 flex items-center justify-center text-2xl shadow-xs">
                        🛡️
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Bitácora de Cancelaciones & Auditoría</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Trazabilidad de Sesiones Eliminadas</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-12">
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-gray-800/50 border-b border-slate-100 dark:border-gray-800">
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Fecha/Hora Log</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Responsable</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Sesión Eliminada</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Motivo Expresado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-rose-50/30 dark:hover:bg-rose-950/10 transition-all">
                                <td class="p-6">
                                    <p class="font-black text-sm text-slate-800 dark:text-gray-200">{{ formatDate(log.created_at) }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase flex items-center mt-1"><ClockIcon class="h-3 w-3 mr-1" /> {{ formatTime(log.created_at) }}</p>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 flex items-center justify-center font-black text-xs border border-tinto-200 dark:border-tinto-800 shadow-2xs">
                                            {{ log.user_name.charAt(0) }}
                                        </div>
                                        <span class="text-xs font-black text-slate-700 dark:text-gray-200 uppercase">{{ log.user_name }}</span>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-xs font-black text-slate-800 dark:text-white uppercase flex items-center gap-1">
                                            <span>👨‍🍳</span> {{ log.provider_name }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 rounded-lg border text-[8px] font-black uppercase shadow-2xs" :class="mealTypeTagColors[log.meal_type]">
                                                {{ log.meal_type }}
                                            </span>
                                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">{{ formatDate(log.date) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <div class="p-3 bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-2xl max-w-xs shadow-2xs">
                                        <p class="text-[10px] text-rose-700 dark:text-rose-300 font-medium italic leading-relaxed">"{{ log.reason }}"</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="logs.data.length === 0" class="p-20 text-center">
                        <TrashIcon class="h-16 w-16 text-slate-200 dark:text-gray-700 mx-auto mb-4" />
                        <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Sin registros de cancelaciones actualmente</p>
                    </div>
                </div>
                <div class="mt-8"><Pagination :links="logs.links" /></div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
