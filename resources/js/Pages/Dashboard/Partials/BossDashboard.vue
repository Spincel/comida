<script setup>
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref } from 'vue';

const props = defineProps({
    passes: Array,
    history: Array,
    usage: Object,
});

const emit = defineEmits(['force-close']);

const rejectingPass = ref(null);
const validatingPass = ref(null); 
const rejectionReason = ref('');
const validationForm = ref({ type: 'personal', reason: '', will_return: true }); 

const approve = (pass) => {
    if (pass.is_emergency) {
        validatingPass.value = pass;
        validationForm.value = { type: 'personal', reason: '', will_return: true };
    } else {
        if (confirm('¿Confirma que desea autorizar este pase?')) {
            router.post(route('exit-passes.approve', pass.id));
        }
    }
};

const submitValidation = () => {
    router.post(route('exit-passes.approve', validatingPass.value.id), validationForm.value, {
        onSuccess: () => validatingPass.value = null
    });
};

const openRejectModal = (pass) => {
    rejectingPass.value = pass;
    rejectionReason.value = '';
};

const submitReject = () => {
    router.post(route('exit-passes.reject', rejectingPass.value.id), {
        reason: rejectionReason.value
    }, {
        onSuccess: () => rejectingPass.value = null
    });
};

const typeLabels = { personal: 'Personal', health: 'Salud', commission: 'Comisión' };
const statusLabels = { approved: 'Autorizado', rejected: 'Rechazado', in_progress: 'En Curso', completed: 'Finalizado' };
const statusColors = {
    approved: 'text-green-600 bg-green-100 border-green-200', rejected: 'text-red-600 bg-red-100 border-red-200',
    in_progress: 'text-blue-600 bg-blue-100 border-blue-200', completed: 'text-gray-600 bg-gray-100 border-gray-200'
};
const typeColors = {
    personal: 'bg-blue-50 text-blue-700 border-blue-200', health: 'bg-red-50 text-red-700 border-red-200',
    commission: 'bg-purple-50 text-purple-700 border-purple-200'
};
</script>

