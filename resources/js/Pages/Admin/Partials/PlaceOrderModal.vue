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
    'Desayuno': 'border-amber-200 dark:border-amber-900/60 bg-amber-50/30 dark:bg-amber-950/20 hover:border-oro-400 hover:bg-amber-50/60',
    'Comida': 'border-tinto-200 dark:border-tinto-900/60 bg-tinto-50/30 dark:bg-tinto-950/20 hover:border-tinto-400 hover:bg-tinto-50/60',
    'Cena': 'border-purple-200 dark:border-purple-900/60 bg-purple-50/30 dark:bg-purple-950/20 hover:border-purple-400 hover:bg-purple-50/60',
    'Extra': 'border-teal-200 dark:border-teal-900/60 bg-teal-50/30 dark:bg-teal-950/20 hover:border-teal-400 hover:bg-teal-50/60',
};

const mealTypeTagStyles = {
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800',
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300 dark:border-tinto-800',
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-800',
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-800',
};

const getDishEmoji = (name, mealType) => {
    if (!name) return '🍽️';
    const n = name.toLowerCase();
    if (n.includes('huevo') || n.includes('chilaquil') || n.includes('omelet') || n.includes('hot cake') || n.includes('waffle')) return '🍳';
    if (n.includes('cafe') || n.includes('café') || n.includes('jugo') || n.includes('leche') || n.includes('avena')) return '☕';
    if (n.includes('carne') || n.includes('res') || n.includes('arrachera') || n.includes('bistec') || n.includes('milanesa') || n.includes('costilla')) return '🥩';
    if (n.includes('pollo') || n.includes('pechuga') || n.includes('alita') || n.includes('fajita')) return '🍗';
    if (n.includes('pescado') || n.includes('camaron') || n.includes('camarón') || n.includes('atun') || n.includes('marisco')) return '🐟';
    if (n.includes('taco') || n.includes('quesadilla') || n.includes('burrito') || n.includes('flauta') || n.includes('gordita')) return '🌮';
    if (n.includes('pasta') || n.includes('espagueti') || n.includes('lasaña')) return '🍝';
    if (n.includes('ensalada') || n.includes('vegetal') || n.includes('verdura') || n.includes('fruta')) return '🥗';
    if (n.includes('sopa') || n.includes('caldo') || n.includes('crema') || n.includes('pozole') || n.includes('menudo')) return '🍲';
    if (n.includes('hamburguesa') || n.includes('sandwich') || n.includes('torta')) return '🥪';
    if (n.includes('postre') || n.includes('pastel') || n.includes('gelatina') || n.includes('flan')) return '🍰';
    if (mealType === 'Desayuno') return '🥞';
    if (mealType === 'Cena') return '🥪';
    return '🍽️';
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
        <div class="p-8 md:p-10 bg-white dark:bg-gray-900 rounded-[2.5rem] relative overflow-hidden transition-all">
            <!-- ERROR DISPLAY -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-8 p-6 bg-red-50 dark:bg-rose-950/40 border-l-8 border-red-500 rounded-r-[2rem] shadow-lg">
                <p class="text-xs font-black text-red-700 dark:text-rose-300 uppercase tracking-[0.2em] mb-2">Hubo un problema:</p>
                <ul class="space-y-1">
                    <li v-for="(error, key) in form.errors" :key="key" class="text-[11px] text-red-600 dark:text-rose-400 font-bold uppercase tracking-tight">→ {{ error }}</li>
                </ul>
            </div>

            <!-- CABECERA DINÁMICA -->
            <div class="flex items-center mb-8 pb-6 border-b border-slate-100 dark:border-gray-800">
                <button v-if="step === 'details' && availableOptions.length > 1" 
                        @click="step = 'list'"
                        class="mr-4 p-3 rounded-2xl hover:bg-slate-100 dark:hover:bg-gray-800 text-slate-500 hover:text-slate-800 dark:hover:text-white transition-all border border-slate-200 dark:border-gray-700 cursor-pointer">
                    <ChevronLeftIcon class="h-6 w-6" />
                </button>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-gradient-to-tr from-tinto-900 to-tinto-800 rounded-2xl text-oro-300 border border-oro-500/40 shadow-sm">
                        <span class="text-2xl">{{ step === 'list' ? '🍲' : '✨' }}</span>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white uppercase tracking-tight leading-none">
                            {{ step === 'list' ? (form.target_user_id ? 'Asignar Platillo' : 'Cambiar Platillo') : (existingOrder ? 'Personalizar Pedido' : 'Confirmar Pedido') }}
                        </h2>
                        <p class="text-[10px] font-black text-tinto-700 dark:text-oro-400 uppercase tracking-[0.3em] mt-1">Catálogo Gastronómico Institucional</p>
                    </div>
                </div>
            </div>
            
            <div v-if="form.target_user_id" class="mb-8 p-5 bg-oro-50/60 dark:bg-oro-950/30 border-2 border-oro-200 dark:border-oro-800/60 rounded-[2rem] flex items-center gap-4 shadow-sm animate-pop">
                <div class="h-12 w-12 bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 rounded-2xl flex items-center justify-center font-black text-lg border border-oro-500/30 shadow-md">
                    {{ existingOrder?.user_name?.charAt(0) || '👤' }}
                </div>
                <div>
                    <p class="text-[9px] font-black uppercase text-oro-700 dark:text-oro-300 tracking-[0.2em]">Asignando platillo a:</p>
                    <p class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">{{ existingOrder?.user_name || 'Personal' }}</p>
                </div>
            </div>
            
            <!-- PASO 1: LISTA DE PLATILLOS (4 COLUMNAS) -->
            <div v-if="step === 'list'" class="space-y-6">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 text-center flex items-center justify-center gap-2">
                    <span>👇</span> Elige una de las opciones del menú:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <button v-for="opt in availableOptions" :key="opt.id"
                            @click="selectOption(opt)"
                            type="button"
                            :class="[
                                form.daily_menu_id === opt.id 
                                    ? 'border-tinto-700 dark:border-oro-500 bg-tinto-50/80 dark:bg-tinto-950/40 ring-4 ring-oro-400/30 selected-glow-tinto scale-[1.02] animate-pop' 
                                    : (mealTypeCardStyles[opt.meal_type] || 'border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm'),
                                'w-full text-left p-5 border-2 rounded-[2rem] transition-all flex flex-col justify-between group h-full relative overflow-hidden cursor-pointer hover:scale-[1.02] active:scale-95'
                            ]">
                        <div class="mb-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-[8px] font-black px-2.5 py-1 rounded-lg uppercase tracking-widest border shadow-sm flex items-center gap-1"
                                      :class="mealTypeTagStyles[opt.meal_type] || 'bg-slate-100 text-slate-500'">
                                    <span>{{ opt.meal_type === 'Desayuno' ? '🍳' : (opt.meal_type === 'Comida' ? '🍲' : '🌙') }}</span>
                                    {{ opt.meal_type }}
                                </span>
                                <CheckCircleIcon v-if="form.daily_menu_id === opt.id" class="h-6 w-6 text-tinto-700 dark:text-oro-400 animate-pop" />
                            </div>

                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-3xl group-hover:scale-125 transition-transform group-hover:rotate-6">{{ getDishEmoji(opt.name, opt.meal_type) }}</span>
                                <p class="font-black text-base uppercase tracking-tight leading-tight" :class="form.daily_menu_id === opt.id ? 'text-tinto-900 dark:text-oro-200' : 'text-slate-800 dark:text-white'">
                                    {{ opt.name }}
                                </p>
                            </div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-2 line-clamp-2 font-medium leading-relaxed italic">{{ opt.description }}</p>
                        </div>
                        <div class="pt-3 border-t border-slate-200/50 dark:border-gray-700/60 flex justify-end">
                            <span class="text-[9px] font-black uppercase tracking-widest text-tinto-700 dark:text-oro-400 group-hover:translate-x-1 transition-transform flex items-center gap-1">
                                <span>Seleccionar</span> <span>→</span>
                            </span>
                        </div>
                    </button>
                </div>
                
                <div class="mt-8 flex justify-center">
                    <SecondaryButton @click="emit('close')" class="!rounded-2xl !py-3.5 !px-10 !text-[11px] !font-black !uppercase !tracking-widest cursor-pointer">Cerrar Menú</SecondaryButton>
                </div>
            </div>

            <!-- PASO 2: DETALLES Y CONFIRMACIÓN -->
            <div v-if="step === 'details'" class="space-y-6">
                <!-- Tarjeta del platillo seleccionado -->
                <div v-if="currentMenu" class="bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 rounded-[2rem] p-6 text-white shadow-tinto-sm border border-oro-400/40 relative overflow-hidden animate-pop">
                    <div class="absolute -right-8 -bottom-8 text-8xl opacity-10 pointer-events-none">{{ getDishEmoji(currentMenu.name, currentMenu.meal_type) }}</div>
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-[9px] font-black uppercase tracking-widest bg-white/20 text-oro-200 px-3 py-1 rounded-lg border border-white/20 flex items-center gap-1.5">
                                <span>✓</span> <span>Platillo Seleccionado</span>
                            </span>
                            <span class="text-[10px] font-bold text-oro-200/90 flex items-center gap-1">
                                <span>👨‍🍳</span> {{ currentMenu.provider?.name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-4xl">{{ getDishEmoji(currentMenu.name, currentMenu.meal_type) }}</span>
                            <div>
                                <p class="font-black text-2xl uppercase tracking-tight leading-none text-white">{{ currentMenu.name }}</p>
                                <p class="text-xs text-oro-100/90 italic leading-snug mt-1.5">{{ currentMenu.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <InputLabel for="preferences" value="¿Alguna instrucción u observación especial?" class="text-slate-700 dark:text-gray-300 font-bold uppercase text-xs" />
                    <div class="mt-2">
                        <textarea
                            id="preferences"
                            rows="3"
                            class="block w-full rounded-2xl border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 text-slate-800 dark:text-white shadow-sm focus:border-tinto-700 focus:ring-tinto-700 text-xs font-semibold"
                            v-model="form.preferences"
                            placeholder="Ej: Sin cebolla, aderezo aparte, bien cocido, sin picante..."
                        ></textarea>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400 italic">
                        {{ form.daily_menu_id === existingOrder?.daily_menu_id 
                            ? 'Estas notas serán recibidas por la cocina para preparar tu platillo.' 
                            : 'Indica cualquier preferencia especial antes de confirmar tu orden.' }}
                    </p>
                    <InputError class="mt-2" :message="form.errors.preferences" />
                </div>

                <div class="flex flex-col gap-3 pt-2">
                    <PrimaryButton 
                        @click="submit" 
                        class="w-full justify-center !py-4 !text-xs !font-black !uppercase !tracking-widest bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white border border-oro-400/40 shadow-tinto-sm hover:scale-[1.01] active:scale-95 transition-all shine-effect cursor-pointer"
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing || !form.daily_menu_id"
                    >
                        <template v-if="form.processing">Guardando en sistema...</template>
                        <template v-else>
                            {{ (existingOrder && existingOrder.id) ? '✓ Actualizar Selección' : '✓ Confirmar y Guardar Platillo' }}
                        </template>
                    </PrimaryButton>
                    
                    <SecondaryButton @click="emit('close')" class="w-full justify-center !py-3 border-none shadow-none text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">
                        Cancelar
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
