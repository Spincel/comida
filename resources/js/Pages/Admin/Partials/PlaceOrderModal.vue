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
    meal_type: '', // Added
});

const currentMenu = ref(null);

watch(() => props.show, (isVisible) => {
    if (isVisible) {
        if (props.existingOrder) {
            form.daily_menu_id = props.existingOrder.daily_menu_id;
            form.preferences = props.existingOrder.preferences || '';
            form.meal_type = props.existingOrder.meal_type; // Added
            currentMenu.value = props.existingOrder.daily_menu;
            // If editing, we start at list if there are options, otherwise details
            step.value = props.availableOptions.length > 1 ? 'list' : 'details';
        } else if (props.menu) {
            form.daily_menu_id = props.menu.id;
            form.preferences = '';
            form.meal_type = props.menu.meal_type; // Added
            currentMenu.value = props.menu;
            step.value = 'details';
        }
    }
});

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
    if (props.existingOrder) {
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
    <Modal :show="show" @close="emit('close')" max-width="md">
        <div class="p-6">
            <!-- CABECERA DINÁMICA -->
            <div class="flex items-center mb-6">
                <button v-if="step === 'details' && availableOptions.length > 1" 
                        @click="step = 'list'"
                        class="mr-3 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500">
                    <ChevronLeftIcon class="h-6 w-6" />
                </button>
                <h2 class="text-xl font-black text-gray-900 dark:text-gray-100">
                    {{ step === 'list' ? 'Cambiar Platillo' : (existingOrder ? 'Personalizar Pedido' : 'Confirmar Pedido') }}
                </h2>
            </div>
            
            <!-- PASO 1: LISTA DE PLATILLOS (SOLO SI HAY OPCIONES) -->
            <div v-if="step === 'list'" class="space-y-3">
                <p class="text-sm text-gray-500 mb-4 font-medium italic">Selecciona el platillo que deseas:</p>
                <div class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                    <button v-for="opt in availableOptions" :key="opt.id"
                            @click="selectOption(opt)"
                            type="button"
                            :class="form.daily_menu_id === opt.id 
                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-500 ring-inset' 
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-indigo-300'"
                            class="w-full text-left p-4 border rounded-2xl transition-all shadow-sm flex items-center justify-between group">
                        <div class="flex-1 mr-4">
                            <p class="font-black text-base" :class="form.daily_menu_id === opt.id ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300'">
                                {{ opt.name }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ opt.description }}</p>
                        </div>
                        <CheckCircleIcon v-if="form.daily_menu_id === opt.id" class="h-6 w-6 text-indigo-500 shrink-0" />
                        <div v-else class="h-6 w-6 rounded-full border-2 border-gray-200 dark:border-gray-600 group-hover:border-indigo-300 shrink-0"></div>
                    </button>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="emit('close')">Cerrar</SecondaryButton>
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
                        :disabled="form.processing"
                    >
                        {{ existingOrder ? 'Actualizar mi Pedido' : 'Confirmar Pedido' }}
                    </PrimaryButton>
                    
                    <SecondaryButton @click="emit('close')" class="w-full justify-center py-3 border-none shadow-none text-gray-400 hover:text-gray-600">
                        Cancelar
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
