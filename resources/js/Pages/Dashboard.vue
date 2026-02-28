<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ActivateMenuModal from '@/Pages/Admin/Partials/ActivateMenuModal.vue';
import DeactivateMenuConfirmationModal from '@/Pages/Admin/Partials/DeactivateMenuConfirmationModal.vue';
import SubmitOrdersConfirmationModal from '@/Pages/Admin/Partials/SubmitOrdersConfirmationModal.vue';
import JustificationModal from '@/Pages/Admin/Partials/JustificationModal.vue';
import DeleteSessionModal from '@/Pages/Admin/Partials/DeleteSessionModal.vue';
import PlaceOrderModal from '@/Pages/Admin/Partials/PlaceOrderModal.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { 
    CheckBadgeIcon, 
    ClockIcon, 
    UserGroupIcon, 
    ClipboardDocumentListIcon, 
    PencilSquareIcon, 
    PlusIcon, 
    UserIcon, 
    ChevronRightIcon, 
    TrashIcon, 
    XMarkIcon, 
    CalendarDaysIcon, 
    ListBulletIcon,
    ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    auth: Object,
    userRole: String,
    // Acquisitions Props
    providers: { type: Array, default: () => [] },
    areas: Array,
    submittedAreasToday: { type: Array, default: () => [] },
    dishSummaryToday: { type: Array, default: () => [] },
    totalOrdersToday: { type: Number, default: 0 },
    openSessions: { type: Array, default: () => [] },
    allOpenSessionsToday: { type: Array, default: () => [] },
    allSessionsToday: { type: Array, default: () => [] },
    // Diner Props
    myOrdersToday: { type: Array, default: () => [] },
    availableMenus: Array,
    orderHistory: Array,
    // Area Manager Props
    teamOrders: Array,
    area: Object,
    activeMealTypes: Array,
    historicalSessions: { type: Array, default: () => [] },
});

const user = props.auth.user;

// --- Acquisitions Logic: Selected Session for Monitoring ---
const selectedSessionId = ref(null);

watch(() => props.openSessions, (sessions) => {
    if (sessions?.length > 0) {
        if (!selectedSessionId.value || !sessions.find(s => s.id === selectedSessionId.value)) {
            selectedSessionId.value = sessions[0].id;
        }
    } else {
        selectedSessionId.value = null;
    }
}, { immediate: true });

const activeSession = computed(() => {
    return props.openSessions?.find(s => s.id === selectedSessionId.value) || props.openSessions?.[0];
});

const activeDishSummary = computed(() => {
    if (!activeSession.value) return [];
    const summary = props.dishSummaryToday?.find(s => s.meal_type === activeSession.value.meal_type);
    return summary?.dishes || [];
});

const activeTotalOrders = computed(() => {
    if (!activeSession.value) return 0;
    const summary = props.dishSummaryToday?.find(s => s.meal_type === activeSession.value.meal_type);
    return summary?.total || 0;
});

// --- Timer Logic for Active Menus ---
const activeTimers = ref({});

const updateTimers = () => {
    const now = new Date();
    props.openSessions.forEach(session => {
        if (session.activated_at) {
            const activatedAt = new Date(session.activated_at);
            const diff = Math.floor((now - activatedAt) / 1000);
            
            const hours = Math.floor(diff / 3600).toString().padStart(2, '0');
            const minutes = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
            const seconds = (diff % 60).toString().padStart(2, '0');
            
            activeTimers.value[session.id] = `${hours}:${minutes}:${seconds}`;
        }
    });
};

let timerInterval;
let refreshInterval;

