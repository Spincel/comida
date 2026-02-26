<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import EmployeeDashboard from './Dashboard/Partials/EmployeeDashboard.vue';
import BossDashboard from './Dashboard/Partials/BossDashboard.vue';
import GuardDashboard from './Dashboard/Partials/GuardDashboard.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    myPasses: Array,
    pendingPasses: Array,
    deptHistory: Array, 
    activePasses: Array,
    recentAttendance: Array,
    allPasses: Array,
    canAuthorize: Boolean,
    usageStats: Object,
});

const page = usePage();
const visibleFlash = ref(null);
let flashTimeout = null;

watch(() => page.props.flash.message, (newMsg) => {
    if (newMsg) {
        visibleFlash.value = newMsg;
        if (flashTimeout) clearTimeout(flashTimeout);
        flashTimeout = setTimeout(() => {
            visibleFlash.value = null;
        }, 3000);
    }
}, { immediate: true });

let interval = null;
onMounted(() => {
    interval = setInterval(() => {
        if (!document.hidden) {
            router.reload({ 
                preserveScroll: true, 
                preserveState: true,
                only: ['myPasses', 'pendingPasses', 'deptHistory', 'activePasses', 'recentAttendance', 'allPasses', 'usageStats'] 
            });
        }
    }, 20000);
});
onUnmounted(() => {
    if (interval) clearInterval(interval);
});

const statusLabels = {
    pending: 'Pendiente', approved: 'Autorizado', rejected: 'Rechazado',
    in_progress: 'En Curso', completed: 'Finalizado', cancelled: 'Cancelado'
};
const statusColors = {
    pending: 'text-yellow-600 bg-yellow-100 border-yellow-200', approved: 'text-green-600 bg-green-100 border-green-200',
    rejected: 'text-red-600 bg-red-100 border-red-200', in_progress: 'text-blue-600 bg-blue-100 border-blue-200',
    completed: 'text-gray-600 bg-gray-100 border-gray-200', cancelled: 'text-gray-500 bg-gray-100 border-gray-200'
};
const typeLabels = { personal: 'Personal', health: 'Salud', commission: 'Comisión' };
const typeColors = {
    personal: 'bg-blue-50 text-blue-700 border-blue-200', health: 'bg-red-50 text-red-700 border-red-200',
    commission: 'bg-purple-50 text-purple-700 border-purple-200'
};

