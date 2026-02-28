<script setup>
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { TrashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    session: Object, // { id, meal_type, provider_name }
});

const emit = defineEmits(['close']);

const sliderValue = ref(0);
const threshold = 95;
const isConfirmed = computed(() => sliderValue.value >= threshold);

const form = useForm({
    reason: '',
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        sliderValue.value = 0;
        form.reason = '';
        form.clearErrors();
    }
});

const handleDelete = () => {
    if (!isConfirmed.value) return;
    
    form.delete(route('dashboard.sessions.destroy', props.session.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="md">
        <div class="p-8">
            <div class="flex items-center justify-center mb-6">
                <div class="h-16 w-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center text-red-600">
                    <TrashIcon class="h-10 w-10" />
                </div>
            </div>

            <h2 class="text-2xl font-black text-center text-gray-900 dark:text-gray-100 mb-2 uppercase tracking-tight">
                Eliminar Sesión
            </h2>
            
            <p class="text-center text-gray-500 dark:text-gray-400 mb-6 px-4">
                ¿Estás seguro de eliminar la sesión de <span class="font-black text-red-600">{{ session?.meal_type }}</span> de <span class="font-black text-red-600">{{ session?.provider_name }}</span>?
            </p>

            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-2xl border border-red-100 dark:border-red-800 mb-6">
                <div class="flex items-start">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-500 mr-2 shrink-0" />
                    <p class="text-[10px] text-red-700 dark:text-red-400 font-bold leading-tight">
                        ADVERTENCIA: Esta acción eliminará permanentemente la sesión y TODOS los pedidos asociados a ella. No se puede deshacer.
                    </p>
                </div>
            </div>

            <div class="mb-6">
                <InputLabel for="reason" value="Motivo de la eliminación (Obligatorio)" class="text-[10px] font-black uppercase text-gray-400 mb-2" />
                <textarea
                    id="reason"
                    v-model="form.reason"
                    rows="2"
                    required
                    class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                    placeholder="Ej: Cambio de proveedor de última hora, error en la captura..."
                ></textarea>
                <InputError class="mt-2" :message="form.errors.reason" />
            </div>

            <div class="mb-8 px-2">
                <div class="relative w-full bg-gray-100 dark:bg-gray-700 rounded-2xl h-14 flex items-center justify-start overflow-hidden border-2 border-transparent focus-within:border-red-500 transition-all">
                    <div class="absolute left-0 top-0 h-full bg-red-500 rounded-xl transition-all duration-75 shadow-lg shadow-red-200 dark:shadow-none" :style="{ width: `${sliderValue}%` }"></div>
                    <input
                        type="range"
                        min="0"
                        max="100"
                        v-model="sliderValue"
                        @mouseup="handleDelete"
                        @touchend="handleDelete"
                        class="absolute w-full h-full appearance-none cursor-grab active:cursor-grabbing bg-transparent z-10"
                        :class="{ 'opacity-0': isConfirmed }"
                    >
                    <span class="absolute left-0 right-0 text-center text-xs font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 z-0 select-none">
                        Desliza para eliminar
                    </span>
                    <span v-if="isConfirmed" class="absolute left-0 right-0 text-center text-sm font-black uppercase tracking-widest text-white z-0 select-none">
                        ¡Confirmado!
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <PrimaryButton 
                    @click="handleDelete" 
                    :disabled="!isConfirmed || form.processing" 
                    class="w-full justify-center py-4 text-base font-black uppercase tracking-widest transition-all"
                    :class="!isConfirmed ? 'opacity-20 grayscale' : 'bg-red-600 hover:bg-red-700 shadow-xl shadow-red-100'"
                >
                    {{ form.processing ? 'Eliminando...' : 'Eliminar Permanentemente' }}
                </PrimaryButton>
                
                <SecondaryButton @click="emit('close')" class="w-full justify-center py-3 border-none shadow-none text-gray-400 hover:text-gray-600 font-bold uppercase text-[10px] tracking-widest">
                    Cancelar
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: white;
  cursor: grab;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  border: 2px solid #ef4444;
}

input[type="range"]::-moz-range-thumb {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: white;
  cursor: grab;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  border: 2px solid #ef4444;
}
</style>