onMounted(() => {
    updateTimers();
    timerInterval = setInterval(updateTimers, 1000);
    
    refreshInterval = setInterval(() => {
        router.reload({ 
            preserveScroll: true,
            only: ['providers', 'submittedAreasToday', 'dishSummaryToday', 'totalOrdersToday', 'openSessions', 'myOrdersToday', 'availableMenus', 'teamOrders', 'historicalSessions']
        });
    }, 10000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    if (refreshInterval) clearInterval(refreshInterval);
});

const roleName = {
    'admin': 'Administrador General',
    'acquisitions_manager': 'Adquisiciones',
    'area_manager': 'Gerente de Área',
    'diner': 'Comensal',
}[user.role];

// --- Acquisitions Modals Logic ---
const showActivateMenuModal = ref(false);
const selectedProviderForActivation = ref(null);
const activateModalMode = ref('new');
const sessionToEdit = ref(null);

const showDeactivateMenuModal = ref(false);
const sessionToDeactivate = ref(null);

const openActivateMenuModal = (provider) => { 
    selectedProviderForActivation.value = provider; 
    activateModalMode.value = 'new';
    sessionToEdit.value = null;
    showActivateMenuModal.value = true; 
};

const openEditSessionModal = (status, provider) => {
    selectedProviderForActivation.value = provider;
    activateModalMode.value = 'edit';
    sessionToEdit.value = status;
    showActivateMenuModal.value = true;
};

const openDeactivateMenuModal = (session, provider) => { 
    sessionToDeactivate.value = { ...session, provider_name: provider?.name || 'Proveedor' }; 
    showDeactivateMenuModal.value = true; 
};

const confirmDeactivation = () => {
    if (!sessionToDeactivate.value) return;
    router.patch(route('dashboard.providers.deactivate', sessionToDeactivate.value.provider_id), {
        date: new Date().toISOString().split('T')[0],
        meal_type: sessionToDeactivate.value.meal_type,
    }, { preserveScroll: true, onSuccess: () => { showDeactivateMenuModal.value = false; } });
};

// --- Session Deletion Logic ---
const showDeleteSessionModal = ref(false);
const sessionToDelete = ref(null);

const openDeleteSessionModal = (session, provider) => {
    sessionToDelete.value = { ...session, provider_name: provider.name };
    showDeleteSessionModal.value = true;
};

// --- Area Management in Monitoring ---
const removeAreaFromSession = (session, areaId) => {
    if (!confirm('¿Estás seguro de quitar esta área de la sesión activa?')) return;
    const currentAreas = Array.isArray(session.selected_area_ids) ? session.selected_area_ids : JSON.parse(session.selected_area_ids || '[]');
    const newAreas = currentAreas.filter(id => id !== areaId);
    router.patch(route('dashboard.sessions.updateAreas', session.id), { selected_area_ids: newAreas }, { preserveScroll: true });
};

const addAreaToSession = (session) => openEditSessionModal(session, session.provider);

// --- Diner Logic ---
const showPlaceOrderModal = ref(false);
const selectedMenuForOrder = ref(null);
const editingOrder = ref(null);
const menusForSelection = ref([]);

const openPlaceOrderModal = (menu) => {
    selectedMenuForOrder.value = menu;
    editingOrder.value = null;
    menusForSelection.value = [menu];
    showPlaceOrderModal.value = true;
};

const openEditOrderModal = (order) => {
    editingOrder.value = order;
    const sameMealTypeMenus = props.availableMenus.filter(m => m.meal_type === order.meal_type);
    const currentInList = sameMealTypeMenus.find(m => m.id === order.daily_menu_id);
    if (!currentInList) sameMealTypeMenus.unshift(order.daily_menu);
    menusForSelection.value = sameMealTypeMenus;
    selectedMenuForOrder.value = order.daily_menu;
    showPlaceOrderModal.value = true;
};

const isSessionOpenForMe = (mealType) => {
    return props.openSessions?.some(s => s.meal_type === mealType && s.selected_area_ids?.includes(parseInt(user.area_id)));
};

const groupedAvailableMenus = computed(() => {
    const groups = {};
    const filtered = props.availableMenus?.filter(m => !m.already_ordered) || [];
    filtered.forEach(menu => {
        if (!groups[menu.meal_type]) groups[menu.meal_type] = [];
        groups[menu.meal_type].push(menu);
    });
    return groups;
});

// --- Unified Color Helpers ---
const mealTypeColors = {
    'Desayuno': 'bg-amber-500 shadow-amber-200 dark:shadow-none',
    'Comida': 'bg-indigo-600 shadow-indigo-200 dark:shadow-none',
    'Cena': 'bg-purple-700 shadow-purple-200 dark:shadow-none',
    'Extra': 'bg-teal-600 shadow-teal-200 dark:shadow-none',
};

const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
    'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800',
    'Cena': 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800',
    'Extra': 'bg-teal-100 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-400 dark:border-teal-800',
};

