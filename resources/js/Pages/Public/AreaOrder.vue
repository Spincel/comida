<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { 
    UserIcon, CheckCircleIcon, ClockIcon, BuildingOfficeIcon, 
    BuildingStorefrontIcon, DocumentTextIcon, SparklesIcon, 
    InformationCircleIcon, CheckBadgeIcon, ExclamationTriangleIcon, 
    MagnifyingGlassIcon, ArrowPathIcon, SunIcon, MoonIcon, 
    CheckIcon, PaperAirplaneIcon, BookmarkSquareIcon, ArrowUturnLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    session: Object,
    area: Object,
    teamMembers: Array,
    dishes: Array,
    existingOrders: Object,
    isOpen: Boolean,
    closeReason: String,
});

// Dark mode local state
const isDark = ref(false);
onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark') || 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    }
});

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

// Form state
const form = useForm({
    user_id: null,
    daily_menu_id: null,
    preferences: '',
    activity_performed: '',
});

const memberSearch = ref('');
const orderStep = ref(1); // 1: Select Member, 2: Select Dish, 3: Justification
const orderSavedSuccessfully = ref(false);
const justificationSaved = ref(false);

const filteredMembers = computed(() => {
    if (!memberSearch.value) return props.teamMembers || [];
    const q = memberSearch.value.toLowerCase();
    return (props.teamMembers || []).filter(m => m.name.toLowerCase().includes(q));
});

const selectedMember = computed(() => {
    return (props.teamMembers || []).find(m => m.id === form.user_id);
});

const existingMemberOrder = computed(() => {
    if (!form.user_id) return null;
    return props.existingOrders?.[form.user_id] || null;
});

const selectMember = (member) => {
    form.user_id = member.id;
    const existing = props.existingOrders?.[member.id];
    if (existing) {
        form.daily_menu_id = existing.daily_menu_id;
        form.preferences = existing.preferences || '';
        form.activity_performed = existing.activity_performed || '';
        orderSavedSuccessfully.value = true;
        justificationSaved.value = Boolean(existing.activity_performed);
    } else {
        form.daily_menu_id = null;
        form.preferences = '';
        form.activity_performed = '';
        orderSavedSuccessfully.value = false;
        justificationSaved.value = false;
    }
    orderStep.value = 2;
    nextTick(() => {
        const el = document.getElementById('step-2-section');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    });
};

const selectDish = (dish) => {
    form.daily_menu_id = dish.id;
};

const submitDishOrder = () => {
    if (!form.user_id) {
        return alert('Por favor, selecciona tu nombre de la lista.');
    }
    if (!form.daily_menu_id) {
        return alert('Por favor, selecciona un platillo del menú.');
    }

    form.post(route('orders.storePublicAreaOrder', { session: props.session.id, area: props.area.id }), {
        preserveScroll: true,
        onSuccess: () => {
            orderSavedSuccessfully.value = true;
            orderStep.value = 3;
            nextTick(() => {
                const el = document.getElementById('step-3-section');
                if (el) el.scrollIntoView({ behavior: 'smooth' });
            });
        }
    });
};

const quickJustifications = [
    'Guardia de turno',
    'Soporte y atención a sesión',
    'Mantenimiento a sistemas y servidores',
    'Trabajo extraordinario de área',
    'Inventario y archivo',
    'Cierre administrativo'
];

const applyQuickJustification = (text) => {
    form.activity_performed = text;
};

const submitJustification = () => {
    if (!form.activity_performed || form.activity_performed.trim().length < 3) {
        return alert('Por favor, escribe el motivo o justificación de por qué te quedas a laborar.');
    }

    form.post(route('orders.storePublicAreaOrder', { session: props.session.id, area: props.area.id }), {
        preserveScroll: true,
        onSuccess: () => {
            justificationSaved.value = true;
        }
    });
};

const resetForAnother = () => {
    form.reset();
    form.user_id = null;
    form.daily_menu_id = null;
    form.preferences = '';
    form.activity_performed = '';
    form.clearErrors();
    orderSavedSuccessfully.value = false;
    justificationSaved.value = false;
    orderStep.value = 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
    'Comida': 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800',
    'Cena': 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800',
    'Extra': 'bg-teal-100 text-teal-800 border-teal-300 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800'
};
</script>

