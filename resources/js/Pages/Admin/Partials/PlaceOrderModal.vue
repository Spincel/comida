<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ChevronLeftIcon, CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    show: Boolean,
    menu: Object, // Initial or single selected menu
    existingOrder: {
        type: Object,
        default: null,
    },
    availableOptions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

// View states: 'list' (to choose dish) or 'details' (to add notes and confirm)
const step = ref('list');

const form = useForm({
    daily_menu_id: null,
    preferences: '',
    meal_type: '', 
    target_user_id: null, // NEW: For manager-led orders
});

const currentMenu = ref(null);

watch(() => props.show, (isVisible) => {
    if (isVisible) {
        if (props.existingOrder) {
            form.daily_menu_id = props.existingOrder.daily_menu_id;
            form.preferences = props.existingOrder.preferences || '';
            form.meal_type = props.existingOrder.meal_type;
            form.target_user_id = props.existingOrder.user_id || null; // NEW
            currentMenu.value = props.existingOrder.daily_menu;
            step.value = props.availableOptions.length > 1 ? 'list' : 'details';
        } else if (props.menu) {
            form.daily_menu_id = props.menu.id;
            form.preferences = '';
            form.meal_type = props.menu.meal_type;
            form.target_user_id = null;
            currentMenu.value = props.menu;
            step.value = 'details';
        } else if (props.availableOptions.length > 0) {
            // Case for Simple Mode when clicking a name with no order
            form.daily_menu_id = null;
            form.preferences = '';
            form.meal_type = props.availableOptions[0].meal_type;
            form.target_user_id = props.existingOrder?.user_id || null; // Accessing from the shell object we passed
            currentMenu.value = null;
            step.value = 'list';
        }
    }
});

const mealTypeCardStyles = {
    'Desayuno': 'border-amber-100 bg-amber-50/20 hover:border-amber-300 hover:bg-amber-50/50',
    'Comida': 'border-indigo-100 bg-indigo-50/20 hover:border-indigo-300 hover:bg-indigo-50/50',
    'Cena': 'border-purple-100 bg-purple-50/20 hover:border-purple-300 hover:bg-purple-50/50',
    'Extra': 'border-teal-100 bg-teal-50/20 hover:border-teal-300 hover:bg-teal-50/50',
};

const mealTypeTagStyles = {
    'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200',
    'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200',
    'Cena': 'bg-purple-100 text-purple-700 border-purple-200',
    'Extra': 'bg-teal-100 text-teal-700 border-teal-200',
};

const selectOption = (option) => {
    if (form.daily_menu_id !== option.id) {
        form.daily_menu_id = option.id;
        currentMenu.value = option;
        form.preferences = ''; // Clear notes if dish changes
        form.meal_type = option.meal_type; // Ensure meal_type is updated if changed (though usually same in step 1)
    }
    step.value = 'details'; // Move to next step
};

