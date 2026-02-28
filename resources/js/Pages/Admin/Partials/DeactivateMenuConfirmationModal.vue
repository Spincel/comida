<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    provider: Object, // The provider for which to deactivate the menu
    todayOrdersByArea: Array, // New prop to display order summary
});

const emit = defineEmits(['close', 'confirm']);

const sliderValue = ref(0);
const threshold = 95; // Percentage to reach for confirmation

const isConfirmed = computed(() => sliderValue.value >= threshold);

const areaColors = [
    'bg-blue-100 text-blue-800', 'bg-green-100 text-green-800', 'bg-yellow-100 text-yellow-800',
    'bg-indigo-100 text-indigo-800', 'bg-purple-100 text-purple-800', 'bg-pink-100 text-pink-800',
    'bg-gray-100 text-gray-800', 'bg-cyan-100 text-cyan-800'
];

const getAreaColor = (index) => {
    return areaColors[index % areaColors.length];
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        sliderValue.value = 0; // Reset slider when modal opens
    }
});

const handleConfirm = () => {
    if (isConfirmed.value) {
        emit('confirm', props.provider.id);
    }
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Confirmar Desactivación de Menú
            </h2>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Estás a punto de cerrar los pedidos para **{{ provider?.name }}** para hoy. Esta acción bloqueará la capacidad de los comensales para realizar nuevos pedidos.
            </p>

            <div v-if="todayOrdersByArea && todayOrdersByArea.length > 0" class="mb-6 p-4 border rounded-lg bg-gray-50 dark:bg-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Pedidos Registrados para Hoy:</h3>
                <div class="flex flex-wrap gap-2">
                    <span v-for="(orderSummary, index) in todayOrdersByArea" :key="orderSummary.area_id"
                          class="px-3 py-1 rounded-full text-xs font-semibold"
                          :class="getAreaColor(index)">
                        {{ orderSummary.area_name }}: {{ orderSummary.total_items }} platillos ({{ orderSummary.total_orders }} pedidos)
                    </span>
                </div>
            </div>
            <div v-else class="mb-6 p-4 border rounded-lg bg-gray-50 dark:bg-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    No se han registrado pedidos para **{{ provider?.name }}** para hoy.
                </p>
            </div>

            <div class="mb-6">
                <InputLabel value="Desliza para Confirmar" class="mb-2" />
                <div class="relative w-full bg-red-100 rounded-full h-10 flex items-center justify-start overflow-hidden">
                    <div class="absolute left-0 top-0 h-full bg-red-500 rounded-full transition-all duration-75" :style="{ width: `${sliderValue}%` }"></div>
                    <input
                        type="range"
                        min="0"
                        max="100"
                        v-model="sliderValue"
                        @mouseup="handleConfirm"
                        @touchend="handleConfirm"
                        class="absolute w-full h-full appearance-none cursor-pointer bg-transparent z-10"
                        :class="{ 'opacity-0': isConfirmed }"
                    >
                    <span class="absolute left-0 right-0 text-center text-sm font-semibold text-red-800 z-0 select-none">
                        Desliza para confirmar
                    </span>
                    <span v-if="isConfirmed" class="absolute left-0 right-0 text-center text-sm font-bold text-white z-0 select-none">
                        Confirmado
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <SecondaryButton @click="close">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton @click="handleConfirm" :disabled="!isConfirmed" :class="{ 'opacity-25': !isConfirmed }">
                    Desactivar Menú
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
/* Custom styling for the slider thumb */
input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 40px; /* Increased size */
  height: 40px; /* Increased size */
  border-radius: 50%;
  background: #dc2626; /* Red color */
  cursor: grab;
  box-shadow: 0 0 0 2px #ef4444, 0 0 0 4px #fee2e2;
  transition: background 0.15s ease-in-out;
}

input[type="range"]::-moz-range-thumb {
  width: 40px; /* Increased size */
  height: 40px; /* Increased size */
  border-radius: 50%;
  background: #dc2626; /* Red color */
  cursor: grab;
  box-shadow: 0 0 0 2px #ef4444, 0 0 0 4px #fee2e2;
  transition: background 0.15s ease-in-out;
}

input[type="range"]::-ms-thumb {
  width: 40px; /* Increased size */
  height: 40px; /* Increased size */
  border-radius: 50%;
  background: #dc2626; /* Red color */
  cursor: grab;
  box-shadow: 0 0 0 2px #ef4444, 0 0 0 4px #fee2e2;
  transition: background 0.15s ease-in-out;
}

input[type="range"]:active::-webkit-slider-thumb {
  cursor: grabbing;
}
input[type="range"]:active::-moz-range-thumb {
  cursor: grabbing;
}
input[type="range"]:active::-ms-thumb {
  cursor: grabbing;
}
</style>