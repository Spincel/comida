<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { 
    TrashIcon, 
    CalendarDaysIcon, 
    UserIcon, 
    InformationCircleIcon,
    ArrowLeftIcon,
    BuildingStorefrontIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Object
});

const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200',
    'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200',
    'Cena': 'bg-purple-100 text-purple-700 border-purple-200',
    'Extra': 'bg-teal-100 text-teal-700 border-teal-200'
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Auditoría de Sesiones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <Link :href="route('dashboard')" class="p-3 bg-white dark:bg-gray-800 rounded-2xl border shadow-sm hover:bg-gray-50 transition-all">
                        <ArrowLeftIcon class="h-6 w-6 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 uppercase tracking-tighter leading-none">Auditoría de Sesiones</h2>
                        <p class="text-[9px] font-black text-rose-500 uppercase tracking-[0.2em] mt-2 leading-none">Registro de Eliminaciones y Cancelaciones</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-[85%] mx-auto">
                <div class="bg-white dark:bg-gray-800 rounded-[3.5rem] border shadow-2xl overflow-hidden">
                    <div class="p-10 border-b dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-rose-600 rounded-2xl flex items-center justify-center shadow-lg shadow-rose-900/20">
                                <TrashIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-tight">Historial de Eliminaciones</h3>
                                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Total de registros: {{ logs.total }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/30 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 border-b dark:border-gray-700">
                                    <th class="px-8 py-6">Fecha/Hora Log</th>
                                    <th class="px-8 py-6">Usuario</th>
                                    <th class="px-8 py-6">Sesión Afectada</th>
                                    <th class="px-8 py-6">Motivo de Eliminación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-colors">
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-gray-800 dark:text-gray-200">{{ formatDate(log.created_at) }}</span>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center mt-1">
                                                <ClockIcon class="h-3 w-3 mr-1.5" /> {{ formatTime(log.created_at) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-xs border border-indigo-200 dark:border-indigo-800">
                                                {{ log.user_name.charAt(0) }}
                                            </div>
                                            <span class="text-sm font-black text-gray-800 dark:text-gray-200">{{ log.user_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <BuildingStorefrontIcon class="h-4 w-4 text-gray-400" />
                                                <span class="text-sm font-black text-gray-800 dark:text-gray-200">{{ log.provider_name }}</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="px-3 py-1 rounded-lg border text-[9px] font-black uppercase tracking-widest" :class="mealTypeTagColors[log.meal_type]">
                                                    {{ log.meal_type }}
                                                </span>
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center">
                                                    <CalendarDaysIcon class="h-3 w-3 mr-1.5" /> {{ formatDate(log.date) }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 max-w-md">
                                        <div class="p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-800 rounded-2xl flex gap-3">
                                            <InformationCircleIcon class="h-5 w-5 text-rose-500 shrink-0" />
                                            <p class="text-xs text-rose-700 dark:text-rose-400 font-medium leading-relaxed italic">
                                                "{{ log.reason }}"
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="4" class="px-8 py-20 text-center">
                                        <TrashIcon class="h-16 w-16 text-gray-100 dark:text-gray-800 mx-auto mb-6" />
                                        <p class="text-gray-400 font-black uppercase tracking-[0.3em] text-sm">No hay registros de auditoría</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-10 bg-gray-50/30 dark:bg-gray-900/30">
                        <Pagination :links="logs.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