const providerColors = [
    { border: 'border-indigo-200 dark:border-indigo-900/50', text: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-900/20' },
    { border: 'border-emerald-200 dark:border-emerald-900/50', text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
    { border: 'border-rose-200 dark:border-rose-900/50', text: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-50 dark:bg-rose-900/20' },
    { border: 'border-amber-200 dark:border-amber-900/50', text: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-900/20' },
    { border: 'border-cyan-200 dark:border-cyan-900/50', text: 'text-cyan-600 dark:text-cyan-400', bg: 'bg-cyan-50 dark:bg-cyan-900/20' },
    { border: 'border-fuchsia-200 dark:border-fuchsia-900/50', text: 'text-fuchsia-600 dark:text-fuchsia-400', bg: 'bg-fuchsia-50 dark:bg-fuchsia-900/20' },
];

const getProviderColor = (index) => providerColors[index % providerColors.length];

const mealTypeCardColors = {
    'Desayuno': 'bg-white/20 border-white/30 hover:bg-white/40',
    'Comida': 'bg-white/10 border-white/20 hover:bg-white/20',
    'Cena': 'bg-white/10 border-white/20 hover:bg-white/20',
    'Extra': 'bg-white/10 border-white/20 hover:bg-white/20',
};

// --- Area Manager Logic ---
const selectedOrderIds = ref({}); 
const showSubmitConfirmation = ref(false);
const pendingSubmissionMealType = ref('');

const toggleOrderSelection = (mealType, orderId) => {
    if (!selectedOrderIds.value[mealType]) selectedOrderIds.value[mealType] = [];
    const index = selectedOrderIds.value[mealType].indexOf(orderId);
    if (index > -1) selectedOrderIds.value[mealType].splice(index, 1);
    else selectedOrderIds.value[mealType].push(orderId);
};

const openSubmitConfirmation = (mealType) => {
    const ids = selectedOrderIds.value[mealType] || [];
    if (ids.length === 0) return alert('Por favor selecciona al menos un pedido para enviar.');
    pendingSubmissionMealType.value = mealType;
    showSubmitConfirmation.value = true;
};

const confirmSubmitAreaOrders = () => {
    const mealType = pendingSubmissionMealType.value;
    const ids = selectedOrderIds.value[mealType] || [];
    router.post(route('orders.areaSubmit'), {
        meal_type: mealType,
        order_ids: ids
    }, { 
        preserveScroll: true,
        onSuccess: () => {
            selectedOrderIds.value[mealType] = [];
            showSubmitConfirmation.value = false;
        }
    });
};

const submitSelectedOrders = (mealType) => openSubmitConfirmation(mealType);

const selectAllPending = (mealType) => {
    if (!selectedOrderIds.value[mealType]) selectedOrderIds.value[mealType] = [];
    const pendingOrders = props.teamOrders.map(m => m.orders.find(o => o.meal_type === mealType && o.status === 'submitted_by_user')).filter(Boolean);
    selectedOrderIds.value[mealType] = pendingOrders.map(o => o.id);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <!-- Status Color Indicator -->
                    <div v-if="$page.props.auth.orderStatus" 
                         class="h-4 w-4 rounded-full shadow-sm animate-pulse border-2 border-white dark:border-gray-800"
                         :class="{
                             'bg-red-500 shadow-red-200': $page.props.auth.orderStatus === 'red',
                             'bg-amber-500 shadow-amber-200': $page.props.auth.orderStatus === 'amber',
                             'bg-green-500 shadow-green-200': $page.props.auth.orderStatus === 'green'
                         }"
                         :title="$page.props.auth.orderStatus === 'red' ? 'Tienes pedidos pendientes por elegir' : ($page.props.auth.orderStatus === 'amber' ? 'Pedido pendiente de autorización' : 'Pedido enviado correctamente')">
                    </div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        {{ user.name }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded-lg border border-indigo-100 dark:border-indigo-800 uppercase tracking-widest">
                        {{ roleName }}
                    </span>
                    <span v-if="user.area_id" class="text-[10px] font-black text-gray-500 bg-gray-50 dark:bg-gray-900/30 px-3 py-1 rounded-lg border border-gray-100 dark:border-gray-800 uppercase tracking-widest">
                        {{ user.area?.name }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- BIENVENIDA -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100 dark:border-gray-700">
                    <div class="p-8 text-gray-900 dark:text-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <div class="relative">
                                <img :src="user.avatar_url" class="h-20 w-20 rounded-full border-4 border-indigo-50 dark:border-indigo-900/50 shadow-xl" alt="" />
                                <div class="absolute -bottom-1 -right-1 bg-green-500 h-5 w-5 rounded-full border-4 border-white dark:border-gray-800"></div>
                            </div>
                            <div>
                                <h3 class="text-3xl font-black tracking-tight">¡Hola de nuevo, {{ user.name.split(' ')[0] }}!</h3>
                                <p class="text-gray-500 dark:text-gray-400 font-medium mt-1">Hoy es {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MONITOREO EN TIEMPO REAL (SOLO ADQUISICIONES) -->
                <div v-if="user.role === 'acquisitions_manager' && openSessions.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-3xl border-l-8 border-indigo-500">
                    <div class="p-8">
                        <h4 class="text-lg font-black flex items-center mb-6 uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                            <span class="relative flex h-3 w-3 mr-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            Monitor de Recepción
                        </h4>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest text-[10px]">Sesiones Abiertas (Clic para ver):</h5>
                                <div v-for="session in openSessions" :key="session.id" 
                                     @click="selectedSessionId = session.id"
                                     class="p-5 bg-white dark:bg-gray-900 border-2 rounded-2xl flex flex-col space-y-4 transition-all cursor-pointer group"
                                     :class="selectedSessionId === session.id 
                                        ? 'border-indigo-500 shadow-xl ring-1 ring-indigo-500 bg-indigo-50/10' 
                                        : 'border-gray-100 dark:border-gray-800 hover:border-indigo-200 shadow-sm'">
                                    
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-black text-xl uppercase tracking-tighter"
                                               :class="[
                                                   selectedSessionId === session.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-800 dark:text-white',
                                                   !selectedSessionId ? (mealTypeColors[session.meal_type] ? 'text-indigo-600' : '') : ''
                                               ]">
                                                {{ session.meal_type }}
                                            </p>
                                            <div class="flex items-center mt-1 space-x-2">
                                                <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                                      :class="mealTypeTagColors[session.meal_type] || 'bg-gray-100 text-gray-600'">
                                                    {{ session.meal_type }}
                                                </span>
                                                <span class="text-xs font-bold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded">{{ activeTimers[session.id] || '00:00:00' }}</span>
                                            </div>
                                        </div>
                                        <div class="h-10 w-10 rounded-2xl flex items-center justify-center transition-all shadow-lg"
                                             :class="selectedSessionId === session.id ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-400'">
                                            <ChevronRightIcon v-if="selectedSessionId !== session.id" class="h-5 w-5" />
                                            <CheckBadgeIcon v-else class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <button @click.stop="openDeactivateMenuModal(session, session.provider)" 
                                            class="w-full py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all border border-red-200 dark:border-red-800">
                                        Finalizar {{ session.meal_type }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="activeSession" class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-indigo-200 dark:shadow-none flex flex-col transition-all duration-500">
                                <div class="flex justify-between items-start mb-8">
                                    <div>
                                        <h5 class="text-xs font-black uppercase tracking-[0.3em] opacity-80 text-indigo-100">Consolidado Confirmado</h5>
                                        <p class="text-3xl font-black uppercase tracking-tighter mt-1">{{ activeSession.meal_type }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2 bg-white/20 px-3 py-1 rounded-full border border-white/30">
                                        <div class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">{{ activeSession.provider?.name }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-8 items-center mb-8 bg-black/10 rounded-[2rem] p-6 border border-white/5">
                                    <div class="text-left border-r border-white/10 pr-4">
                                        <p class="text-[60px] font-black leading-none tracking-tighter">{{ activeTotalOrders }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] opacity-70 mt-2">Platillos Listos</p>
                                    </div>
                                    <div class="space-y-2 overflow-y-auto max-h-32 pr-2 custom-scrollbar">
                                        <div v-for="dish in activeDishSummary" :key="dish.name" class="flex justify-between items-center text-[10px] border-b border-white/5 pb-1 last:border-0">
                                            <span class="font-medium opacity-80 truncate mr-2">{{ dish.name }}</span>
                                            <span class="font-black bg-white text-indigo-600 h-5 min-w-[1.2rem] px-1.5 flex items-center justify-center rounded-lg shadow-sm">{{ dish.count }}</span>
                                        </div>
                                        <p v-if="activeDishSummary.length === 0" class="text-[10px] italic opacity-50 text-center py-4 tracking-widest uppercase">Esperando confirmaciones...</p>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Seguimiento de Áreas:</p>
                                        <button @click="addAreaToSession(activeSession)" class="text-[8px] font-black uppercase bg-white/20 hover:bg-white/30 px-2 py-1 rounded border border-white/10 transition-all flex items-center">
                                            <PlusIcon class="h-3 w-3 mr-1" stroke-width="3" /> Gestionar Áreas
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 overflow-y-auto max-h-48 pr-2 custom-scrollbar">
                                        <div v-for="areaStatus in activeSession.areas_status" :key="activeSession.id + '-' + areaStatus.id"
                                             class="flex items-center justify-between p-3 bg-white/10 rounded-2xl border border-white/5 transition-all group/area">
                                            <span class="text-[10px] font-black uppercase tracking-tight truncate mr-2">{{ areaStatus.name }}</span>
                                            <div class="flex items-center shrink-0">
                                                <button v-if="!areaStatus.is_submitted" 
                                                        @click.stop="removeAreaFromSession(activeSession, areaStatus.id)"
                                                        class="opacity-0 group-hover/area:opacity-100 p-1 hover:text-red-300 transition-all mr-1" title="Quitar área de esta sesión">
                                                    <XMarkIcon class="h-3 w-3" />
                                                </button>
                                                <CheckBadgeIcon v-if="areaStatus.is_submitted" class="h-4 w-4 text-green-300" />
                                                <ClockIcon v-else class="h-4 w-4 text-white/30 animate-pulse" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MÓDULO ADQUISICIONES -->
                <div v-if="user.role === 'acquisitions_manager'" class="space-y-6">
                    <div class="flex items-center justify-between mb-2 px-2">
                        <h4 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Proveedores y Sesiones</h4>
                        <Link :href="route('providers.create')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 uppercase tracking-widest">+ Nuevo Proveedor</Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(provider, index) in providers" :key="provider.id" 
                             class="bg-white dark:bg-gray-800 rounded-[2rem] p-8 border-2 shadow-xl shadow-gray-100 dark:shadow-none transition-all hover:scale-[1.02] flex flex-col"
                             :class="getProviderColor(index).border">
                            
                            <div class="flex justify-between items-center mb-1">
                                <p class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em]">
                                    {{ new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' }) }}
                                </p>
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                            </div>

                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-black text-2xl text-gray-900 dark:text-white leading-tight" :class="getProviderColor(index).text">{{ provider.name }}</h4>
                                <button @click="openActivateMenuModal(provider)" class="p-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 dark:shadow-none shrink-0 ml-2">
                                    <PlusIcon class="h-6 w-6" stroke-width="3" />
                                </button>
                            </div>

                            <!-- Información discreta del proveedor -->
                            <div class="mb-6 space-y-1">
                                <p v-if="provider.address" class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase truncate flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ provider.address }}
                                </p>
                                <div class="flex flex-wrap gap-x-3 gap-y-1">
                                    <p v-if="provider.contact_phone" class="text-[10px] text-gray-400 dark:text-gray-500 font-bold flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ provider.contact_phone }}
                                    </p>
                                    <p v-if="provider.contact_email" class="text-[10px] text-gray-400 dark:text-gray-500 font-bold flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        {{ provider.contact_email }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="space-y-4 mb-2 flex-1">
                                <div v-for="status in provider.dailyStatuses" :key="status.id" class="relative group/session">
                                    <div v-if="status.status === 'open'" class="p-1">
                                        <div class="flex gap-2">
                                            <button @click="openDeactivateMenuModal(status, provider)" 
                                                    class="flex-1 flex flex-col items-center justify-center p-6 text-white rounded-2xl hover:opacity-90 transition-all shadow-xl dark:shadow-none group"
                                                    :class="mealTypeColors[status.meal_type] || 'bg-red-600'">
                                                <span class="font-black text-lg uppercase leading-none">Cerrar {{ status.meal_type }}</span>
                                                <span class="text-[10px] font-bold opacity-70 uppercase tracking-widest mt-2">Finalizar recepción</span>
                                            </button>
                                            <div class="flex flex-col gap-2">
                                                <button @click="openDeleteSessionModal(status, provider)" 
                                                        class="p-3 bg-gray-100 dark:bg-gray-700 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-xl border border-gray-200 dark:border-gray-600 transition-all"
                                                        title="Eliminar sesión">
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                                <Link :href="route('admin.orders.send', { provider: provider.id, date: new Date().toISOString().split('T')[0], meal_type: status.meal_type })"
                                                      class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-800 hover:bg-indigo-600 hover:text-white transition-all"
                                                      title="Enviar Pedido (WhatsApp)">
                                                    <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700 rounded-2xl">
                                        <div>
                                            <span class="text-xs font-black uppercase block mb-0.5 px-2 py-0.5 rounded border inline-block" 
                                                  :class="mealTypeTagColors[status.meal_type] || 'text-gray-400 border-gray-200'">{{ status.meal_type }}</span>
                                            <span class="text-[10px] text-green-500 font-bold uppercase tracking-widest flex items-center mt-1">
                                                <CheckBadgeIcon class="h-3 w-3 mr-1" /> Finalizado
                                            </span>
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="openEditSessionModal(status, provider)" class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-xl transition-colors" title="Reabrir">
                                                <ClockIcon class="h-5 w-5" />
                                            </button>
                                            <Link :href="route('admin.orders.summary', { provider: provider.id, date: new Date().toISOString().split('T')[0], meal_type: status.meal_type })" 
                                                  class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-colors" title="Reporte">
                                                <ClipboardDocumentListIcon class="h-5 w-5" />
                                            </Link>
                                            <Link :href="route('admin.orders.send', { provider: provider.id, date: new Date().toISOString().split('T')[0], meal_type: status.meal_type })"
                                                  class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-xl transition-colors" title="Enviar de nuevo">
                                                <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                            </Link>
                                            <button @click="openDeleteSessionModal(status, provider)" 
                                                    class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-xl transition-colors"
                                                    title="Eliminar permanentemente">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="provider.dailyStatuses.length === 0" class="p-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl text-center">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Sin sesiones hoy</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MÓDULO FILTRO (AREA MANAGER) -->
                <div v-if="(user.role === 'area_manager' || teamOrders?.length > 0) && activeMealTypes?.length > 0" class="space-y-6">
                    <h4 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight px-2">Control de Pedidos del Área</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div v-for="mType in activeMealTypes" :key="mType" 
                             class="bg-white dark:bg-gray-800 rounded-3xl border-2 border-gray-100 dark:border-gray-700 overflow-hidden shadow-lg">
                            <div class="bg-indigo-600 p-6 text-white flex justify-between items-center">
                                <div>
                                    <h5 class="text-2xl font-black uppercase tracking-tighter">{{ mType }}</h5>
                                    <p class="text-[10px] font-bold opacity-80 tracking-widest uppercase">Seguimiento en tiempo real</p>
                                </div>
                                <div class="h-12 w-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20">
                                    <UserGroupIcon class="h-6 w-6" />
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <button @click="selectAllPending(mType)" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-700">Seleccionar todos los pendientes</button>
                                    <span class="text-[10px] font-black text-gray-400 uppercase">{{ teamOrders.length }} Colaboradores</span>
                                </div>

                                <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="member in teamOrders" :key="member.id" 
                                         class="flex items-center p-4 rounded-2xl border-2 transition-all"
                                         :class="member.orders.some(o => o.meal_type === mType) 
                                            ? 'border-green-100 bg-green-50/30 dark:border-green-900/30 dark:bg-green-900/10' 
                                            : 'border-red-50 bg-red-50/30 dark:border-red-900/20 dark:bg-red-900/10'">
                                        
                                        <div class="mr-4">
                                            <div v-if="member.orders.find(o => o.meal_type === mType && o.status === 'submitted_by_user')" class="flex items-center">
                                                <Checkbox 
                                                    :checked="selectedOrderIds[mType]?.includes(member.orders.find(o => o.meal_type === mType).id)"
                                                    @change="toggleOrderSelection(mType, member.orders.find(o => o.meal_type === mType).id)"
                                                />
                                            </div>
                                            <div v-else class="w-5 flex justify-center">
                                                <CheckBadgeIcon v-if="member.orders.some(o => o.meal_type === mType && o.status === 'submitted_by_manager')" class="h-5 w-5 text-green-500" />
                                                <div v-else class="h-5 w-5 rounded-full border-2 border-red-200 dark:border-red-900"></div>
                                            </div>
                                        </div>

                                        <img :src="member.avatar_url" class="h-10 w-10 rounded-full border-2 border-white shadow-sm mr-3" alt="" />
                                        
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-black truncate" :class="member.orders.some(o => o.meal_type === mType) ? 'text-gray-800 dark:text-gray-200' : 'text-red-600 dark:text-red-400'">
                                                {{ member.name }}
                                                <span v-if="member.id === user.id" class="ml-1 text-[8px] bg-indigo-100 text-indigo-600 px-1 rounded">TÚ</span>
                                            </p>
                                            <p v-if="member.orders.find(o => o.meal_type === mType)" class="text-[10px] font-bold text-gray-500 truncate">
                                                {{ member.orders.find(o => o.meal_type === mType).platillo }}
                                            </p>
                                            <p v-else class="text-[10px] font-black uppercase text-red-400 tracking-tighter">Sin pedido aún</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <button 
                                        @click="submitSelectedOrders(mType)"
                                        :disabled="!selectedOrderIds[mType]?.length"
                                        class="w-full py-4 rounded-2xl font-black uppercase tracking-widest text-sm transition-all shadow-xl"
                                        :class="selectedOrderIds[mType]?.length 
                                            ? 'bg-green-600 text-white hover:bg-green-700 shadow-green-100 dark:shadow-none' 
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200 dark:bg-gray-700 dark:border-gray-600'"
                                    >
                                        Enviar {{ selectedOrderIds[mType]?.length || 0 }} Pedidos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MÓDULO COMENSAL UNIFICADO -->
                <div v-if="user.role === 'diner' || user.role === 'area_manager' || (user.role === 'acquisitions_manager' && user.area_id)" class="space-y-6">
                    <h4 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight px-2">Mi Comedor</h4>
                    
                    <!-- MIS PEDIDOS DE HOY -->
                    <div v-if="myOrdersToday?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="order in myOrdersToday" :key="order.id" 
                             class="bg-white dark:bg-gray-800 border-2 rounded-3xl p-6 shadow-lg transition-all duration-500"
                             :class="isSessionOpenForMe(order.meal_type) 
                                ? 'border-green-400 dark:border-green-500 shadow-green-100 dark:shadow-none animate-glow-green' 
                                : 'border-red-200 dark:border-red-900 shadow-red-50 dark:shadow-none opacity-90'">
                            
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-2xl flex items-center justify-center text-white shadow-sm transition-colors duration-500"
                                         :class="isSessionOpenForMe(order.meal_type) ? (mealTypeColors[order.meal_type] || 'bg-indigo-600') : 'bg-red-400'">
                                        <CheckBadgeIcon v-if="isSessionOpenForMe(order.meal_type)" class="h-6 w-6" />
                                        <ClockIcon v-else class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                                  :class="mealTypeTagColors[order.meal_type] || 'bg-gray-100 text-gray-600'">
                                                {{ order.meal_type }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-widest"
                                              :class="isSessionOpenForMe(order.meal_type) ? 'text-green-500' : 'text-red-500'">
                                            {{ isSessionOpenForMe(order.meal_type) ? 'Sesión Abierta' : 'Pedido Bloqueado' }}
                                        </span>
                                    </div>
                                </div>
                                <button v-if="isSessionOpenForMe(order.meal_type)" 
                                        @click="openEditOrderModal(order)" 
                                        class="p-2 text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all hover:scale-110 active:scale-95"
                                        title="Editar pedido">
                                    <PencilSquareIcon class="h-6 w-6" />
                                </button>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter">{{ order.daily_menu.name }}</p>
                                <p v-if="order.preferences" class="text-xs text-gray-500 mt-1 italic">"{{ order.preferences }}"</p>
                            </div>
                            <div class="flex items-center text-[10px] font-black uppercase tracking-widest" 
                                 :class="order.status === 'submitted_by_manager' ? 'text-green-600' : 'text-orange-500'">
                                <div class="h-2 w-2 rounded-full mr-2" 
                                     :class="order.status === 'submitted_by_manager' ? 'bg-green-500' : 'bg-orange-500 animate-pulse'"></div>
                                {{ order.status === 'submitted_by_manager' ? 'Enviado a Cocina' : 'Esperando Filtro de Área' }}
                            </div>
                        </div>
                    </div>

                    <!-- SELECCIÓN DE MENÚ -->
                    <div v-if="Object.keys(groupedAvailableMenus).length > 0" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <div v-for="(menus, mType) in groupedAvailableMenus" :key="mType" 
                                 class="rounded-[2.5rem] p-6 text-white shadow-2xl relative overflow-hidden flex flex-col"
                                 :class="mealTypeColors[mType] || 'bg-gray-600'">
                                
                                <div class="absolute top-0 right-0 p-8 opacity-10 scale-125">
                                    <ClockIcon class="h-24 w-24" />
                                </div>

                                <div class="relative z-10 mb-6">
                                    <h4 class="text-2xl font-black uppercase tracking-tighter">{{ mType }}</h4>
                                    <p class="text-[10px] font-bold opacity-70 uppercase tracking-widest">Opciones disponibles</p>
                                </div>

                                <div class="space-y-3 relative z-10 flex-1">
                                    <div v-for="menu in menus" :key="menu.id" 
                                         class="p-4 backdrop-blur-md border rounded-2xl transition-all cursor-pointer group flex justify-between items-center"
                                         :class="mealTypeCardColors[mType] || 'bg-white/10 border-white/20'"
                                         @click="openPlaceOrderModal(menu)">
                                        <div class="flex-1 min-w-0 mr-3">
                                            <h5 class="text-sm font-black truncate">{{ menu.name }}</h5>
                                            <p class="text-[10px] opacity-70 italic line-clamp-1">{{ menu.description }}</p>
                                        </div>
                                        <div class="h-8 w-8 bg-white rounded-full flex items-center justify-center text-indigo-600 shadow-lg shrink-0 group-hover:scale-110 transition-all">
                                            <PlusIcon class="h-5 w-5" stroke-width="3" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HISTORIAL RECIENTE INTEGRADO -->
                    <div v-if="orderHistory?.length > 0" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-3xl p-8 border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Mis últimos pedidos</h4>
                            <Link :href="route('justification.index')" class="text-[9px] font-black text-indigo-600 uppercase tracking-widest hover:underline">Ver todo el historial</Link>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div v-for="h in orderHistory" :key="h.id" class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                                <span class="text-[8px] font-black uppercase text-indigo-500 block mb-1">{{ h.meal_type }}</span>
                                <p class="font-bold text-xs text-gray-800 dark:text-gray-200 truncate">{{ h.daily_menu.name }}</p>
                                <p class="text-[8px] text-gray-400 mt-1 font-bold">{{ new Date(h.created_at).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <!-- MODALS -->
        <template v-if="user.role === 'acquisitions_manager' || user.role === 'admin'">
            <ActivateMenuModal 
                :show="showActivateMenuModal" 
                :provider="selectedProviderForActivation" 
                :areas="areas" 
                :allSessions="allSessionsToday"
                :initialMode="activateModalMode"
                :initialSession="sessionToEdit"
                @close="showActivateMenuModal = false" 
            />
            <DeactivateMenuConfirmationModal :show="showDeactivateMenuModal" :provider="sessionToDeactivate" :todayOrdersByArea="[]" @close="showDeactivateMenuModal = false" @confirm="confirmDeactivation" />
            <DeleteSessionModal 
                :show="showDeleteSessionModal"
                :session="sessionToDelete"
                @close="showDeleteSessionModal = false"
            />
        </template>

        <template v-if="user.role === 'area_manager'">
            <SubmitOrdersConfirmationModal 
                :show="showSubmitConfirmation"
                :mealType="pendingSubmissionMealType"
                :count="selectedOrderIds[pendingSubmissionMealType]?.length || 0"
                @close="showSubmitConfirmation = false"
                @confirm="confirmSubmitAreaOrders"
            />
        </template>

        <PlaceOrderModal 
            :show="showPlaceOrderModal" 
            :menu="selectedMenuForOrder" 
            :existingOrder="editingOrder" 
            :availableOptions="menusForSelection"
            @close="showPlaceOrderModal = false" 
        />

    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.2);
    border-radius: 10px;
}

@keyframes glow-green {
    0%, 100% { border-color: rgb(74, 222, 128); box-shadow: 0 0 5px rgba(74, 222, 128, 0.2); }
    50% { border-color: rgb(34, 197, 94); box-shadow: 0 0 20px rgba(34, 197, 94, 0.4); }
}

.animate-glow-green {
    animation: glow-green 2s infinite;
}
</style>