<template>
    <Head :title="'Pedido de ' + session.meal_type + ' - ' + area.name" />

    <div class="min-h-screen bg-slate-50 dark:bg-gray-950 text-slate-800 dark:text-gray-100 transition-colors duration-300 antialiased selection:bg-indigo-500 selection:text-white pb-20">
        
        <!-- HEADER PÚBLICO -->
        <header class="sticky top-0 z-30 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-b border-slate-200/80 dark:border-gray-800 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <ApplicationLogo class="h-10 w-10 fill-current text-indigo-600 dark:text-indigo-400" />
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black tracking-tight text-slate-900 dark:text-white uppercase">SICOA</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 uppercase">Pedido Directo</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ area.name }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="toggleDarkMode" class="p-2.5 rounded-xl bg-slate-100 dark:bg-gray-800 hover:bg-slate-200 dark:hover:bg-gray-700 text-slate-600 dark:text-gray-300 transition-all" title="Cambiar Tema">
                        <SunIcon v-if="isDark" class="h-4 w-4 text-amber-400" />
                        <MoonIcon v-else class="h-4 w-4 text-indigo-600" />
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 pt-8">
            
            <!-- TARJETA CONTEXTUAL DE TURNO -->
            <div class="bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 text-white rounded-[2.5rem] p-6 sm:p-8 shadow-2xl shadow-indigo-950/20 relative overflow-hidden mb-8 border border-white/10">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase border shadow-sm" :class="mealTypeTagColors[session.meal_type] || 'bg-white/20 text-white'">
                                {{ session.meal_type }}
                            </span>
                            <span class="text-[10px] font-bold text-indigo-200 uppercase tracking-widest flex items-center gap-1.5">
                                <ClockIcon class="h-3.5 w-3.5" /> {{ session.date }}
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tight leading-none mb-2">
                            {{ area.name }}
                        </h1>
                        <p class="text-xs font-bold text-indigo-200 uppercase tracking-widest flex items-center gap-2">
                            <BuildingStorefrontIcon class="h-4 w-4" /> Proveedor: <span class="text-white">{{ session.provider?.name }}</span>
                        </p>
                    </div>

                    <div v-if="isOpen" class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-[10px] font-black uppercase tracking-widest backdrop-blur-md">
                        <div class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></div>
                        <span>Turno Activo</span>
                    </div>
                </div>
            </div>

            <!-- ESTADO CERRADO O NO DISPONIBLE -->
            <div v-if="!isOpen" class="bg-white dark:bg-gray-900 rounded-[3rem] p-10 sm:p-16 border border-slate-200 dark:border-gray-800 text-center shadow-xl">
                <div class="h-20 w-20 bg-rose-50 dark:bg-rose-950/30 text-rose-500 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-inner border border-rose-100 dark:border-rose-900/40">
                    <ExclamationTriangleIcon class="h-10 w-10" />
                </div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight mb-3">
                    Servicio No Disponible
                </h2>
                <p class="text-sm text-slate-500 dark:text-gray-400 max-w-md mx-auto leading-relaxed mb-8">
                    {{ closeReason || 'El turno de servicio se encuentra cerrado o no corresponde a tu área.' }}
                </p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Comunícate con tu Gerente de Área o el personal de Adquisiciones.
                </p>
            </div>

            <!-- FLUJO DE PASOS INTERACTIVO -->
            <div v-else class="space-y-8">
                
                <!-- PASO 1: SELECCIONA TU NOMBRE -->
                <div id="step-1-section" class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 sm:p-8 border border-slate-200 dark:border-gray-800 shadow-xl space-y-6 transition-all">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 dark:border-gray-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm transition-all"
                                 :class="form.user_id ? 'bg-emerald-500 text-white shadow-md' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600'">
                                <CheckIcon v-if="form.user_id" class="h-5 w-5 stroke-[3]" />
                                <span v-else>1</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-tight text-slate-800 dark:text-white">
                                    1. ¿Quién eres?
                                </h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    {{ selectedMember ? selectedMember.name : 'Toca tu nombre para comenzar tu pedido' }}
                                </p>
                            </div>
                        </div>

                        <!-- BUSCADOR -->
                        <div class="relative w-full sm:w-64">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                            <input v-model="memberSearch" type="text" placeholder="Buscar mi nombre..."
                                   class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-gray-800 border-slate-200 dark:border-gray-700 rounded-xl text-[10px] font-bold uppercase focus:ring-indigo-500 shadow-inner" />
                        </div>
                    </div>

                    <!-- GRID DE INTEGRANTES -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="m in filteredMembers" :key="m.id" @click="selectMember(m)"
                             class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-center justify-between group relative shadow-sm hover:scale-[1.01] active:scale-95"
                             :class="form.user_id === m.id ? 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-500 shadow-indigo-500/10' : 'bg-slate-50 dark:bg-gray-800/60 border-transparent hover:border-slate-200 dark:hover:border-gray-700'">
                            
                            <div class="flex items-center gap-3 min-w-0">
                                <img :src="m.avatar_url" class="h-9 w-9 rounded-full border-2 border-white dark:border-gray-700 shadow-sm object-cover shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-black uppercase truncate text-slate-800 dark:text-gray-200">
                                        {{ m.name }}
                                    </p>
                                    <p v-if="existingOrders[m.id]" class="text-[8px] font-bold text-emerald-600 dark:text-emerald-400 uppercase truncate">
                                        ✓ {{ existingOrders[m.id].dish_name }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="form.user_id === m.id" class="h-6 w-6 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 ml-2 shadow-md">
                                <CheckIcon class="h-3.5 w-3.5 stroke-[3]" />
                            </div>
                        </div>

                        <div v-if="filteredMembers.length === 0" class="col-span-full py-8 text-center text-slate-400 text-xs font-bold uppercase">
                            No se encontraron comensales con ese nombre
                        </div>
                    </div>
                </div>

                <!-- PASO 2: ELIGE TU PLATILLO Y ENVÍA EL PEDIDO (VISIBLE TRAS ELEGIR PERSONA) -->
                <div v-if="form.user_id" id="step-2-section" 
                     class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 sm:p-8 border border-slate-200 dark:border-gray-800 shadow-xl space-y-6 animate-fade-in">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 dark:border-gray-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm transition-all"
                                 :class="orderSavedSuccessfully ? 'bg-emerald-500 text-white shadow-md' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600'">
                                <CheckIcon v-if="orderSavedSuccessfully" class="h-5 w-5 stroke-[3]" />
                                <span v-else>2</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-tight text-slate-800 dark:text-white">
                                    2. Elige tu Platillo
                                </h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Para {{ selectedMember?.name }} • {{ session.provider?.name }}
                                </p>
                            </div>
                        </div>

                        <div v-if="orderSavedSuccessfully" class="px-3 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 text-[9px] font-black uppercase border border-emerald-200 dark:border-emerald-800 flex items-center gap-1.5 shadow-sm">
                            <CheckCircleIcon class="h-4 w-4" />
                            <span>Pedido Visible para tu Gerente</span>
                        </div>
                    </div>

                    <!-- GRID DE PLATILLOS -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="dish in dishes" :key="dish.id" @click="selectDish(dish)"
                             class="p-5 rounded-3xl border-2 transition-all cursor-pointer flex flex-col justify-between group shadow-sm hover:scale-[1.01] active:scale-95"
                             :class="form.daily_menu_id === dish.id ? 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-500 shadow-indigo-500/10' : 'bg-slate-50 dark:bg-gray-800/60 border-transparent hover:border-slate-200 dark:hover:border-gray-700'">
                            
                            <div class="space-y-2">
                                <div class="flex justify-between items-start gap-3">
                                    <h4 class="text-xs font-black uppercase text-slate-900 dark:text-white leading-tight">
                                        {{ dish.name }}
                                    </h4>
                                    <div class="h-5 w-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-all"
                                         :class="form.daily_menu_id === dish.id ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                        <CheckIcon v-if="form.daily_menu_id === dish.id" class="h-3 w-3 stroke-[3]" />
                                    </div>
                                </div>

                                <p v-if="dish.description" class="text-[10px] text-slate-500 dark:text-gray-400 leading-relaxed line-clamp-3">
                                    {{ dish.description }}
                                </p>
                            </div>
                        </div>

                        <div v-if="dishes.length === 0" class="col-span-full py-8 text-center text-slate-400 text-xs font-bold uppercase">
                            No hay platillos publicados para este proveedor
                        </div>
                    </div>

                    <!-- PREFERENCIAS / OBSERVACIONES -->
                    <div class="pt-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-gray-400 mb-2 ml-1">
                            Observaciones / Notas Especiales (Opcional):
                        </label>
                        <input v-model="form.preferences" type="text" placeholder="Ej. Sin cebolla / salsa aparte / término medio..."
                               class="w-full px-5 py-3.5 bg-slate-50 dark:bg-gray-800 border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold focus:ring-indigo-500 shadow-inner" />
                    </div>

                    <!-- BOTÓN ENVIAR PEDIDO -->
                    <div class="pt-2">
                        <button @click="submitDishOrder" :disabled="form.processing || !form.daily_menu_id"
                                class="w-full py-5 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-indigo-600 bg-[length:200%_auto] animate-gradient text-white text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-500/30 hover:scale-[1.01] active:scale-95 transition-all flex items-center justify-center gap-3 disabled:opacity-50">
                            <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <PaperAirplaneIcon v-else class="h-4 w-4" />
                            <span>{{ orderSavedSuccessfully ? 'Actualizar y Enviar Platillo' : 'Enviar Pedido al Gerente' }}</span>
                        </button>
                    </div>
                </div>

                <!-- PASO 3: MOTIVO / JUSTIFICACIÓN (SE ACTIVA/DESTACA AL ENVIAR PLATILLO) -->
                <div v-if="form.user_id && form.daily_menu_id" id="step-3-section" 
                     class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 sm:p-8 border border-slate-200 dark:border-gray-800 shadow-xl space-y-6 animate-fade-in"
                     :class="{ 'ring-2 ring-indigo-500 shadow-indigo-500/10': orderSavedSuccessfully && !justificationSaved }">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 dark:border-gray-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm transition-all"
                                 :class="justificationSaved ? 'bg-emerald-500 text-white shadow-md' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600'">
                                <CheckIcon v-if="justificationSaved" class="h-5 w-5 stroke-[3]" />
                                <span v-else>3</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-black uppercase tracking-tight text-slate-800 dark:text-white">
                                        3. Motivo / Justificación
                                    </h3>
                                    <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded"
                                          :class="justificationSaved ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'">
                                        {{ justificationSaved ? 'Guardada' : 'Agrega antes de cerrar' }}
                                    </span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Indica la actividad o motivo por el que te quedas a laborar
                                </p>
                            </div>
                        </div>

                        <div v-if="orderSavedSuccessfully && !justificationSaved" class="text-[9px] font-black uppercase text-indigo-600 dark:text-indigo-400 animate-pulse">
                            ⚡ Tu comida ya está enviada. Agrega tu motivo aquí:
                        </div>
                    </div>

                    <!-- SUGERENCIAS RÁPIDAS -->
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">
                            Sugerencias rápidas (toca una para aplicar):
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="q in quickJustifications" :key="q" type="button" @click="applyQuickJustification(q)"
                                    class="px-3 py-1.5 rounded-xl text-[9px] font-bold uppercase transition-all bg-slate-100 dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-400 border border-slate-200 dark:border-gray-700">
                                {{ q }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <textarea v-model="form.activity_performed" rows="3" 
                                  placeholder="Escribe aquí la actividad o motivo detallado..."
                                  class="w-full p-4 bg-slate-50 dark:bg-gray-800 border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-gray-200 focus:ring-indigo-500 shadow-inner resize-none"></textarea>
                        <div class="flex justify-between items-center mt-1 px-2">
                            <span v-if="form.errors.activity_performed" class="text-[10px] font-bold text-rose-500">
                                {{ form.errors.activity_performed }}
                            </span>
                            <span v-else></span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase">
                                {{ (form.activity_performed || '').length }} / 500
                            </span>
                        </div>
                    </div>

                    <!-- BOTÓN GUARDAR JUSTIFICACIÓN -->
                    <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                        <button @click="submitJustification" :disabled="form.processing || !form.activity_performed"
                                class="w-full sm:flex-1 py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-3 disabled:opacity-50 active:scale-95">
                            <BookmarkSquareIcon class="h-4 w-4" />
                            <span>{{ justificationSaved ? 'Actualizar Justificación' : 'Guardar Justificación' }}</span>
                        </button>
                    </div>

                    <!-- ESTADO FINAL / REGISTRAR OTRO COMPAÑERO -->
                    <div v-if="orderSavedSuccessfully" class="pt-6 border-t border-slate-100 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 text-xs font-bold">
                            <CheckCircleIcon class="h-5 w-5" />
                            <span>¡Listo! Tu pedido está completo en el sistema.</span>
                        </div>

                        <button @click="resetForAnother" 
                                class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-gray-800 hover:bg-slate-200 dark:hover:bg-gray-700 text-slate-600 dark:text-gray-300 text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2 active:scale-95">
                            <ArrowUturnLeftIcon class="h-3.5 w-3.5" />
                            <span>Registrar a otro compañero</span>
                        </button>
                    </div>

                </div>

            </div>

        </main>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.4);
    border-radius: 4px;
}
</style>
