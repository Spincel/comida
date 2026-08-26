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
    'bg-tinto-50 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-200 dark:border-tinto-800',
    'bg-oro-50 dark:bg-oro-950/80 text-oro-900 dark:text-oro-300 border-oro-200 dark:border-oro-800',
    'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
    'bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700'
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
        <div class="p-8 dark:bg-gray-900 rounded-[2.5rem]">
            <div class="flex items-center gap-4 mb-4">
                <div class="h-12 w-12 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-200 dark:border-rose-800">
                    <span class="text-xl">🛑</span>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">
                        Finalizar Turno de Servicio
                    </h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Cierre operativo de comedor
                    </p>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-gray-300 mb-6 font-medium leading-relaxed">
                Estás a punto de cerrar y finalizar el turno de pedidos para <strong class="text-tinto-800 dark:text-oro-300 font-bold uppercase">{{ provider?.name }}</strong>. Esta acción bloqueará la recepción de nuevos pedidos para este turno.
            </p>

            <div v-if="todayOrdersByArea && todayOrdersByArea.length > 0" class="mb-6 p-5 border border-slate-200/80 dark:border-gray-800 rounded-2xl bg-slate-50/70 dark:bg-gray-800/50">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Raciones Registradas por Área:</h3>
                <div class="flex flex-wrap gap-2">
                    <span v-for="(orderSummary, index) in todayOrdersByArea" :key="orderSummary.area_id"
                          class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase border shadow-sm"
                          :class="getAreaColor(index)">
                        {{ orderSummary.area_name }}: {{ orderSummary.total_items }} raciones
                    </span>
                </div>
            </div>
            <div v-else class="mb-6 p-5 border border-slate-200/80 dark:border-gray-800 rounded-2xl bg-slate-50/70 dark:bg-gray-800/50">
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    No se registraron pedidos en este turno para {{ provider?.name }}.
                </p>
            </div>

            <div class="mb-6">
                <InputLabel value="Desliza para Confirmar Cierre" class="mb-2 text-[10px] font-black uppercase tracking-widest text-slate-400" />
                <div class="relative w-full bg-rose-100 dark:bg-rose-950/50 rounded-2xl h-12 flex items-center justify-start overflow-hidden border border-rose-200 dark:border-rose-800">
                    <div class="absolute left-0 top-0 h-full bg-gradient-to-r from-rose-600 to-rose-500 rounded-2xl transition-all duration-75" :style="{ width: `${sliderValue}%` }"></div>
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
                    <span class="absolute left-0 right-0 text-center text-xs font-black uppercase tracking-widest text-rose-800 dark:text-rose-200 z-0 select-none">
                        Desliza para finalizar turno ➔
                    </span>
                    <span v-if="isConfirmed" class="absolute left-0 right-0 text-center text-xs font-black uppercase tracking-widest text-white z-0 select-none">
                        ✓ Turno Finalizado
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <SecondaryButton @click="close" class="rounded-xl px-5 py-3 text-[10px] font-black uppercase tracking-widest cursor-pointer">
                    Cancelar
                </SecondaryButton>
                <button @click="handleConfirm" 
                        :disabled="!isConfirmed" 
                        :class="{ 'opacity-30 cursor-not-allowed': !isConfirmed, 'cursor-pointer hover:scale-105 active:scale-95': isConfirmed }"
                        class="bg-gradient-to-r from-rose-700 to-rose-600 text-white rounded-xl px-6 py-3 text-[10px] font-black uppercase tracking-widest shadow-md transition-all">
                    Finalizar Ahora
                </button>
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