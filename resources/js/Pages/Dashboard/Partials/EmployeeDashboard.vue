<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    passes: Array,
    usage: Object,
});

const page = usePage();
const user = page.props.auth.user;

const creatingPass = ref(false);
const form = useForm({
    type: 'personal',
    reason: '',
    will_return: true,
    is_emergency: false,
});

const submit = () => {
    form.post(route('exit-passes.store'), {
        onSuccess: () => {
            creatingPass.value = false;
            form.reset();
        },
    });
};

const statusLabels = {
    pending: 'Pendiente',
    approved: 'Autorizado',
    rejected: 'Rechazado',
    in_progress: 'En Curso',
    completed: 'Finalizado',
};

const statusColors = {
    pending: 'text-yellow-600 bg-yellow-100 border-yellow-200',
    approved: 'text-green-600 bg-green-100 border-green-200',
    rejected: 'text-red-600 bg-red-100 border-red-200',
    in_progress: 'text-blue-600 bg-blue-100 border-blue-200',
    completed: 'text-gray-500 bg-gray-100 border-gray-200'
};

const zoomedQr = ref(null);
</script>

<template>
    <div class="relative">
        <!-- QR Zoom Overlay -->
        <div v-if="zoomedQr" @click="zoomedQr = null" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-90 transition-opacity">
            <div class="bg-white p-8 rounded-2xl shadow-2xl text-center animate-in zoom-in duration-300" @click.stop>
                <h3 class="text-xl font-bold mb-6 text-gray-800 uppercase tracking-widest">Escanee su Pase</h3>
                <div class="inline-block p-4 border-4 border-gray-100 rounded-xl">
                    <qrcode-vue :value="zoomedQr" :size="300" level="H" />
                </div>
                <div class="mt-6 text-2xl font-black font-mono tracking-[0.5em] text-blue-600">
                    {{ zoomedQr }}
                </div>
                <button @click="zoomedQr = null" class="mt-8 px-6 py-2 bg-gray-800 text-white rounded-full font-bold uppercase text-xs tracking-widest hover:bg-black transition">
                    Cerrar (Esc)
                </button>
            </div>
        </div>

        <!-- Header and Stats -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Mis Pases</h2>
            <div class="flex items-center gap-4">
                 <!-- Usage Stats -->
                <div v-if="usage" class="flex gap-4">
                    <div class="text-right">
                        <p class="text-xs text-gray-500 font-bold">PERSONALES (MES)</p>
                        <p class="text-lg font-black" :class="usage.personal.used >= usage.personal.limit && usage.personal.limit > 0 ? 'text-red-500' : 'text-blue-600'">
                            {{ usage.personal.used }} <span class="text-gray-400">/ {{ usage.personal.limit > 0 ? usage.personal.limit : '∞' }}</span>
                        </p>
                    </div>
                     <div class="text-right">
                        <p class="text-xs text-gray-500 font-bold">EMERGENCIAS (MES)</p>
                        <p class="text-lg font-black" :class="usage.emergency.used >= usage.emergency.limit && usage.emergency.limit > 0 ? 'text-red-500' : 'text-orange-500'">
                            {{ usage.emergency.used }} <span class="text-gray-400">/ {{ usage.emergency.limit > 0 ? usage.emergency.limit : '∞' }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passes Table -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl mb-24">
            <div class="text-gray-900 dark:text-gray-100 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-xs uppercase text-gray-500">
                            <th class="py-3 px-6">Fecha/Hora</th>
                            <th class="py-3 px-6">Motivo</th>
                            <th class="py-3 px-6">Estado</th>
                            <th class="py-3 px-6 text-center">QR de Salida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pass in passes" :key="pass.id" class="border-b dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="py-4 px-6 align-top">
                                {{ new Date(pass.created_at).toLocaleString() }}
                            </td>
                            <td class="py-4 px-6 align-top font-medium max-w-sm">
                                <p class="font-bold text-gray-800 dark:text-gray-200">{{ pass.reason }}</p>
                                <p class="text-xs text-gray-500">{{ pass.is_emergency ? 'Emergencia' : pass.type }}</p>
                            </td>
                            <td class="py-4 px-6 align-top">
                                <span :class="statusColors[pass.status]" class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide border">
                                    {{ statusLabels[pass.status] }}
                                </span>
                                <p v-if="pass.status === 'rejected' && pass.rejection_reason" class="text-xs text-red-500 mt-1 max-w-xs">{{ pass.rejection_reason }}</p>
                            </td>
                            <td class="py-4 px-6 text-center align-middle">
                                <div v-if="pass.qr_code && (pass.status === 'approved' || pass.status === 'in_progress' || (pass.is_emergency && pass.status === 'pending'))"
                                     class="bg-white p-2 inline-block rounded-lg shadow-md border border-gray-200 text-center cursor-pointer hover:shadow-xl hover:scale-105 transition-all group"
                                     @click="zoomedQr = pass.qr_code">
                                    <qrcode-vue :value="pass.qr_code" :size="60" level="H" />
                                    <p class="mt-1 text-[10px] font-bold tracking-widest text-gray-600 font-mono group-hover:text-blue-600">{{ pass.qr_code }}</p>
                                </div>
                                <div v-else class="text-xs text-gray-400 italic">
                                    {{ pass.status === 'pending' ? 'Esperando Aprobación' : '-' }}
                                </div>
                            </td>
                        </tr>
                        <tr v-if="passes.length === 0">
                            <td colspan="4" class="text-center py-10 text-gray-500 italic">No tienes pases registrados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Floating Action Button -->
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40">
             <PrimaryButton @click="creatingPass = true" class="!px-6 !py-4 !rounded-full shadow-lg bg-gradient-to-br from-pink-500 to-rose-500 text-white transform hover:scale-110 transition-transform flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                    <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
                <span class="font-bold text-sm">Nuevo Pase</span>
            </PrimaryButton>
        </div>

        <!-- Creating Pass Modal -->
        <Modal :show="creatingPass" @close="creatingPass = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Solicitar Pase de Salida</h2>
                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div class="p-4 bg-red-50 dark:bg-red-900/30 rounded-lg border border-red-100 dark:border-red-800">
                        <label class="flex items-center text-red-600 dark:text-red-400 font-bold cursor-pointer">
                            <Checkbox name="is_emergency" v-model:checked="form.is_emergency" />
                            <span class="ms-2 text-lg">🚨 ¡ES UNA EMERGENCIA!</span>
                        </label>
                         <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 ml-7" v-if="form.is_emergency">
                            Se generará un QR inmediato. Tu jefe validará los detalles después.
                        </p>
                    </div>
                    <div v-if="!form.is_emergency" class="space-y-4 transition-all duration-300">
                        <div>
                            <InputLabel for="type" value="Tipo de Permiso" />
                            <select v-model="form.type" id="type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="personal">Personal</option>
                                <option value="health">Salud / Médico</option>
                                <option value="commission">Comisión Oficial</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="reason" value="Justificación / Detalle" />
                            <TextInput id="reason" v-model="form.reason" type="text" class="mt-1 block w-full" placeholder="Describe brevemente el motivo..." required />
                        </div>
                        <div class="block">
                            <label class="flex items-center">
                                <Checkbox name="will_return" v-model:checked="form.will_return" />
                                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Regresaré a las instalaciones hoy mismo</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6 gap-2">
                        <SecondaryButton @click="creatingPass = false">Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="form.processing" :class="{ 'bg-red-600 hover:bg-red-700': form.is_emergency }">
                            {{ form.is_emergency ? 'GENERAR PASE RÁPIDO' : 'Solicitar Pase' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>