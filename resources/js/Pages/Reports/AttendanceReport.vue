<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed, onMounted, onUnmounted } from 'vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    reportData: Array,
    departments: Array,
    filters: Object,
    dashboard: Object, // { stats: { on_time, late }, recentLogs: [], date: string }
});

const page = usePage();
const reportLogo = computed(() => page.props.settings?.report_logo);

// Chart Configuration
const chartData = computed(() => ({
    labels: ['Puntual', 'Retardo'],
    datasets: [{
        backgroundColor: ['#22c55e', '#eab308'],
        data: [props.dashboard?.stats.on_time || 0, props.dashboard?.stats.late || 0]
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' }
    }
};

// Polling for Real-time Dashboard
let pollInterval = null;

onMounted(() => {
    // Refresh dashboard data every 10 seconds
    pollInterval = setInterval(() => {
        router.reload({ only: ['dashboard'], preserveScroll: true, preserveState: true });
    }, 10000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    department: props.filters.department || 'all',
    search: props.filters.search || '',
    status: props.filters.status || 'all',
});

const generateReport = () => {
    form.get(route('reports.attendance'), {
        preserveState: true,
        replace: true,
    });
};

const printReport = () => {
    window.print();
};

const statusLabels = {
    ok: 'A tiempo',
    late: 'Retardo',
    missing_exit: 'Sin Salida',
    missing_entry: 'Sin Entrada',
    absent: 'Ausente'
};

const statusClasses = {
    ok: 'bg-green-100 text-green-800 border-green-200',
    late: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    missing_exit: 'bg-orange-100 text-orange-800 border-orange-200',
    missing_entry: 'bg-orange-100 text-orange-800 border-orange-200',
    absent: 'bg-red-100 text-red-800 border-red-200'
};
</script>

<style>
@media print {
    @page { size: landscape; margin: 0.5cm; }
    body { background-color: white !important; -webkit-print-color-adjust: exact; font-family: sans-serif; font-size: 9pt; }
    nav, aside, header, footer, .no-print, button { display: none !important; }
    main, .print-container { width: 100% !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important; background: white !important; }
    table { width: 100%; border-collapse: collapse; font-size: 8pt; }
    th, td { border: 1px solid #9ca3af; padding: 4px; text-align: left; }
    th { background-color: #e5e7eb !important; color: black !important; font-weight: bold; text-transform: uppercase; }
    .bg-green-100 { background-color: #dcfce7 !important; color: #166534 !important; }
    .bg-yellow-100 { background-color: #fef9c3 !important; color: #854d0e !important; }
    .bg-orange-100 { background-color: #ffedd5 !important; color: #9a3412 !important; }
    .bg-red-100 { background-color: #fee2e2 !important; color: #991b1b !important; }
}
</style>

<template>
    <Head title="Reporte de Asistencia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Reporte de Asistencia y Puntualidad
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- DASHBOARD (Real-time) -->
                <div v-if="dashboard" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 no-print">
                    <!-- Graph -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm sm:rounded-lg flex flex-col items-center justify-center relative overflow-hidden">
                        <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">Puntualidad - Hoy</h3>
                        <div class="h-64 w-full relative z-10">
                            <Doughnut :data="chartData" :options="chartOptions" />
                        </div>
                        <div v-if="!dashboard.stats.on_time && !dashboard.stats.late" class="absolute inset-0 flex items-center justify-center text-gray-400 z-0">
                            Sin datos hoy
                        </div>
                    </div>

                    <!-- Live Feed -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm sm:rounded-lg flex flex-col h-96">
                        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
                             <h3 class="text-lg font-bold flex items-center gap-2 text-gray-800 dark:text-gray-200">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                                Actividad en Tiempo Real
                             </h3>
                             <span class="text-xs text-gray-500">{{ dashboard.date }}</span>
                        </div>
                        <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                             <div v-for="log in dashboard.recentLogs" :key="log.id" class="flex items-center p-3 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 transition-colors">
                                  <!-- Avatar -->
                                  <div class="flex-shrink-0 mr-3">
                                      <img v-if="log.user_avatar" :src="log.user_avatar" class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                                      <div v-else class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold border border-indigo-200">
                                          {{ log.user_name.charAt(0) }}
                                      </div>
                                  </div>
                                  <!-- Info -->
                                  <div class="flex-1 min-w-0">
                                      <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">{{ log.user_name }}</p>
                                      <p class="text-xs text-gray-500 truncate">{{ log.department }}</p>
                                      <div class="flex items-center gap-2 mt-1">
                                          <p class="text-[10px] text-gray-400">{{ log.ago }}</p>
                                          <span v-if="log.type === 'exit' && log.duration" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                              ⏱ {{ log.duration }}
                                          </span>
                                      </div>
                                  </div>
                                  <!-- Status/Time -->
                                  <div class="text-right">
                                      <div class="text-sm font-bold px-2 py-0.5 rounded-full inline-block" :class="log.type === 'entry' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
                                          {{ log.type === 'entry' ? 'ENTRADA' : 'SALIDA' }}
                                      </div>
                                      <div class="text-xs text-gray-600 dark:text-gray-400 font-mono mt-1">{{ log.time }}</div>
                                      <div v-if="log.type === 'entry' && log.status === 'late'" class="mt-1">
                                          <span class="text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">RETARDO</span>
                                      </div>
                                  </div>
                             </div>
                             
                             <div v-if="dashboard.recentLogs.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400">
                                 <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                 <p>Esperando registros...</p>
                             </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4 md:p-6 no-print">
                    <form @submit.prevent="generateReport" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <InputLabel value="Buscar Empleado" />
                            <TextInput v-model="form.search" type="text" class="block w-full" placeholder="Nombre o No. Empleado" />
                        </div>
                        <div>
                            <InputLabel value="Departamento" />
                            <select v-model="form.department" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                <option value="all">-- Todos --</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Estado" />
                            <select v-model="form.status" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                <option value="all">-- Todos --</option>
                                <option value="late">Retardos</option>
                                <option value="missing_exit">Sin Salida</option>
                                <option value="absent">Ausencias</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Inicio" />
                            <TextInput v-model="form.start_date" type="date" class="block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Fin" />
                            <TextInput v-model="form.end_date" type="date" class="block w-full" />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-6 flex justify-end gap-2 mt-2">
                            <PrimaryButton :disabled="form.processing">Consultar</PrimaryButton>
                            <button type="button" @click="printReport" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md text-xs font-bold uppercase tracking-widest hover:bg-gray-700">
                                🖨️ PDF
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabla Reporte -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg print-container">
                    <div class="p-6">
                        <!-- Encabezado Impresión -->
                        <div class="text-center mb-6 hidden print:block">
                            <div v-if="reportLogo" class="flex justify-center mb-4"><img :src="reportLogo" class="h-24 w-auto object-contain" /></div>
                            <h1 class="text-xl font-bold uppercase">{{ $page.props.settings?.company_name }}</h1>
                            <h2 class="text-lg">Reporte de Asistencia</h2>
                            <p class="text-sm">Del {{ new Date(filters.start_date).toLocaleDateString() }} al {{ new Date(filters.end_date).toLocaleDateString() }}</p>
                        </div>

                        <div class="overflow-x-auto -mx-4 md:mx-0">
                            <div class="inline-block min-w-full align-middle">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead>
                                        <tr class="bg-gray-100 dark:bg-gray-700">
                                            <th class="p-3 text-left">Empleado</th>
                                            <th class="p-3 text-left">Fecha</th>
                                            <th class="p-3 text-center">Entrada</th>
                                            <th class="p-3 text-center">Salida</th>
                                            <th class="p-3 text-center">Estado</th>
                                            <th class="p-3 text-left">Incidencias / Pases</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="(row, index) in reportData" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="p-3">
                                                <div class="font-bold">{{ row.user.name }}</div>
                                                <div class="text-xs text-gray-500">{{ row.user.department }}</div>
                                            </td>
                                            <td class="p-3 whitespace-nowrap">{{ new Date(row.date + 'T00:00:00').toLocaleDateString() }}</td>
                                            <td class="p-3 text-center font-mono text-blue-600 font-bold">{{ row.entry_time || '--:--' }}</td>
                                            <td class="p-3 text-center font-mono text-blue-600 font-bold">{{ row.exit_time || '--:--' }}</td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-1 rounded text-xs font-bold uppercase border" :class="statusClasses[row.status]">
                                                    {{ statusLabels[row.status] }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-xs">
                                                <div v-if="row.passes.length > 0" class="flex flex-col gap-1">
                                                    <div v-for="(pass, pi) in row.passes" :key="pi" class="bg-purple-50 text-purple-700 border border-purple-200 p-1 rounded">
                                                        <span class="font-bold">Pase Salida:</span> 
                                                        {{ pass.time_out ? new Date(pass.time_out).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : 'Pend' }} - 
                                                        {{ pass.time_in ? new Date(pass.time_in).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : (pass.will_return ? '...' : 'No Regresa') }}
                                                        <span v-if="pass.is_emergency" class="text-red-600 font-bold ml-1">(EMG)</span>
                                                    </div>
                                                </div>
                                                <span v-else class="text-gray-400 italic">-</span>
                                            </td>
                                        </tr>
                                        <tr v-if="reportData.length === 0">
                                            <td colspan="6" class="p-6 text-center text-gray-500">No se encontraron registros.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>