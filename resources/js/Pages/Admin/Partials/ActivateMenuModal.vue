<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ExclamationTriangleIcon, ArrowPathIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    show: Boolean,
    provider: Object,
    areas: {
        type: Array,
        default: () => [],
    },
    allSessions: { // Contains ALL sessions for today (open and closed)
        type: Array,
        default: () => [],
    },
    initialMode: {
        type: String,
        default: 'new',
    },
    initialSession: {
        type: Object,
        default: null,
    }
});

const emit = defineEmits(['close']);

const mode = ref('new');

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    status: 'open',
    selected_area_ids: [],
    meal_type: 'Comida',
    conflict_resolution: 'restart', // Forced to restart
});

const selectAll = ref(false);
const mealOptions = ['Desayuno', 'Comida', 'Cena', 'Extra'];

// --- Slider Confirmation Logic ---
const sliderValue = ref(0);
const threshold = 95;
const isConfirmed = computed(() => sliderValue.value >= threshold);

// --- Conflict Detection Logic ---
const areasBusyWithOtherProviders = computed(() => {
    const busy = {};
    props.allSessions.forEach(session => {
        // Skip current session or same provider/meal session we are already editing
        if (session.provider_id === props.provider?.id && session.meal_type === form.meal_type) return;
        
        // Only interested in same meal type conflicts
        if (session.meal_type !== form.meal_type) return;

        const areaIds = Array.isArray(session.selected_area_ids) ? session.selected_area_ids : JSON.parse(session.selected_area_ids || '[]');
        areaIds.forEach(id => {
            busy[id] = {
                provider: session.provider?.name || 'Otro proveedor',
                status: session.status // 'open' or 'closed'
            };
        });
    });
    return busy;
});

const hasConflicts = computed(() => {
    return form.selected_area_ids.some(id => areasBusyWithOtherProviders.value[id]);
});

watch(() => props.show, (isVisible) => {
    if (isVisible) {
        form.clearErrors();
        form.date = new Date().toISOString().split('T')[0];
        mode.value = props.initialMode;
        sliderValue.value = 0;

        if (props.initialMode === 'edit' && props.initialSession) {
            form.meal_type = props.initialSession.meal_type;
            form.selected_area_ids = [...(props.initialSession.selected_area_ids || [])];
        } else {
            form.meal_type = 'Comida';
            form.selected_area_ids = [];
        }
        updateSelectAllState();
    }
});

const updateSelectAllState = () => {
    selectAll.value = form.selected_area_ids.length === props.areas?.length && props.areas?.length > 0;
};

watch(selectAll, (newVal) => {
    if (newVal && props.areas) {
        form.selected_area_ids = props.areas.map(area => area.id);
    }
});

const toggleAreaSelection = (areaId) => {
    const index = form.selected_area_ids.indexOf(areaId);
    if (index > -1) {
        form.selected_area_ids.splice(index, 1);
    } else {
        form.selected_area_ids.push(areaId);
    }
    updateSelectAllState();
};

const selectMealType = (type) => {
    if (mode.value === 'new') {
        form.meal_type = type;
    }
};