const calculateDuration = (start, end) => {
    if (!start || !end) return '-';
    const diff = new Date(end) - new Date(start);
    const minutes = Math.floor((diff / 1000) / 60);
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}m`;
};

const showForceCloseModal = ref(false);
const passToClose = ref(null);
const forceCloseForm = useForm({ notes: '' });

const openForceCloseModal = (pass) => {
    passToClose.value = pass;
    forceCloseForm.reset();
    showForceCloseModal.value = true;
};
const closeForceCloseModal = () => {
    showForceCloseModal.value = false;
    passToClose.value = null;
    forceCloseForm.reset();
};
const confirmForceClose = () => {
    forceCloseForm.post(route('exit-passes.force-close', passToClose.value.id), {
        preserveScroll: true,
        onSuccess: () => closeForceCloseModal(),
    });
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Panel de Control - {{ 
                    $page.props.auth.user.role === 'super_admin' ? 'SUPER ADMINISTRADOR (TI)' :
                    ($page.props.auth.user.role === 'boss' ? 'JEFE DE ÁREA' : 
                    ($page.props.auth.user.role === 'guard' ? 'VIGILANCIA' : 
                    ($page.props.auth.user.role === 'admin' ? 'RECURSOS HUMANOS' : 
                    (canAuthorize ? 'EMPLEADO (AUTORIZADOR)' : 'EMPLEADO')))) 
                }}
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="transform opacity-0 -translate-y-2"
                    enter-to-class="transform opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="transform opacity-100 translate-y-0"
                    leave-to-class="transform opacity-0 -translate-y-2"
                >
                    <div v-if="visibleFlash && $page.props.auth.user.role !== 'guard'" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm">
                        {{ visibleFlash }}
                    </div>
                </transition>
                
                <BossDashboard v-if="($page.props.auth.user.role === 'boss' || canAuthorize) && !['admin', 'super_admin'].includes($page.props.auth.user.role)" :passes="pendingPasses" :history="deptHistory" @force-close="openForceCloseModal" :usage="usageStats" />
                <GuardDashboard v-if="$page.props.auth.user.role === 'guard'" :passes="activePasses" :attendance="recentAttendance" />
                <EmployeeDashboard v-if="$page.props.auth.user.role === 'employee' && !canAuthorize" :passes="myPasses" :usage="usageStats" />
                
                <div v-if="['admin', 'super_admin'].includes($page.props.auth.user.role)" class="space-y-8">
                    <BossDashboard :passes="pendingPasses" :history="[]" @force-close="openForceCloseModal" />

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mt-8">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📖 BITÁCORA GLOBAL DE PASES (DÍA ACTUAL)</h3>
                        <div v-if="allPasses && allPasses.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div v-for="pass in allPasses.filter(p => p.user)" :key="pass.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border dark:border-gray-700 hover:shadow-md transition-shadow duration-300 group">
                                <div class="p-5 flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-lg text-gray-800 dark:text-gray-100">{{ pass.user.name }}</h4>
                                        <p class="text-sm text-gray-500">{{ pass.user.department }}</p>
                                        <p v-if="pass.sequence_number" class="text-xs text-gray-400 mt-1">
                                            <span class="font-bold">Pase #{{ pass.sequence_number }}</span> en su historial
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span :class="typeColors[pass.type] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600'" class="px-2 py-1 rounded text-xs font-bold uppercase border tracking-wider">
                                            {{ typeLabels[pass.type] ?? (pass.is_emergency ? 'EMERGENCIA' : 'N/A') }}
                                        </span>
                                        <div v-if="pass.user_monthly_usage" class="text-right text-xs mt-1 text-gray-500 font-semibold">
                                            <span class="font-mono bg-blue-100 text-blue-700 px-1 rounded">{{ pass.user_monthly_usage.personal.used }}P</span>
                                            <span class="mx-0.5">/</span>
                                            <span class="font-mono bg-red-100 text-red-700 px-1 rounded">{{ pass.user_monthly_usage.emergency.used }}E</span>
                                            <span class="ml-1">({{ new Date().toLocaleString('es-MX', { month: 'short' }) }})</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-5 pb-5 space-y-3">
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                        <span class="text-gray-500">Fecha:</span><span class="font-medium dark:text-gray-300 text-right">{{ new Date(pass.scheduled_exit_time).toLocaleDateString() }}</span>
                                        <span class="text-gray-500">Salida Real:</span><span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-right">{{ pass.real_exit_time ? new Date(pass.real_exit_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '--:--' }}</span>
                                        <span class="text-gray-500">Retorno Real:</span><span class="font-mono font-bold text-green-600 dark:text-green-400 text-right">{{ pass.real_return_time ? new Date(pass.real_return_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '--:--' }}</span>
                                    </div>
                                    <div v-if="pass.will_return" class="flex justify-between text-sm border-t dark:border-gray-700 pt-3 mt-3">
                                        <span class="text-gray-500 italic">Tiempo fuera:</span><span class="font-bold text-gray-700 dark:text-gray-300">{{ calculateDuration(pass.real_exit_time, pass.real_return_time) }}</span>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-900/50 px-5 py-3 flex justify-between items-center">
                                    <span :class="statusColors[pass.status]" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide">{{ statusLabels[pass.status] }}</span>
                                    <button v-if="pass.status === 'in_progress'" @click="openForceCloseModal(pass)" class="text-xs font-bold text-red-500 hover:underline">Cerrar Salida</button>
                                    <span v-else class="text-xs text-gray-400 flex items-center gap-1 font-bold">{{ pass.will_return ? '🔄 CON RETORNO' : '🚫 SIN RETORNO' }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center p-10 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                            <p class="text-gray-500 font-medium">No hay pases registrados en el día de hoy.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="showForceCloseModal" @close="closeForceCloseModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cierre Forzado de Pase</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Estás a punto de marcar que el empleado <strong>NO regresó</strong>. Esto cerrará el pase y cambiará su estado a "Sin Retorno".</p>
                <div class="mt-6">
                    <InputLabel for="notes" value="Observación (Opcional)" />
                    <TextInput id="notes" v-model="forceCloseForm.notes" type="text" class="mt-1 block w-full" placeholder="Ej: Se retiró a su domicilio, No regresará hoy, etc." @keyup.enter="confirmForceClose" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeForceCloseModal">Cancelar</SecondaryButton>
                    <DangerButton @click="confirmForceClose" :class="{ 'opacity-25': forceCloseForm.processing }" :disabled="forceCloseForm.processing">Confirmar Cierre</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