<template>
    <div class="space-y-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">🔔 SOLICITUDES PENDIENTES DE APROBACIÓN</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr class="text-xs uppercase text-gray-500">
                            <th class="py-3 px-4">Empleado</th>
                            <th class="py-3 px-4">Fecha Solicitud</th>
                            <th class="py-3 px-4">Motivo</th>
                            <th class="py-3 px-4 text-center">Uso Mensual</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pass in passes" :key="pass.id" class="border-b dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="{'bg-red-50 dark:bg-red-900/20': pass.is_emergency}">
                            <td class="py-4 px-4">
                                <p class="font-bold">{{ pass.user.name }}</p>
                                <p v-if="pass.sequence_number" class="text-xs text-gray-400">
                                    <span class="font-semibold">Pase #{{ pass.sequence_number }}</span>
                                </p>
                            </td>
                            <td class="py-4 px-4 text-gray-600 dark:text-gray-400">
                                {{ new Date(pass.scheduled_exit_time).toLocaleString() }}
                                <div v-if="pass.is_emergency" class="mt-1 text-xs font-bold text-red-600 dark:text-red-400 animate-pulse">
                                    🚨 EMERGENCIA (Requiere Validación)
                                </div>
                            </td>
                            <td class="py-4 px-4 italic max-w-xs">
                                <span class="font-semibold not-italic" :class="pass.is_emergency ? 'text-red-500' : 'text-blue-500'">[{{ typeLabels[pass.type] || 'EMERGENCIA' }}]</span>
                                {{ pass.reason }}
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div v-if="pass.user_monthly_usage" class="text-xs">
                                    <span title="Pases Personales">PER: </span>
                                    <span class="font-bold" :class="pass.user_monthly_usage.personal.used >= pass.user_monthly_usage.personal.limit && pass.user_monthly_usage.personal.limit > 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300'">
                                        {{ pass.user_monthly_usage.personal.used }}/{{ pass.user_monthly_usage.personal.limit > 0 ? pass.user_monthly_usage.personal.limit : '∞' }}
                                    </span>
                                    <span class="mx-1">|</span>
                                    <span title="Pases de Emergencia">EMG: </span>
                                    <span class="font-bold" :class="pass.user_monthly_usage.emergency.used >= pass.user_monthly_usage.emergency.limit && pass.user_monthly_usage.emergency.limit > 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300'">
                                        {{ pass.user_monthly_usage.emergency.used }}/{{ pass.user_monthly_usage.emergency.limit > 0 ? pass.user_monthly_usage.emergency.limit : '∞' }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-right flex justify-end gap-2">
                                <button @click="approve(pass)" class="px-3 py-1 text-xs font-bold text-white rounded" :class="pass.is_emergency ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600'">
                                    {{ pass.is_emergency ? 'Validar' : 'Aprobar' }}
                                </button>
                                <button @click="openRejectModal(pass)" class="px-3 py-1 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded">
                                    Rechazar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="passes.length === 0">
                            <td colspan="5" class="text-center py-6 text-gray-500 italic">No hay solicitudes pendientes en su área.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="history && history.length > 0" class="mt-8">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📖 ÚLTIMOS 10 PASES DEL DEPARTAMENTO</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div v-for="pass in history" :key="'hist-'+pass.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border dark:border-gray-700 hover:shadow-md transition-shadow duration-300 group">
                    <div class="p-5 flex justify-between items-start">
                        <div>
                            <h4 class="font-bold text-md text-gray-800 dark:text-gray-100">{{ pass.user.name }}</h4>
                            <p class="text-xs text-gray-500">{{ pass.user.employee_number }}</p>
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
                            <span class="text-gray-500">Salida:</span><span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-right">{{ pass.real_exit_time ? new Date(pass.real_exit_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '--:--' }}</span>
                            <span class="text-gray-500">Retorno:</span><span class="font-mono font-bold text-green-600 dark:text-green-400 text-right">{{ pass.real_return_time ? new Date(pass.real_return_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '--:--' }}</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-5 py-3 flex justify-between items-center">
                        <span :class="statusColors[pass.status]" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide">{{ statusLabels[pass.status] }}</span>
                         <button v-if="pass.status === 'in_progress'" @click="$emit('force-close', pass)" class="text-xs font-bold text-red-500 hover:underline">Cerrar Salida</button>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="!!rejectingPass" @close="rejectingPass = null">
             <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Motivo del Rechazo</h2>
                <div class="mt-4">
                    <InputLabel for="reason" value="Especifique la razón" />
                    <TextInput id="reason" v-model="rejectionReason" type="text" class="mt-1 block w-full" autofocus />
                </div>
                <div class="flex justify-end mt-6">
                    <SecondaryButton @click="rejectingPass = null">Cancelar</SecondaryButton>
                    <DangerButton class="ml-3" @click="submitReject">Confirmar Rechazo</DangerButton>
                </div>
            </div>
        </Modal>
        <Modal :show="!!validatingPass" @close="validatingPass = null">
             <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Validar Pase de Emergencia</h2>
                <p class="text-sm text-gray-500 mb-4">Complete los datos para regularizar este pase.</p>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Tipo de Permiso" />
                        <select v-model="validationForm.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">
                            <option value="personal">Personal</option>
                            <option value="health">Salud / Médico</option>
                            <option value="commission">Comisión Oficial</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Justificación" />
                        <TextInput v-model="validationForm.reason" type="text" class="mt-1 block w-full" />
                    </div>
                    <div class="block">
                        <label class="flex items-center">
                            <Checkbox name="will_return" v-model:checked="validationForm.will_return" />
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Regresará a las instalaciones</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <SecondaryButton @click="validatingPass = null">Cancelar</SecondaryButton>
                    <PrimaryButton class="ml-3" @click="submitValidation">Confirmar</PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
