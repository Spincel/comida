<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import SwipeButton from '@/Components/SwipeButton.vue';
import { ClipboardDocumentCheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    mealType: String,
    count: Number,
});

const emit = defineEmits(['close', 'confirm']);

const isProcessing = ref(false);

const handleConfirm = () => {
    isProcessing.value = true;
    emit('confirm');
    // Success handling is done by Dashboard.vue (closes modal on onSuccess)
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        isProcessing.value = false;
    }
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="md">
        <div class="p-10 text-center relative">
            <button @click="emit('close')" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 transition-colors">
                <XMarkIcon class="h-6 w-6" />
            </button>

            <div class="flex items-center justify-center mb-8">
                <div class="h-20 w-20 bg-green-50 dark:bg-green-900/30 rounded-[2rem] flex items-center justify-center text-green-600 shadow-lg shadow-green-100 dark:shadow-none transition-transform hover:scale-110">
                    <ClipboardDocumentCheckIcon class="h-12 w-12" />
                </div>
            </div>

            <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2 uppercase tracking-tighter leading-none">
                Firmar Pedidos
            </h2>
            
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-10 px-4 leading-relaxed">
                Estás a punto de enviar <span class="font-black text-green-600 dark:text-green-400">{{ count }}</span> solicitudes de <span class="font-black text-green-600 dark:text-green-400">{{ mealType }}</span> a Adquisiciones para su consolidación final.
            </p>

            <div class="space-y-6">
                <SwipeButton 
                    text="Deslizar para enviar a cocina" 
                    activeText="¡Pedidos Enviados!" 
                    colorClass="bg-green-600"
                    @confirm="handleConfirm" 
                />
                
                <button @click="emit('close')" 
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 hover:text-gray-600 transition-colors">
                    Cancelar y revisar pedidos
                </button>
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
