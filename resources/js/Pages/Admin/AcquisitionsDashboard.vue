<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    providers: Array,
});

const getWeeklyOrdersText = (count) => {
    if (count === 0) return 'Sin pedidos esta semana';
    if (count === 1) return '1 día con pedidos esta semana';
    return `${count} días con pedidos esta semana`;
};

const getMonthlyOrdersText = (count) => {
    if (count === 0) return 'Sin pedidos este mes';
    if (count === 1) return '1 pedido este mes';
    return `${count} pedidos este mes`;
};

const getProviderDailyStatusText = (dailyStatus) => {
    if (!dailyStatus || dailyStatus.status === 'closed') {
        return 'Pedidos Cerrados para Hoy';
    }
    return 'Pedidos Abiertos para Hoy';
};

const getProviderDailyStatusClass = (dailyStatus) => {
    if (!dailyStatus || dailyStatus.status === 'closed') {
        return 'bg-red-100 text-red-800';
    }
    return 'bg-green-100 text-green-800';
};
</script>

<template>
    <Head title="Panel de Adquisiciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Panel de Adquisiciones
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">Gestión de Proveedores y Menús Diarios</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="provider in providers" :key="provider.id" class="bg-gray-50 dark:bg-gray-900/50 rounded-lg shadow-md border dark:border-gray-700 hover:shadow-lg transition-shadow duration-300">
                            <div class="p-5">
                                <h4 class="font-bold text-xl text-indigo-600 dark:text-indigo-400 mb-2">{{ provider.name }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    <span class="font-semibold">Contacto:</span> {{ provider.contact_person || 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    <span class="font-semibold">Entrega:</span> {{ provider.delivery_time_window || 'N/A' }}
                                </p>

                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <h5 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Estadísticas Recientes:</h5>
                                    <p class="text-xs text-gray-500 mb-1">📅 {{ getWeeklyOrdersText(provider.weekly_orders) }}</p>
                                    <p class="text-xs text-gray-500 mb-3">📦 {{ getMonthlyOrdersText(provider.monthly_orders_count) }}</p>

                                    <div class="flex items-center text-sm font-semibold mb-4">
                                        <span class="mr-2 text-gray-700 dark:text-gray-300">Estado Hoy:</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs" :class="getProviderDailyStatusClass(provider.dailyStatus)">
                                            {{ getProviderDailyStatusText(provider.dailyStatus) }}
                                        </span>
                                    </div>

                                    <div class="flex flex-col space-y-2 mt-4">
                                        <PrimaryButton class="justify-center">
                                            Activar Menú para Hoy
                                        </PrimaryButton>
                                        <Link :href="route('providers.edit', provider.id)" class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            Revisar Catálogo
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="providers.length === 0" class="text-center p-10 bg-white dark:bg-gray-800 rounded-lg shadow-inner">
                        <p class="text-lg text-gray-500 font-medium">No hay proveedores registrados todavía.</p>
                        <p class="text-gray-400 text-sm mt-2">¡Comienza añadiendo tu primer proveedor!</p>
                        <Link :href="route('providers.create')" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Añadir Proveedor
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>