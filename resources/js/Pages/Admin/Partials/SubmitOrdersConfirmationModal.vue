<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    mealType: String,
    count: Number,
});

const emit = defineEmits(['close', 'confirm']);

const sliderValue = ref(0);
const threshold = 95;

const isConfirmed = computed(() => sliderValue.value >= threshold);

watch(() => props.show, (newVal) => {
    if (newVal) {
        sliderValue.value = 0;
    }
});

const handleConfirm = () => {
    if (isConfirmed.value) {
        emit('confirm');
    }
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="md">
        <div class="p-8">
            <div class="flex items-center justify-center mb-6">
                <div class="h-16 w-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600">
                    <ClipboardDocumentCheckIcon class="h-10 w-10" />
                </div>
            </div>

            <h2 class="text-2xl font-black text-center text-gray-900 dark:text-gray-100 mb-2 uppercase tracking-tight">
                Enviar Pedidos
            </h2>
            
            <p class="text-center text-gray-500 dark:text-gray-400 mb-8 px-4">
                Estás a punto de enviar <span class="font-black text-indigo-600 dark:text-indigo-400">{{ count }}</span> pedidos de <span class="font-black text-indigo-600 dark:text-indigo-400">{{ mealType }}</span> a Adquisiciones.
            </p>

            <div class="mb-8 px-2">
                <div class="relative w-full bg-gray-100 dark:bg-gray-700 rounded-2xl h-14 flex items-center justify-start overflow-hidden border-2 border-transparent focus-within:border-indigo-500 transition-all">
                    <div class="absolute left-0 top-0 h-full bg-green-500 rounded-xl transition-all duration-75 shadow-lg shadow-green-200 dark:shadow-none" :style="{ width: `${sliderValue}%` }"></div>
                    <input
                        type="range"
                        min="0"
                        max="100"
                        v-model="sliderValue"
                        @mouseup="handleConfirm"
                        @touchend="handleConfirm"
                        class="absolute w-full h-full appearance-none cursor-grab active:cursor-grabbing bg-transparent z-10"
                        :class="{ 'opacity-0': isConfirmed }"
                    >
                    <span class="absolute left-0 right-0 text-center text-xs font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 z-0 select-none">
                        Desliza para enviar
                    </span>
                    <span v-if="isConfirmed" class="absolute left-0 right-0 text-center text-sm font-black uppercase tracking-widest text-white z-0 select-none">
                        ¡Listo para enviar!
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <PrimaryButton 
                    @click="handleConfirm" 
                    :disabled="!isConfirmed" 
                    class="w-full justify-center py-4 text-base font-black uppercase tracking-widest transition-all"
                    :class="!isConfirmed ? 'opacity-20 grayscale' : 'bg-green-600 hover:bg-green-700 shadow-xl shadow-green-100'"
                >
                    Confirmar Envío
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
  border: 2px solid #10b981;
}

input[type="range"]::-moz-range-thumb {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: white;
  cursor: grab;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  border: 2px solid #10b981;
}
</style>
