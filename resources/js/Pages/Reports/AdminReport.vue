<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed } from 'vue';

const props = defineProps({
    passes: Array,
    departments: Array,
    filters: Object,
});

const page = usePage();
const reportLogo = computed(() => page.props.settings?.report_logo);

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    department: props.filters.department,
    search: props.filters.search || '',
});

const generateReport = () => {
    form.get(route('reports.admin'), {
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

const statusLabels = {
    approved: 'Autorizado',
    in_progress: 'En Curso',
    completed: 'Finalizado'
};

const calculateDuration = (start, end) => {
    if (!start || !end) return '-';
    const diff = new Date(end) - new Date(start);
    const minutes = Math.floor((diff / 1000) / 60);
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}m`;
};
</script>

<style>
@media print {
    @page {
        size: landscape; /* Forzar horizontal para reporte general */
        margin: 0.5cm;
    }
    
    body {
        background-color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-family: Arial, sans-serif; /* Arial es más legible en tamaños pequeños */
        font-size: 9pt; /* Base general */
    }

    /* Ocultar elementos de UI */
    aside, nav, header, footer, .no-print, button, .fixed {
        display: none !important;
    }

    /* Contenedor principal ajustado */
    main, .print-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-shadow: none !important;
        border: none !important;
        background: white !important;
    }

    /* Tabla optimizada para alta densidad */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5pt; /* Letra pequeña para que quepan las columnas */
        table-layout: auto; /* Permitir ajuste automático */
    }
    
    th, td {
        border: 1px solid #9ca3af; /* Gris más oscuro para definición */
        padding: 3px 4px; /* Padding reducido */
        text-align: left;
        vertical-align: middle;
        word-wrap: break-word;
    }

    th {
        background-color: #e5e7eb !important;
        color: black !important;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 7pt;
        text-align: center;
    }

    /* Ajuste específico para columnas */
    td.truncate {
        white-space: normal; /* Permitir salto de línea en impresión */
        max-width: 150px; /* Limitar ancho de motivo */
    }

    /* Colores */
    .bg-blue-50 { background-color: #eff6ff !important; color: black !important; }
    .bg-red-50 { background-color: #fef2f2 !important; color: black !important; }
    .bg-purple-50 { background-color: #faf5ff !important; color: black !important; }
    .bg-green-50 { background-color: #f0fdf4 !important; color: black !important; }
    
    /* Firmas al pie */
    .signatures {
        display: flex !important;
        justify-content: space-around;
        margin-top: 30px;
        page-break-inside: avoid;
    }
    
    .signature-box {
        text-align: center;
        width: 200px;
    }
    
    .signature-line {
        border-top: 1px solid black;
        margin-bottom: 4px;
    }
}

.signatures {
    display: none;
}
</style>

<template>
    <Head title="Reporte General RH" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Reporte General de Recursos Humanos
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filtros (No imprimir) -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4 md:p-6 no-print">
                    <form @submit.prevent="generateReport" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <InputLabel value="Buscar Empleado (Nombre o No.)" />
                            <TextInput v-model="form.search" type="text" class="block w-full" placeholder="Ej. Juan Pérez o EMP001" />
                        </div>
                        <div>
                            <InputLabel value="Departamento / Área" />
                            <select v-model="form.department" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="all">-- Todos --</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Fecha Inicio" />
                            <TextInput v-model="form.start_date" type="date" class="block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Fecha Fin" />
                            <TextInput v-model="form.end_date" type="date" class="block w-full" />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-5 flex justify-end gap-2 mt-2">
                            <PrimaryButton :disabled="form.processing" class="w-full sm:w-auto justify-center">Consultar</PrimaryButton>
                            <button type="button" @click="printReport" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 transition">
                                🖨️ PDF
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Reporte -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg print-container">
                    <div class="p-4 md:p-6 text-gray-900 dark:text-gray-100">
                        
                        <!-- Encabezado Impreso -->
                        <div class="text-center mb-6 hidden print:block">
                            <div v-if="reportLogo" class="flex justify-center mb-4">
                                <img :src="reportLogo" alt="Encabezado" class="h-24 w-auto object-contain" />
                            </div>
                            <h1 v-else class="text-xl font-bold uppercase">{{ $page.props.settings?.company_name || 'H. Congreso del Estado de Nayarit' }}</h1>
                            <h2 class="text-lg">Reporte de Incidencias - Pases de Salida</h2>
                            <p class="text-sm">Periodo: {{ new Date(filters.start_date).toLocaleDateString() }} al {{ new Date(filters.end_date).toLocaleDateString() }}</p>
                            <p class="text-sm font-bold mt-1" v-if="filters.department !== 'all'">Área: {{ filters.department }}</p>
                        </div>

                        <div class="overflow-x-auto -mx-4 md:mx-0">
                            <div class="inline-block min-w-full align-middle">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                            <th class="p-3 text-xs uppercase tracking-wider">Empleado</th>
                                            <th class="p-3 text-xs uppercase tracking-wider">Depto.</th>
                                            <th class="p-3 text-xs uppercase tracking-wider">Fecha</th>
                                            <th class="p-3 text-xs uppercase tracking-wider">Tipo/Emergencia</th>
                                            <th class="p-3 text-xs uppercase tracking-wider">Motivo</th>
                                            <th class="p-3 text-center text-xs uppercase tracking-wider">Salida</th>
                                            <th class="p-3 text-center text-xs uppercase tracking-wider">Retorno</th>
                                            <th class="p-3 text-center text-xs uppercase tracking-wider">Tiempo</th>
                                            <th class="p-3 text-center text-xs uppercase tracking-wider">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="pass in passes" :key="pass.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="p-3 text-sm font-medium">{{ pass.user.name }}</td>
                                            <td class="p-3 text-xs text-gray-500">{{ pass.user.department }}</td>
                                            <td class="p-3 text-xs whitespace-nowrap">{{ new Date(pass.scheduled_exit_time).toLocaleDateString() }}</td>
                                            <td class="p-3">
                                                <div class="flex flex-col gap-1">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-bold border w-max"
                                                        :class="{
                                                            'bg-blue-50 text-blue-700 border-blue-200': pass.type === 'personal',
                                                            'bg-red-50 text-red-700 border-red-200': pass.type === 'health',
                                                            'bg-purple-50 text-purple-700 border-purple-200': pass.type === 'commission'
                                                        }">
                                                        {{ typeLabels[pass.type] ?? 'Sin Tipo' }}
                                                    </span>
                                                    <span v-if="pass.is_emergency" class="px-2 py-0.5 rounded-full text-[9px] uppercase border bg-red-600 text-white border-red-700 font-bold w-max animate-pulse">
                                                        🚨 Emergencia
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="p-3 text-xs max-w-[120px] break-words">{{ pass.reason }}</td>
                                            <td class="p-3 text-center font-mono text-xs text-blue-600">{{ pass.real_exit_time ? new Date(pass.real_exit_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-' }}</td>
                                            <td class="p-3 text-center font-mono text-xs text-green-600">{{ pass.real_return_time ? new Date(pass.real_return_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-' }}</td>
                                            <td class="p-3 text-center font-bold text-xs">{{ pass.will_return ? calculateDuration(pass.real_exit_time, pass.real_return_time) : 'N/A' }}</td>
                                            <td class="p-3 text-center text-[10px] uppercase font-bold text-gray-500">
                                                {{ statusLabels[pass.status] }}
                                                <div v-if="pass.is_emergency" class="mt-1">
                                                    <span v-if="pass.approver_id" class="text-green-600 border border-green-200 bg-green-50 px-1 rounded">✅ Val.</span>
                                                    <span v-else class="text-red-600 border border-red-200 bg-red-50 px-1 rounded">⚠️ Pend.</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="passes.length === 0">
                                            <td colspan="9" class="p-6 text-center text-sm text-gray-500 italic">No se encontraron incidencias con los filtros seleccionados.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sección de Firmas (Solo Impresión) -->
                        <div class="signatures">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <p class="font-bold">Elaboró</p>
                                <p class="text-xs">Recursos Humanos</p>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <p class="font-bold">Autorizó</p>
                                <p class="text-xs">Dirección Administrativa</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
