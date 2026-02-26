<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed } from 'vue';

const props = defineProps({
    passes: Array,
    filters: Object,
    user: Object,
});

const page = usePage();
const reportLogo = computed(() => page.props.settings?.report_logo);

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const generateReport = () => {
    form.get(route('reports.employee'), {
        preserveState: true,
        replace: true,
    });
};

const printReport = () => {
    window.print();
};

const typeLabels = {
    personal: 'Personal',
    health: 'Salud',
    commission: 'Comisión'
};
</script>

<style>
@media print {
    @page {
        size: letter portrait;
        margin: 1cm;
    }
    
    body {
        background-color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-family: Arial, sans-serif;
    }

    aside, nav, header, footer, .no-print, button, .fixed {
        display: none !important;
    }

    main, .print-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        box-shadow: none !important;
        border: none !important;
        background: white !important;
    }

    /* Tabla */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 10pt;
    }
    
    th, td {
        border: 1px solid #9ca3af;
        padding: 6px 8px;
        text-align: left;
    }

    th {
        background-color: #e5e7eb !important;
        color: black !important;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 9pt;
    }

    /* Firmas */
    .signature-section {
        display: flex !important;
        justify-content: center;
        margin-top: 2cm;
        page-break-inside: avoid;
    }
    
    .signature-box {
        text-align: center;
        width: 250px;
    }
    
    .signature-line {
        border-top: 1px solid black;
        margin-bottom: 5px;
    }
}

.signature-section {
    display: none;
}
</style>

<template>
    <Head title="Reporte de Pases" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Reporte de Historial de Pases
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filtros (No imprimir) -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 no-print">
                    <form @submit.prevent="generateReport" class="flex flex-col md:flex-row gap-4 items-end">
                        <div>
                            <InputLabel value="Fecha Inicio" />
                            <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Fecha Fin" />
                            <TextInput v-model="form.end_date" type="date" class="mt-1 block w-full" />
                        </div>
                        <div class="flex gap-2">
                            <PrimaryButton :disabled="form.processing">Filtrar</PrimaryButton>
                            <button type="button" @click="printReport" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                🖨️ Imprimir
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Contenido del Reporte -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg print-container">
                    <div class="p-8 text-gray-900 dark:text-gray-100">
                        
                        <!-- Encabezado del Reporte -->
                        <div class="text-center mb-8 border-b pb-4">
                            <div v-if="reportLogo" class="flex justify-center mb-4">
                                <img :src="reportLogo" alt="Encabezado" class="h-24 w-auto object-contain" />
                            </div>
                            <h1 v-else class="text-2xl font-bold uppercase tracking-wider">{{ $page.props.settings?.company_name || 'H. Congreso del Estado de Nayarit' }}</h1>
                            <h2 class="text-lg mt-2">Reporte de Pases de Salida</h2>
                            <p class="text-sm text-gray-500 mt-1">
                                Del {{ new Date(filters.start_date).toLocaleDateString() }} al {{ new Date(filters.end_date).toLocaleDateString() }}
                            </p>
                        </div>

                        <!-- Datos del Empleado -->
                        <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-bold">Nombre:</span> {{ user.name }}
                            </div>
                            <div>
                                <span class="font-bold">No. Empleado:</span> {{ user.employee_number }}
                            </div>
                            <div>
                                <span class="font-bold">Departamento:</span> {{ user.department }}
                            </div>
                            <div>
                                <span class="font-bold">Fecha de Impresión:</span> {{ new Date().toLocaleDateString() }}
                            </div>
                        </div>

                        <!-- Tabla de Pases -->
                        <table class="w-full text-left text-sm border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    <th class="border border-gray-300 px-3 py-2">Fecha</th>
                                    <th class="border border-gray-300 px-3 py-2">Tipo</th>
                                    <th class="border border-gray-300 px-3 py-2">Motivo</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center">Salida</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center">Retorno</th>
                                </tr>
                            </thead>
                                                            <tbody>
                                                            <tr v-for="pass in passes" :key="pass.id">
                                                                <td class="border border-gray-300 px-3 py-2">{{ new Date(pass.scheduled_exit_time).toLocaleDateString() }}</td>
                                                                <td class="border border-gray-300 px-3 py-2">
                                                                    {{ typeLabels[pass.type] ?? 'Sin Tipo' }}
                                                                    <div v-if="pass.is_emergency" class="mt-1">
                                                                        <span class="px-1 py-0.5 rounded text-[10px] uppercase border bg-red-600 text-white border-red-700 font-bold">
                                                                            🚨 Emergencia
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td class="border border-gray-300 px-3 py-2">{{ pass.reason }}</td>                                    <td class="border border-gray-300 px-3 py-2 text-center font-mono">
                                        {{ pass.real_exit_time ? new Date(pass.real_exit_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-' }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2 text-center font-mono">
                                        {{ pass.real_return_time ? new Date(pass.real_return_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : (pass.will_return ? 'Pendiente' : 'N/A') }}
                                    </td>
                                </tr>
                                <tr v-if="passes.length === 0">
                                    <td colspan="5" class="border border-gray-300 px-3 py-6 text-center italic text-gray-500">
                                        No se encontraron registros en el rango de fechas seleccionado.
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Sección de Firmas -->
                        <div class="signature-section">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <p class="font-bold uppercase">{{ user.name }}</p>
                                <p class="text-xs uppercase">Firma de Conformidad</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
