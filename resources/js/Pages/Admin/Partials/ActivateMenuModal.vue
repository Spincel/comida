<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ExclamationTriangleIcon, ArrowPathIcon, ClockIcon, XMarkIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    show: Boolean,
    provider: Object,
    areas: {
        type: Array,
        default: () => [],
    },
    allSessions: { 
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
const dateInput = ref(null);

const triggerPicker = () => {
    if (dateInput.value && dateInput.value.showPicker) {
        dateInput.value.showPicker();
    }
};

const form = useForm({
    date: new Date().toLocaleDateString('sv-SE'), // Local YYYY-MM-DD
    status: 'open',
    selected_area_ids: [],
    meal_type: 'Comida',
    conflict_resolution: 'restart',
});

const selectAll = ref(false);
const mealOptions = ['Desayuno', 'Comida', 'Cena', 'Extra'];
const areaSearch = ref('');

const mealColors = {
    'Desayuno': 'bg-amber-500 border-amber-600 shadow-amber-100',
    'Comida': 'bg-indigo-600 border-indigo-700 shadow-indigo-100',
    'Cena': 'bg-purple-700 border-purple-800 shadow-purple-100',
    'Extra': 'bg-teal-600 border-teal-700 shadow-teal-100',
};

const areaColors = [
    'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
    'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
    'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800',
    'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
    'bg-teal-100 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-400 dark:border-teal-800',
    'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800',
    'bg-cyan-100 text-cyan-700 border-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-400 dark:border-cyan-800',
    'bg-pink-100 text-fuchsia-700 border-pink-200 dark:bg-pink-900/30 dark:text-pink-400 dark:border-pink-800',
];

const getAreaColor = (areaId) => {
    return areaColors[areaId % areaColors.length];
};

const filteredAreas = computed(() => {
    if (!areaSearch.value) return props.areas;
    return props.areas.filter(a => a.name.toLowerCase().includes(areaSearch.value.toLowerCase()));
});

// --- Slider Confirmation Logic ---
const sliderValue = ref(0);
const threshold = 95;
const isConfirmed = computed(() => sliderValue.value >= threshold);

// --- Conflict Detection Logic ---
const areasBusyWithOtherProviders = computed(() => {
    const busy = {};
    props.allSessions.forEach(session => {
        if (session.provider_id === props.provider?.id && session.meal_type === form.meal_type) return;
        if (session.meal_type !== form.meal_type) return;

        const areaIds = Array.isArray(session.selected_area_ids) ? session.selected_area_ids : JSON.parse(session.selected_area_ids || '[]');
        areaIds.forEach(id => {
            busy[id] = {
                provider: session.provider?.name || 'Otro proveedor',
                status: session.status
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
        form.date = new Date().toLocaleDateString('sv-SE'); // Local YYYY-MM-DD
        mode.value = props.initialMode;
        sliderValue.value = 0;
        areaSearch.value = '';

        if (props.initialMode === 'edit' && props.initialSession) {
            form.meal_type = props.initialSession.meal_type;
            form.selected_area_ids = [...(props.initialSession.selected_area_ids || [])];
        } else {
            form.meal_type = 'Comida';
            form.selected_area_ids = props.areas.map(a => a.id); // Default Select All
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
    } else if (!newVal && form.selected_area_ids.length === props.areas.length) {
        form.selected_area_ids = [];
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
    
    // NEW: Clear search after selection to show full list with changes
    if (areaSearch.value) {
        areaSearch.value = '';
    }
};

const selectMealType = (type) => {
    if (mode.value === 'new') {
        form.meal_type = type;
    }
};

const activateMenu = () => {
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
    <Modal :show="show" @close="emit('close')" max-width="4xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-4">
                <div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight leading-none">
                        {{ provider?.name }}
                    </h2>
                    <div class="flex items-center gap-3 mt-1">
                        <p class="text-[8px] font-black text-indigo-500 uppercase tracking-[0.2em]">{{ mode === 'edit' ? 'Gestión Activa' : 'Nueva Sesión' }}</p>
                        <span v-if="provider?.last_used_date" class="flex items-center text-[7px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                            <span class="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600 mr-2"></span>
                            Último servicio: {{ new Date(provider.last_used_date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                        </span>
                    </div>
                </div>
                <button @click="emit('close')" class="text-gray-400 hover:text-red-500 transition-colors">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="activateMenu">
                <div class="mb-6">
                    <div class="mb-6">
                        <InputLabel value="Fecha de la Sesión" class="text-[10px] font-black uppercase text-gray-400 mb-2 ml-2 tracking-widest" />
                        <div class="relative group cursor-pointer" @click="triggerPicker">
                            <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition-colors pointer-events-none" />
                            <input type="date" ref="dateInput" v-model="form.date" 
                                   class="w-full pl-12 pr-4 py-3.5 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-black text-gray-700 dark:text-white uppercase focus:border-indigo-500 focus:ring-0 transition-all cursor-pointer [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>
                    </div>

                    <InputError :message="form.errors.error" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl text-[10px] font-black uppercase text-red-600 dark:text-red-400" />
                    
                    <div class="grid grid-cols-4 gap-3">
                        <button v-for="opt in mealOptions" :key="opt" 
                                type="button"
                                @click="selectMealType(opt)"
                                :disabled="mode === 'edit'"
                                :class="[
                                    form.meal_type === opt 
                                        ? `${mealColors[opt]} text-white shadow-lg` 
                                        : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-indigo-300',
                                    mode === 'edit' && form.meal_type !== opt ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                class="px-3 py-3 border-2 rounded-2xl text-[9px] font-black uppercase transition-all tracking-widest flex items-center justify-center gap-2">
                            <ClockIcon v-if="form.meal_type === opt" class="h-4 w-4" />
                            {{ opt }}
                        </button>
                    </div>
                </div>

                <!-- SELECCIÓN DE ÁREAS -->
                <div class="mb-4">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="flex-1 relative">
                            <input type="text" 
                                   v-model="areaSearch"
                                   placeholder="Buscar área..." 
                                   class="block w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white shadow-inner" />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                        </div>
                        
                        <button type="button" @click="selectAll = !selectAll" 
                                class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl text-[9px] font-black uppercase tracking-widest border border-indigo-100 dark:border-indigo-800 transition-all">
                            {{ selectAll ? 'Deseleccionar' : 'Todos' }}
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 max-h-[350px] overflow-y-auto border-2 border-gray-50 dark:border-gray-700 p-4 rounded-[1.5rem] bg-gray-50/30 dark:bg-gray-900/30 custom-scrollbar">
                        <div v-for="area in filteredAreas" :key="area.id" 
                             @click="toggleAreaSelection(area.id)"
                             class="flex items-center justify-center p-3 rounded-xl transition-all border-2 cursor-pointer text-center min-h-[3.5rem] relative"
                             :class="[
                                form.selected_area_ids.includes(area.id) 
                                    ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-500 text-emerald-700 dark:text-emerald-400 shadow-sm' 
                                    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-300 dark:text-gray-600 hover:border-red-300 hover:text-red-400'
                             ]">
                            
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-black uppercase leading-tight tracking-tighter line-clamp-2">
                                    {{ area.name }}
                                </p>
                                <span v-if="areasBusyWithOtherProviders[area.id]" class="absolute -top-1 -right-1 flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                                </span>
                            </div>
                        </div>
                        <div v-if="filteredAreas.length === 0" class="col-span-full p-10 text-center text-gray-400 italic text-xs uppercase tracking-widest font-black">
                            Sin resultados
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.selected_area_ids" />
                </div>

                <!-- ALERTA DE REINICIO COMPACTA -->
                <div v-if="hasConflicts" class="mb-4 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
                    <div class="flex items-center">
                        <ExclamationTriangleIcon class="h-5 w-5 text-orange-500 mr-3 shrink-0" />
                        <p class="text-[9px] text-orange-700 dark:text-orange-400 font-black uppercase leading-tight">
                            ¡Atención! Áreas con pedidos previos serán reiniciadas.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <!-- SLIDER -->
                    <div v-if="hasConflicts" class="mb-1">
                        <div class="relative w-full bg-gray-100 dark:bg-gray-700 rounded-xl h-12 flex items-center justify-start overflow-hidden border-2 border-orange-200 focus-within:border-orange-500 transition-all">
                            <div class="absolute left-0 top-0 h-full bg-orange-500 transition-all duration-75 shadow-lg" :style="{ width: `${sliderValue}%` }"></div>
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
                            <span class="absolute left-0 right-0 text-center text-[8px] font-black uppercase tracking-[0.3em] text-gray-400 dark:text-gray-500 z-0 select-none">
                                Desliza para reiniciar
                            </span>
                        </div>
                    </div>

                    <PrimaryButton 
                        v-if="!hasConflicts"
                        @click="activateMenu"
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing" 
                        class="w-full justify-center py-4 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 dark:shadow-none"
                    >
                        Activar Sesión de Alimentos
                    </PrimaryButton>

                    <PrimaryButton 
                        v-else
                        @click="activateMenu"
                        :disabled="!isConfirmed || form.processing" 
                        class="w-full justify-center py-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="!isConfirmed ? 'opacity-20 grayscale' : 'bg-orange-600 hover:bg-orange-700'"
                    >
                        Confirmar y Abrir
                    </PrimaryButton>

                    <button @click="emit('close')" type="button" 
                            class="w-full py-3 bg-red-50/50 dark:bg-red-900/10 text-red-400 hover:text-red-600 dark:text-red-500 dark:hover:text-red-400 uppercase font-black text-[9px] tracking-[0.2em] transition-all rounded-xl border border-red-100/50 dark:border-red-900/30 hover:bg-red-50 dark:hover:bg-red-900/20">
                        Cancelar y volver al monitor
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<style scoped>
input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 60px;
  height: 60px;
  border-radius: 18px;
  background: white;
  cursor: grab;
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  border: 3px solid #f97316;
}

input[type="range"]::-moz-range-thumb {
  width: 60px;
  height: 60px;
  border-radius: 18px;
  background: white;
  cursor: grab;
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  border: 3px solid #f97316;
}

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.1); border-radius: 10px; }
</style>