const submit = () => {
    // Only update if we have a real order ID, otherwise store a new one
    if (props.existingOrder && props.existingOrder.id) {
        form.put(route('orders.update', props.existingOrder.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
        });
    } else {
        form.post(route('orders.store'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    }
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="6xl">
        <div class="p-10">
            <!-- ERROR DISPLAY -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-10 p-6 bg-red-50 border-l-8 border-red-500 rounded-r-[2rem] shadow-lg shadow-red-100">
                <p class="text-xs font-black text-red-700 uppercase tracking-[0.2em] mb-2">Hubo un problema:</p>
                <ul class="space-y-1">
                    <li v-for="(error, key) in form.errors" :key="key" class="text-[11px] text-red-600 font-bold uppercase tracking-tight">→ {{ error }}</li>
                </ul>
            </div>

            <!-- CABECERA DINÁMICA -->
            <div class="flex items-center mb-10">
                <button v-if="step === 'details' && availableOptions.length > 1" 
                        @click="step = 'list'"
                        class="mr-6 p-3 rounded-2xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 transition-all border border-transparent hover:border-gray-200">
                    <ChevronLeftIcon class="h-8 w-8" />
                </button>
                <div>
                    <h2 class="text-3xl font-black text-gray-900 dark:text-gray-100 uppercase tracking-tighter leading-none">
                        {{ step === 'list' ? (form.target_user_id ? 'Asignar Platillo' : 'Cambiar Platillo') : (existingOrder ? 'Personalizar Pedido' : 'Confirmar Pedido') }}
                    </h2>
                    <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.3em] mt-2">Selección de catálogo permanente</p>
                </div>
            </div>
            
            <div v-if="form.target_user_id" class="mb-10 p-6 bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-100 dark:border-amber-800 rounded-[2.5rem] flex items-center gap-6 shadow-xl shadow-amber-900/5">
                <div class="h-14 w-14 bg-amber-500 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg">
                    {{ existingOrder?.user_name?.charAt(0) || '?' }}
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase text-amber-600 dark:text-amber-400 tracking-[0.2em] mb-1">Asignando platillo a:</p>
                    <p class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">{{ existingOrder?.user_name || 'Personal' }}</p>
                </div>
            </div>
            
            <!-- PASO 1: LISTA DE PLATILLOS (4 COLUMNAS) -->
            <div v-if="step === 'list'" class="space-y-6">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-gray-400 text-center">Elige una de las opciones disponibles:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-h-[60vh] overflow-y-auto pr-4 custom-scrollbar">
                    <button v-for="opt in availableOptions" :key="opt.id"
                            @click="selectOption(opt)"
                            type="button"
                            :class="[
                                form.daily_menu_id === opt.id 
                                    ? 'border-indigo-600 bg-indigo-100 ring-4 ring-indigo-500/20 shadow-xl scale-[1.02]' 
                                    : (mealTypeCardStyles[opt.meal_type] || 'border-gray-100 bg-white shadow-sm'),
                                'w-full text-left p-4 border-2 rounded-[2rem] transition-all flex flex-col justify-between group h-full relative overflow-hidden'
                            ]">
                        <div class="mb-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest shadow-sm"
                                      :class="mealTypeTagStyles[opt.meal_type] || 'bg-gray-100 text-gray-500'">
                                    {{ opt.meal_type }}
                                </span>
                                <CheckCircleIcon v-if="form.daily_menu_id === opt.id" class="h-5 w-5 text-indigo-600" />
                            </div>
                            <p class="font-black text-base uppercase tracking-tighter leading-tight" :class="form.daily_menu_id === opt.id ? 'text-indigo-900' : 'text-gray-800 dark:text-white'">
                                {{ opt.name }}
                            </p>
                            <p class="text-[9px] text-gray-500 mt-2 line-clamp-2 font-medium leading-relaxed italic">{{ opt.description }}</p>
                        </div>
                        <div class="pt-3 border-t border-gray-200/50 dark:border-gray-700 flex justify-end">
                            <span class="text-[9px] font-black uppercase tracking-widest text-indigo-600 group-hover:translate-x-1 transition-transform">Elegir →</span>
                        </div>
                    </button>
                </div>
                
                <div class="mt-10 flex justify-center">
                    <SecondaryButton @click="emit('close')" class="!rounded-2xl !py-4 !px-12 !text-[11px] !font-black !uppercase !tracking-widest">Cerrar Catálogo</SecondaryButton>
                </div>
            </div>

            <!-- PASO 2: DETALLES Y CONFIRMACIÓN -->
            <div v-if="step === 'details'" class="space-y-6">
                <!-- Tarjeta del platillo seleccionado -->
                <div v-if="currentMenu" class="bg-indigo-600 rounded-2xl p-5 text-white shadow-lg shadow-indigo-200 dark:shadow-none">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest bg-white/20 px-2 py-0.5 rounded">Seleccionado</span>
                        <span class="text-[10px] font-bold opacity-80">{{ currentMenu.provider.name }}</span>
                    </div>
                    <p class="font-black text-xl mb-1">{{ currentMenu.name }}</p>
                    <p class="text-sm opacity-90 italic leading-snug">{{ currentMenu.description }}</p>
                </div>

                <div>
                    <InputLabel for="preferences" value="¿Alguna instrucción especial?" class="text-gray-700 dark:text-gray-300 font-bold" />
                    <div class="mt-2">
                        <textarea
                            id="preferences"
                            rows="3"
                            class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            v-model="form.preferences"
                            placeholder="Ej: Sin cebolla, extra salsa, términos de cocción..."
                        ></textarea>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 italic">
                        {{ form.daily_menu_id === existingOrder?.daily_menu_id 
                            ? 'Estas notas ayudan a la cocina a preparar tu platillo.' 
                            : 'Has cambiado de platillo, por favor indica tus nuevas preferencias.' }}
                    </p>
                    <InputError class="mt-2" :message="form.errors.preferences" />
                </div>

                <div class="flex flex-col gap-3">
                    <PrimaryButton 
                        @click="submit" 
                        class="w-full justify-center py-4 text-base font-black uppercase tracking-widest"
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing || !form.daily_menu_id"
                    >
                        <template v-if="form.processing">Procesando...</template>
                        <template v-else>
                            {{ (existingOrder && existingOrder.id) ? 'Actualizar Pedido' : 'Guardar Pedido' }}
                        </template>
                    </PrimaryButton>
                    
                    <SecondaryButton @click="emit('close')" class="w-full justify-center py-3 border-none shadow-none text-gray-400 hover:text-gray-600">
                        Cancelar
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