const activateMenu = () => {
    // If there are conflicts, ensure slider is confirmed
    if (hasConflicts.value && !isConfirmed.value) return;

    form.post(route('dashboard.providers.activate', props.provider.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-black text-gray-900 dark:text-gray-100 mb-4 uppercase tracking-tight">
                {{ provider?.name }} - {{ mode === 'edit' ? 'Reabrir/Editar' : 'Nueva Sesión' }}
            </h2>

            <form @submit.prevent="activateMenu">
                <div class="mb-6">
                    <InputLabel value="Tipo de Pedido (Etiqueta)" />
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                        <button v-for="opt in mealOptions" :key="opt" 
                                type="button"
                                @click="selectMealType(opt)"
                                :disabled="mode === 'edit'"
                                :class="[
                                    form.meal_type === opt ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-indigo-400',
                                    mode === 'edit' && form.meal_type !== opt ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                class="px-3 py-2 border rounded-xl text-xs font-black transition-all">
                            {{ opt }}
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.meal_type" />
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <InputLabel value="Seleccionar Áreas" />
                        <div class="flex items-center">
                            <Checkbox id="select_all_areas" v-model:checked="selectAll" />
                            <label for="select_all_areas" class="ml-2 text-[10px] font-black uppercase text-gray-400">Todas</label>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto border-2 border-gray-50 dark:border-gray-700 p-3 rounded-2xl bg-gray-50/50 dark:bg-gray-900/50">
                        <div v-for="area in areas" :key="area.id" 
                             class="flex items-center p-2 rounded-lg transition-colors"
                             :class="areasBusyWithOtherProviders[area.id] ? 'bg-orange-50 dark:bg-orange-900/10' : ''">
                            <Checkbox
                                :id="`area_${area.id}`"
                                :checked="form.selected_area_ids.includes(area.id)"
                                @change="toggleAreaSelection(area.id)"
                            />
                            <div class="ml-2 min-w-0">
                                <label :for="`area_${area.id}`" class="text-xs font-bold block truncate" :class="areasBusyWithOtherProviders[area.id] ? 'text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300'">
                                    {{ area.name }}
                                </label>
                                <span v-if="areasBusyWithOtherProviders[area.id]" class="text-[8px] font-black uppercase text-orange-400 block tracking-tighter">
                                    {{ areasBusyWithOtherProviders[area.id].status === 'closed' ? 'Ya finalizó con:' : 'Activo con:' }} {{ areasBusyWithOtherProviders[area.id].provider }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.selected_area_ids" />
                </div>

                <!-- ALERTA DE REINICIO DE PROCESO -->
                <div v-if="hasConflicts" class="mb-6 p-5 bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-200 dark:border-orange-800 rounded-2xl">
                    <div class="flex items-start">
                        <ExclamationTriangleIcon class="h-6 w-6 text-orange-500 mr-3 shrink-0" />
                        <div>
                            <p class="text-xs font-black text-orange-800 dark:text-orange-300 uppercase mb-2">¡Atención! Reinicio de Pedidos</p>
                            <p class="text-[10px] text-orange-700 dark:text-orange-400 leading-normal font-bold">
                                Has seleccionado áreas que ya tienen pedidos registrados para {{ form.meal_type }}. 
                                <br><br>
                                Al confirmar, <span class="text-red-600 dark:text-red-400 underline uppercase">se borrarán todos los pedidos anteriores</span> de estas áreas. Los comensales deberán entrar de nuevo y elegir su alimento con este nuevo proveedor.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <!-- BOTÓN DESLIZABLE SI HAY CONFLICTO -->
                    <div v-if="hasConflicts" class="mb-2 px-1">
                        <div class="relative w-full bg-gray-100 dark:bg-gray-700 rounded-2xl h-14 flex items-center justify-start overflow-hidden border-2 border-orange-200 focus-within:border-orange-500 transition-all">
                            <div class="absolute left-0 top-0 h-full bg-orange-500 rounded-xl transition-all duration-75 shadow-lg shadow-orange-200 dark:shadow-none" :style="{ width: `${sliderValue}%` }"></div>
                            <input
                                type="range"
                                min="0"
                                max="100"
                                v-model="sliderValue"
                                @mouseup="activateMenu"
                                @touchend="activateMenu"
                                class="absolute w-full h-full appearance-none cursor-grab active:cursor-grabbing bg-transparent z-10"
                                :class="{ 'opacity-0': isConfirmed }"
                            >
                            <span class="absolute left-0 right-0 text-center text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 z-0 select-none">
                                Desliza para reiniciar y activar
                            </span>
                            <span v-if="isConfirmed" class="absolute left-0 right-0 text-center text-sm font-black uppercase tracking-widest text-white z-0 select-none">
                                ¡Confirmado!
                            </span>
                        </div>
                    </div>

                    <PrimaryButton 
                        v-if="!hasConflicts"
                        @click="activateMenu"
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing" 
                        class="w-full justify-center py-4 font-black uppercase tracking-widest shadow-xl shadow-indigo-100 dark:shadow-none"
                    >
                        {{ mode === 'new' ? 'Activar Sesión' : 'Guardar y Reabrir' }}
                    </PrimaryButton>

                    <PrimaryButton 
                        v-else
                        @click="activateMenu"
                        :disabled="!isConfirmed || form.processing" 
                        class="w-full justify-center py-4 font-black uppercase tracking-widest transition-all"
                        :class="!isConfirmed ? 'opacity-20 grayscale' : 'bg-orange-600 hover:bg-orange-700 shadow-xl shadow-orange-100 dark:shadow-none'"
                    >
                        Confirmar Reinicio y Abrir
                    </PrimaryButton>

                    <SecondaryButton @click="emit('close')" type="button" class="w-full justify-center border-none shadow-none text-gray-400 uppercase font-black text-[10px] tracking-widest">
                        Cancelar
                    </SecondaryButton>
                </div>
            </form>
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
  border: 2px solid #f97316;
}

input[type="range"]::-moz-range-thumb {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: white;
  cursor: grab;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  border: 2px solid #f97316;
}
</style>
