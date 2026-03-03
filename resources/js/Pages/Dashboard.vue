<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ActivateMenuModal from '@/Pages/Admin/Partials/ActivateMenuModal.vue';
import DeactivateMenuConfirmationModal from '@/Pages/Admin/Partials/DeactivateMenuConfirmationModal.vue';
import SubmitOrdersConfirmationModal from '@/Pages/Admin/Partials/SubmitOrdersConfirmationModal.vue';
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
    ChatBubbleLeftRightIcon,
    BuildingStorefrontIcon,
    InformationCircleIcon,
    ClipboardDocumentCheckIcon,
    DocumentIcon
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
    closedTodaySessions: { type: Array, default: () => [] },
    allOpenSessionsToday: { type: Array, default: () => [] },
    allSessionsToday: { type: Array, default: () => [] },
    // Diner Props
    myOrdersToday: { type: Array, default: () => [] },
    availableMenus: Array,
    orderHistory: Array,
    pendingAuthorizations: { type: Array, default: () => [] },
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
    // FIX: Filter by BOTH meal_type and provider_id to distinguish between different providers of the same meal type
    const summary = props.dishSummaryToday?.find(s => 
        s.meal_type === activeSession.value.meal_type && 
        s.provider_id === activeSession.value.provider_id
    );
    return summary?.dishes || [];
});

const activeTotalOrders = computed(() => {
    if (!activeSession.value) return 0;
    // FIX: Filter by BOTH meal_type and provider_id
    const summary = props.dishSummaryToday?.find(s => 
        s.meal_type === activeSession.value.meal_type && 
        s.provider_id === activeSession.value.provider_id
    );
    return summary?.total || 0;
});

// --- Timer Logic ---
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
    }, 5000);
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

// --- Area Management ---
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

const isSessionClosedToday = (mealType) => {
    return props.closedTodaySessions?.some(s => s.meal_type === mealType && s.selected_area_ids?.includes(parseInt(user.area_id)));
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
    { border: 'border-indigo-500', text: 'text-indigo-600', bg: 'bg-indigo-50', icon: 'bg-indigo-600' },
    { border: 'border-emerald-500', text: 'text-emerald-600', bg: 'bg-emerald-50', icon: 'bg-emerald-600' },
    { border: 'border-rose-500', text: 'text-rose-600', bg: 'bg-rose-50', icon: 'bg-rose-600' },
    { border: 'border-amber-500', text: 'text-amber-600', bg: 'bg-amber-50', icon: 'bg-amber-600' },
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
    const pendingOrderIds = props.teamOrders
        .map(member => member.orders.find(o => o.meal_type === mealType && o.status === 'submitted_by_user'))
        .filter(Boolean)
        .map(o => o.id);
    selectedOrderIds.value[mealType] = pendingOrderIds;
};

// --- Session Authorization Logic ---
const authorizedUserIds = ref({}); 
const processingAuthorizations = ref({}); 
const authStatus = ref({}); 

watch(() => props.teamOrders, (newTeam) => {
    if (!newTeam) return;
    const newAuths = {};
    props.openSessions.forEach(session => {
        if (authStatus.value[session.id] !== 'dirty') {
            newAuths[session.id] = newTeam
                .filter(m => m.authorized_sessions?.includes(session.id))
                .map(m => m.id);
        } else {
            newAuths[session.id] = authorizedUserIds.value[session.id] || [];
        }
    });
    authorizedUserIds.value = newAuths;
}, { immediate: true, deep: true });

const hasDirtyAuths = computed(() => {
    return Object.values(authStatus.value).some(status => status === 'dirty');
});

const toggleUserAuthorization = (sessionId, userId) => {
    if (!authorizedUserIds.value[sessionId]) authorizedUserIds.value[sessionId] = [];
    const index = authorizedUserIds.value[sessionId].indexOf(userId);
    if (index > -1) authorizedUserIds.value[sessionId].splice(index, 1);
    else authorizedUserIds.value[sessionId].push(userId);
    authStatus.value[sessionId] = 'dirty'; 
};

const saveAuthorizations = (sessionId) => {
    processingAuthorizations.value[sessionId] = true;
    router.post(route('orders.authorizeDiners'), {
        provider_daily_status_id: sessionId,
        user_ids: authorizedUserIds.value[sessionId] || []
    }, { 
        preserveScroll: true,
        onSuccess: () => {
            processingAuthorizations.value[sessionId] = false;
            authStatus.value[sessionId] = 'saved';
            setTimeout(() => { authStatus.value[sessionId] = null; }, 3000);
        },
        onError: () => {
            processingAuthorizations.value[sessionId] = false;
        }
    });
};

const selectAllForAuth = (sessionId) => {
    authorizedUserIds.value[sessionId] = props.teamOrders.map(m => m.id);
    authStatus.value[sessionId] = 'dirty';
};

const deselectAllForAuth = (sessionId) => {
    authorizedUserIds.value[sessionId] = [];
    authStatus.value[sessionId] = 'dirty';
};

// --- Navigation ---
const activeTab = ref('global'); 

onMounted(() => {
    if (user.role === 'area_manager' || user.role === 'diner') {
        activeTab.value = 'my-area';
    }
});

const hasPendingActionsInMyArea = computed(() => {
    const sessionsForMyArea = props.openSessions?.filter(s => s.is_open_for_my_area) || [];
    const hasPendingOrders = props.teamOrders?.some(member => member.orders?.some(o => o.status === 'submitted_by_user'));
    const hasSessionsNeedingTeamAuth = sessionsForMyArea.some(s => (s.authorized_count || 0) <= 1);
    return (sessionsForMyArea.length > 0 && hasSessionsNeedingTeamAuth) || hasPendingOrders;
});

const hasAnyTeamOrders = computed(() => {
    // Check if any team member has at least one order regardless of status
    return props.teamOrders?.some(member => member.orders && member.orders.length > 0);
});

// Format time
const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <!-- CABECERA UNIFICADA -->
                <div class="flex items-center gap-4">
                    <div class="relative group">
                        <img :src="user.avatar_url" class="h-12 w-12 rounded-full border-2 border-indigo-500 shadow-md object-cover transition-transform group-hover:scale-105" />
                        <div v-if="$page.props.auth.orderStatus" 
                             class="absolute -top-1 -right-1 h-4 w-4 rounded-full border-2 border-white dark:border-gray-800 animate-pulse"
                             :class="{
                                 'bg-red-500': $page.props.auth.orderStatus === 'red',
                                 'bg-amber-500': $page.props.auth.orderStatus === 'amber',
                                 'bg-green-500': $page.props.auth.orderStatus === 'green'
                             }">
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="font-black text-xl text-gray-800 dark:text-gray-100 uppercase tracking-tight leading-none">
                                {{ user.name }}
                            </h2>
                            <span class="text-[7px] font-black text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-1.5 py-0.5 rounded border border-indigo-100 uppercase tracking-widest">{{ roleName }}</span>
                        </div>
                        <div class="flex items-center gap-3 mt-1">
                            <span v-if="user.area_id" class="text-[8px] font-black text-gray-400 uppercase tracking-widest flex items-center">
                                <BuildingOfficeIcon class="h-2.5 w-2.5 mr-1" /> {{ user.area?.name }}
                            </span>
                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest flex items-center border-l border-gray-200 dark:border-gray-700 pl-2">
                                <CalendarDaysIcon class="h-2.5 w-2.5 mr-1" /> {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'short' }) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- TAB SWITCHER -->
                <div v-if="user.area_id && (user.role === 'acquisitions_manager' || user.role === 'admin')" class="flex bg-gray-100 dark:bg-gray-900 p-1 rounded-xl border border-gray-200 dark:border-gray-800">
                    <button @click="activeTab = 'global'" 
                            class="px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2"
                            :class="activeTab === 'global' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        <BuildingStorefrontIcon class="h-3.5 w-3.5" /> Monitor
                    </button>
                    <button @click="activeTab = 'my-area'" 
                            class="px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2 relative"
                            :class="[
                                activeTab === 'my-area' ? 'bg-white dark:bg-gray-800 text-emerald-600 shadow-sm' : 'text-gray-500 hover:text-gray-700',
                                activeTab !== 'my-area' && hasPendingActionsInMyArea ? 'animate-pulse bg-emerald-50 dark:bg-emerald-900/10' : ''
                            ]">
                        <UserGroupIcon class="h-3.5 w-3.5" /> Mi Área
                        <span v-if="hasPendingActionsInMyArea && activeTab !== 'my-area'" class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 shadow-sm"></span>
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-[85%] mx-auto sm:px-6 lg:px-8 space-y-10">
                
                <!-- TAB GLOBAL -->
                <div v-if="activeTab === 'global'" class="space-y-10">
                    <div v-if="(user.role === 'acquisitions_manager' || user.role === 'admin') && openSessions.length > 0" class="bg-white dark:bg-gray-800 rounded-[2.5rem] border-l-8 border-indigo-500 shadow-xl p-8">
                        <h4 class="text-sm font-black flex items-center mb-10 uppercase tracking-[0.3em] text-indigo-600">
                            <span class="relative flex h-4 w-4 mr-4"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span><span class="relative inline-flex rounded-full h-4 w-4 bg-green-500 shadow-sm"></span></span>
                            Gestión de Alimentos en Vivo
                        </h4>
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                            <!-- Columna 30%: Sesiones -->
                            <div class="lg:col-span-4 space-y-4">
                                <div v-for="session in openSessions" :key="session.id" @click="selectedSessionId = session.id" 
                                     class="p-6 bg-white dark:bg-gray-900 border-2 rounded-[2.5rem] flex flex-col space-y-4 cursor-pointer transition-all relative" 
                                     :class="selectedSessionId === session.id ? 'border-indigo-500 shadow-2xl bg-indigo-50/5' : 'border-gray-100 hover:border-indigo-300 opacity-80'">
                                    
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-black text-2xl uppercase tracking-tighter leading-none" :class="selectedSessionId === session.id ? 'text-indigo-600' : 'text-gray-800'">{{ session.meal_type }}</p>
                                            <div class="flex items-center mt-3 gap-2">
                                                <span class="text-[9px] font-black px-3 py-1 rounded-lg border uppercase tracking-widest" :class="mealTypeTagColors[session.meal_type]">{{ session.meal_type }}</span>
                                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest flex items-center bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded-lg"><ClockIcon class="h-3 w-3 mr-1" /> {{ formatTime(session.activated_at) }}</span>
                                            </div>
                                        </div>
                                        <div class="h-10 w-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 transition-all" :class="selectedSessionId === session.id ? 'bg-indigo-600 text-white shadow-lg' : ''">
                                            <ChevronRightIcon class="h-5 w-5" />
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-4xl font-black tabular-nums tracking-tighter" :class="selectedSessionId === session.id ? 'text-indigo-600' : 'text-gray-400'">
                                            {{ activeTimers[session.id] || '00:00:00' }}
                                        </p>
                                        <p class="text-[8px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Tiempo de Servicio</p>
                                    </div>

                                    <button @click.stop="openDeactivateMenuModal(session, session.provider)" class="w-full py-3 bg-red-50 text-red-600 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-100">Finalizar Servicio</button>
                                </div>
                            </div>

                            <!-- Columna 70%: Detalle Global -->
                            <div v-if="activeSession" class="lg:col-span-8 bg-indigo-600 rounded-[3rem] p-8 text-white shadow-2xl flex flex-col transition-all">
                                <div class="flex justify-between items-start mb-6">
                                    <div><h5 class="text-[10px] font-black uppercase tracking-[0.4em] opacity-70 mb-1">Monitor Consolidado</h5><p class="text-3xl font-black uppercase tracking-tighter leading-none">{{ activeSession.meal_type }}</p></div>
                                    <div class="bg-white/20 px-5 py-2 rounded-xl border border-white/30 text-[10px] font-black uppercase tracking-[0.2em] backdrop-blur-xl">{{ activeSession.provider?.name }}</div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center mb-6 bg-black/10 rounded-[2.5rem] p-8 border border-white/10">
                                    <div class="text-left border-r border-white/10 pr-6 col-span-1"><p class="text-[70px] font-black leading-none tracking-tighter">{{ activeTotalOrders }}</p><p class="text-[10px] font-black uppercase tracking-[0.4em] opacity-60 mt-3 leading-none">Platillos Listos</p></div>
                                    <div class="space-y-3 overflow-y-auto max-h-40 pr-4 custom-scrollbar col-span-2">
                                        <div class="grid grid-cols-2 gap-x-6">
                                            <div v-for="dish in activeDishSummary" :key="dish.name" class="flex justify-between items-center text-[11px] border-b border-white/10 pb-2 mb-2 last:border-0"><span class="font-bold truncate mr-3">{{ dish.name }}</span><span class="font-black bg-white text-indigo-600 h-6 min-w-[1.8rem] px-1.5 flex items-center justify-center rounded-lg shadow-lg">{{ dish.count }}</span></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-4">
                                        <p class="text-[10px] font-black uppercase tracking-[0.4em] opacity-60">Seguimiento de Dependencias:</p>
                                        <button @click="addAreaToSession(activeSession)" class="text-[9px] font-black uppercase bg-amber-400 hover:bg-amber-500 text-amber-950 px-5 py-2 rounded-xl border-b-4 border-amber-600 transition-all shadow-lg active:border-b-0 active:translate-y-1">
                                            Gestionar Áreas
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5 gap-3 overflow-y-auto max-h-72 pr-3 custom-scrollbar">
                                        <div v-for="areaStatus in activeSession.areas_status" :key="areaStatus.id" 
                                             class="flex items-center justify-between p-3 rounded-2xl border transition-all hover:scale-[1.02] hover:shadow-lg active:scale-95 cursor-pointer group/area"
                                             :class="areaStatus.is_submitted 
                                                ? 'bg-emerald-500/40 border-emerald-400/50 shadow-lg shadow-emerald-900/20' 
                                                : 'bg-white/10 border-white/10 hover:bg-white/20'"
                                             @click="!areaStatus.is_submitted && !areaStatus.is_pending ? removeAreaFromSession(activeSession, areaStatus.id) : null">
                                            <div class="flex-1 min-w-0 mr-2">
                                                <span class="text-[9px] font-black uppercase truncate block tracking-tight leading-none"
                                                      :class="areaStatus.is_submitted ? 'text-white' : 'text-white/90'">
                                                    {{ areaStatus.name }}
                                                </span>
                                                <span class="text-[8px] font-bold tracking-widest block mt-1.5 leading-none"
                                                      :class="areaStatus.is_submitted ? 'text-emerald-100' : 'opacity-60 text-white'">
                                                    {{ areaStatus.submitted_count }} / {{ areaStatus.order_count }} enviadas
                                                </span>
                                            </div>
                                            <div class="flex items-center shrink-0">
                                                <CheckBadgeIcon v-if="areaStatus.is_submitted" class="h-5 w-5 text-green-300 drop-shadow-lg" />
                                                <div v-else-if="areaStatus.is_pending" class="h-5 w-5 rounded-full border-2 border-amber-300 flex items-center justify-center animate-pulse">
                                                    <div class="h-1 w-1 bg-amber-300 rounded-full shadow-sm"></div>
                                                </div>
                                                <div v-else class="text-white/40 group-hover/area:text-red-300 transition-colors">
                                                    <TrashIcon class="h-5 w-5" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Catálogo de Proveedores Premium -->
                    <div v-if="user.role === 'acquisitions_manager' || user.role === 'admin'" class="space-y-10">
                        <div class="flex items-center justify-between px-4 border-b dark:border-gray-700 pb-6">
                            <h4 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Proveedores y Sesiones</h4>
                            <Link :href="route('providers.create')" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:bg-indigo-700 transition-all">+ Nuevo Proveedor</Link>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                            <div v-for="(provider, index) in providers" :key="provider.id" class="flex flex-col gap-4 group">
                                <!-- Tarjeta Botón de Proveedor -->
                                <div @click="openActivateMenuModal(provider)" 
                                     class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border-2 shadow-xl flex flex-col cursor-pointer transition-all hover:scale-[1.03] active:scale-95 relative overflow-hidden"
                                     :class="getProviderColor(index).border">
                                    
                                    <div class="absolute -right-4 -top-4 h-24 w-24 opacity-5 rounded-full blur-2xl" :class="getProviderColor(index).icon"></div>
                                    
                                    <div class="flex flex-col items-center text-center space-y-4 relative z-10">
                                        <div class="h-16 w-16 rounded-3xl flex items-center justify-center text-white shadow-xl" :class="getProviderColor(index).icon">
                                            <BuildingStorefrontIcon class="h-8 w-8" />
                                        </div>
                                        <div>
                                            <h4 class="font-black text-2xl text-gray-900 dark:text-white leading-tight tracking-tighter uppercase mb-1">{{ provider.name }}</h4>
                                            <p class="text-[8px] font-black text-indigo-500 uppercase tracking-[0.2em]">Click para Nueva Sesión</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sección de Sesiones Debajo -->
                                <div v-if="provider.dailyStatuses?.length > 0" class="space-y-3 px-2">
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-4">Sesiones de Hoy:</p>
                                    <div v-for="status in provider.dailyStatuses" :key="status.id" 
                                         class="bg-white dark:bg-gray-800/50 rounded-3xl p-5 border shadow-md flex flex-col gap-4 border-gray-100 dark:border-gray-700">
                                        
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-3">
                                                <span class="text-[9px] font-black uppercase px-3 py-1 rounded-lg border bg-gray-50 dark:bg-gray-900 shadow-sm" :class="mealTypeTagColors[status.meal_type]">{{ status.meal_type }}</span>
                                                <span v-if="status.status === 'open'" class="flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                                                <span v-else class="text-[8px] font-black text-gray-400 uppercase tracking-widest flex items-center"><CheckBadgeIcon class="h-3 w-3 mr-1 text-green-500" /> Finalizado</span>
                                            </div>
                                            <div class="flex gap-1.5">
                                                <!-- Reporte WhatsApp -->
                                                <Link :href="route('admin.orders.send', { provider: provider.id, date: new Date().toISOString().split('T')[0], meal_type: status.meal_type })" 
                                                      class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all" title="Ver y Enviar Reporte">
                                                    <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                                </Link>
                                                <!-- Reporte PDF -->
                                                <a :href="route('admin.orders.summary.pdf', { provider: provider.id, date: new Date().toISOString().split('T')[0], meal_type: status.meal_type })" 
                                                   target="_blank"
                                                   class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all" title="Descargar PDF">
                                                    <DocumentIcon class="h-5 w-5" />
                                                </a>
                                                <!-- Borrar -->
                                                <button @click="openDeleteSessionModal(status, provider)" 
                                                        class="p-2 text-gray-400 hover:text-red-600 rounded-xl transition-all" title="Eliminar Sesión">
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="status.status === 'open'">
                                            <button @click="openDeactivateMenuModal(status, provider)" 
                                                    class="w-full py-2.5 bg-red-50 text-red-600 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-100">
                                                Cerrar {{ status.meal_type }}
                                            </button>
                                        </div>
                                        <div v-else>
                                            <button @click="openEditSessionModal(status, provider)" 
                                                    class="w-full py-2.5 bg-indigo-50 text-indigo-600 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100 flex items-center justify-center gap-2">
                                                <ClockIcon class="h-3.5 w-3.5" /> Reabrir / Gestionar Áreas
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="px-6 py-4 text-center">
                                    <p class="text-[9px] font-bold text-gray-300 uppercase tracking-widest italic">Sin sesiones activas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB MY AREA -->
                <div v-if="activeTab === 'my-area'" class="space-y-10">
                    <!-- AVISO -->
                    <div v-if="pendingAuthorizations?.length > 0" class="bg-emerald-50 dark:bg-emerald-900/10 border-2 border-emerald-200 dark:border-emerald-800 rounded-3xl p-8 shadow-lg shadow-emerald-50/50">
                        <div class="flex items-center space-x-6">
                            <div class="bg-white dark:bg-emerald-900/30 p-4 rounded-2xl border border-emerald-200 dark:border-emerald-800 shadow-sm"><ClockIcon class="h-10 w-10 text-emerald-600 animate-pulse" /></div>
                            <div><h5 class="text-xl font-black text-emerald-800 dark:text-emerald-400 uppercase tracking-tight leading-none">Servicio Iniciado</h5><p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-500 uppercase mt-2 tracking-widest">Habilita a tu equipo en el panel de abajo para desbloquear el menú.</p></div>
                        </div>
                    </div>

                    <!-- AUTORIZACIÓN (FULL WIDTH) -->
                    <div v-if="(user.role === 'area_manager' || (user.area_id && (user.role === 'admin' || user.role === 'acquisitions_manager'))) && openSessions.length > 0" class="space-y-6">
                        <h4 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-tight px-2">Habilitar Comensales para Hoy</h4>
                        <div v-for="session in openSessions" :key="'auth-' + session.id" class="bg-white dark:bg-gray-800 rounded-3xl border-2 border-indigo-100 dark:border-indigo-900/50 overflow-hidden shadow-xl">
                            <div class="px-8 py-5 text-white flex justify-between items-center" :class="mealTypeColors[session.meal_type] || 'bg-indigo-600'">
                                <div class="flex items-center gap-4"><div class="h-12 w-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20"><UserIcon class="h-6 w-6" /></div><div><h5 class="text-xl font-black uppercase tracking-tighter leading-none">{{ session.meal_type }}</h5><p class="text-[8px] font-bold opacity-80 uppercase mt-1">Gestión de acceso: {{ session.provider?.name }}</p></div></div>
                                <div class="flex gap-2"><button @click="selectAllForAuth(session.id)" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl text-[8px] font-black uppercase tracking-widest transition-all">Todos</button><button @click="deselectAllForAuth(session.id)" class="px-4 py-2 bg-black/10 hover:bg-black/20 rounded-xl text-[8px] font-black uppercase tracking-widest transition-all">Ninguno</button></div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3 mb-6">
                                    <div v-for="member in teamOrders" :key="'auth-member-' + session.id + '-' + member.id" @click="toggleUserAuthorization(session.id, member.id)" class="flex items-center p-3 rounded-2xl border-2 transition-all cursor-pointer relative min-h-[4rem] shadow-sm hover:scale-[1.02]" :class="authorizedUserIds[session.id]?.includes(member.id) ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-500 text-emerald-700' : 'bg-gray-50 dark:bg-gray-900/50 border-transparent text-gray-400 opacity-60'">
                                        <img :src="member.avatar_url" class="h-8 w-8 rounded-full border border-white dark:border-gray-700 mr-2.5 object-cover shadow-sm" /><div class="flex-1 min-w-0"><p class="text-[9px] font-black uppercase leading-tight truncate">{{ member.name }}</p></div>
                                        <div v-if="authorizedUserIds[session.id]?.includes(member.id)" class="absolute -top-1.5 -right-1.5 bg-emerald-500 text-white rounded-full p-0.5 shadow-md"><CheckBadgeIcon class="h-3 w-3" /></div>
                                    </div>
                                </div>
                                <div class="flex justify-center"><button @click="saveAuthorizations(session.id)" :disabled="processingAuthorizations[session.id]" class="px-10 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all shadow-lg flex items-center justify-center gap-3" :class="[authStatus[session.id] === 'saved' ? 'bg-green-500 text-white shadow-green-100' : authStatus[session.id] === 'dirty' ? 'bg-amber-500 text-white animate-pulse' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-indigo-100']"><template v-if="processingAuthorizations[session.id]">Guardando...</template><template v-else-if="authStatus[session.id] === 'saved'"><CheckBadgeIcon class="h-4 w-4" />¡Guardado!</template><template v-else><CheckBadgeIcon class="h-4 w-4" /> {{ authStatus[session.id] === 'dirty' ? 'Guardar Cambios' : 'Actualizar Habilitados' }}</template></button></div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTROL PEDIDOS (DYNAMIC VISIBILITY) -->
                    <div v-if="hasAnyTeamOrders" class="space-y-6">
                        <div class="flex items-center gap-2 px-2"><ClipboardDocumentListIcon class="h-5 w-5 text-indigo-500" /><h4 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-tight">Pedidos del Equipo Pendientes</h4></div>
                        <div v-for="mType in ['Desayuno', 'Comida', 'Cena', 'Extra']" :key="mType">
                            <div v-if="teamOrders.some(m => m.orders.some(o => o.meal_type === mType))" class="bg-white dark:bg-gray-800 rounded-3xl border-2 border-gray-50 dark:border-gray-700 overflow-hidden shadow-xl mb-6">
                                <div class="bg-gray-900 px-8 py-5 text-white flex justify-between items-center border-b border-white/5"><div><h5 class="text-xl font-black uppercase tracking-tight">{{ mType }}</h5><p class="text-[8px] font-bold opacity-50 uppercase tracking-widest mt-1">Estatus de solicitudes de tu dependencia</p></div><div class="bg-white/10 px-4 py-1.5 rounded-xl border border-white/10 text-[9px] font-black uppercase">{{ teamOrders.filter(m => m.orders.some(o => o.meal_type === mType)).length }} Pedidos</div></div>
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-6"><button @click="selectAllPending(mType)" class="text-[9px] font-black uppercase text-indigo-600 hover:text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 px-4 py-2 rounded-xl transition-all shadow-sm">✓ Firmar todos los pendientes</button></div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 max-h-[400px] overflow-y-auto pr-3 custom-scrollbar">
                                        <div v-for="member in teamOrders" :key="member.id" class="flex items-center p-4 rounded-2xl border-2 transition-all shadow-sm" :class="member.orders.some(o => o.meal_type === mType) ? 'border-green-100 bg-green-50/10 dark:bg-green-900/10' : 'border-gray-50 bg-gray-50/10 opacity-40 grayscale'">
                                            <div class="mr-3"><div v-if="member.orders.find(o => o.meal_type === mType && o.status === 'submitted_by_user')" class="flex items-center"><Checkbox :checked="selectedOrderIds[mType]?.includes(member.orders.find(o => o.meal_type === mType).id)" @change="toggleOrderSelection(mType, member.orders.find(o => o.meal_type === mType).id)" class="h-5 w-5" /></div><div v-else class="w-5 flex justify-center"><CheckBadgeIcon v-if="member.orders.some(o => o.meal_type === mType && o.status === 'submitted_by_manager')" class="h-5 w-5 text-green-500" /><div v-else class="h-5 w-5 rounded-full border-2 border-red-100"></div></div></div>
                                            <img :src="member.avatar_url" class="h-10 w-10 rounded-full border border-white mr-3 object-cover shadow-sm" /><div class="flex-1 min-w-0"><p class="text-[10px] font-black truncate uppercase tracking-tight" :class="member.orders.some(o => o.meal_type === mType) ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400'">{{ member.name }}</p><p v-if="member.orders.find(o => o.meal_type === mType)" class="text-[9px] font-bold text-indigo-600 dark:text-indigo-400 truncate italic mt-1 leading-none">"{{ member.orders.find(o => o.meal_type === mType).platillo }}"</p></div>
                                        </div>
                                    </div>
                                    <div class="mt-8 flex justify-center"><button @click="submitSelectedOrders(mType)" :disabled="!selectedOrderIds[mType]?.length" class="px-16 py-4 bg-green-600 hover:bg-green-700 text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] transition-all shadow-xl shadow-green-100 disabled:opacity-30">Enviar {{ selectedOrderIds[mType]?.length || 0 }} a Cocina</button></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MI COMEDOR (GRID WIDE) -->
                    <div v-if="user.role === 'diner' || user.role === 'area_manager' || (user.role === 'acquisitions_manager' && user.area_id)" class="space-y-8 pt-8 border-t-2 border-gray-100 dark:border-gray-800">
                        <div class="flex justify-between items-center px-2">
                            <h4 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Mi Comedor Personal</h4>
                            <div v-if="hasDirtyAuths" class="px-6 py-2.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-2xl text-[9px] font-black uppercase tracking-widest border-2 border-amber-200 dark:border-amber-800 animate-pulse flex items-center gap-3 shadow-sm">
                                <InformationCircleIcon class="h-4 w-4" /> Habilita a tu equipo primero para desbloquear tu menú
                            </div>
                        </div>
                        <div v-if="!hasDirtyAuths" class="w-full space-y-8">
                            <div v-if="myOrdersToday?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                <div v-for="order in myOrdersToday" :key="order.id" class="bg-white dark:bg-gray-800 border-2 rounded-[2rem] p-6 shadow-lg transition-all" :class="isSessionOpenForMe(order.meal_type) ? 'border-green-400 animate-glow-green' : 'border-red-100 opacity-90'">
                                    <div class="flex justify-between items-start mb-6"><div class="flex items-center space-x-4"><div class="h-12 w-12 rounded-2xl flex items-center justify-center text-white shadow-lg" :class="isSessionOpenForMe(order.meal_type) ? (mealTypeColors[order.meal_type] || 'bg-indigo-600') : 'bg-red-400'"><CheckBadgeIcon v-if="isSessionOpenForMe(order.meal_type)" class="h-7 w-7" /><ClockIcon v-else class="h-7 w-7" /></div><div><div class="flex items-center gap-2 mb-1"><span class="text-[9px] font-black px-3 py-1 rounded-lg border uppercase shadow-sm tracking-widest" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span></div><span class="text-[9px] font-black uppercase tracking-widest" :class="isSessionOpenForMe(order.meal_type) ? 'text-green-500' : 'text-red-500'">{{ isSessionOpenForMe(order.meal_type) ? 'Servicio Abierto' : 'Bloqueado' }}</span></div></div><button v-if="isSessionOpenForMe(order.meal_type)" @click="openEditOrderModal(order)" class="p-2.5 text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all shadow-sm border border-gray-50 dark:border-gray-700"><PencilSquareIcon class="h-6 w-6" /></button></div>
                                    <div class="mb-6"><p class="text-lg font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tight leading-tight">{{ order.daily_menu.name }}</p><p v-if="order.preferences" class="text-[10px] text-gray-500 dark:text-gray-400 mt-3 italic font-medium leading-relaxed bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border-l-2 border-indigo-500">"{{ order.preferences }}"</p></div>

                                    <div class="flex items-center text-[10px] font-black uppercase tracking-widest" :class="order.status === 'submitted_by_manager' ? 'text-green-600' : 'text-orange-500'"><div class="h-2 w-2 rounded-full mr-3" :class="order.status === 'submitted_by_manager' ? 'bg-green-500 shadow-sm shadow-green-200' : 'bg-orange-500 animate-pulse shadow-sm shadow-orange-200'"></div>{{ order.status === 'submitted_by_manager' ? 'Enviado a Cocina' : 'Esperando Firma de Área' }}</div>
                                </div>
                            </div>
                            <div v-for="(menus, mType) in groupedAvailableMenus" :key="mType" class="rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden flex flex-col min-h-[300px] transition-all" :class="mealTypeColors[mType] || 'bg-gray-600'">
                                <div class="relative z-10 mb-8"><h4 class="text-3xl font-black uppercase tracking-tighter leading-none">{{ mType }}</h4><p class="text-[9px] font-bold opacity-70 uppercase tracking-[0.3em] mt-3">Platillos disponibles hoy:</p></div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 relative z-10 flex-1">
                                    <div v-for="menu in menus" :key="menu.id" @click="openPlaceOrderModal(menu)" class="p-6 backdrop-blur-xl border rounded-[2rem] cursor-pointer group flex justify-between items-center transition-all hover:scale-[1.02] hover:bg-white/20 hover:border-white shadow-md" :class="mealTypeCardColors[mType] || 'bg-white/10 border-white/20'"><div class="flex-1 min-w-0 mr-6"><h5 class="text-[13px] font-black truncate uppercase tracking-tight">{{ menu.name }}</h5><p class="text-[9px] opacity-80 italic line-clamp-1 mt-1 font-medium">{{ menu.description || 'Consulta ingredientes.' }}</p></div><div class="h-9 w-9 bg-white rounded-xl flex items-center justify-center text-indigo-600 shadow-xl group-hover:scale-110 transition-all border border-white/50"><PlusIcon class="h-5 w-5" stroke-width="3" /></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODALS -->
        <template v-if="user.role === 'acquisitions_manager' || user.role === 'admin'">
            <ActivateMenuModal :show="showActivateMenuModal" :provider="selectedProviderForActivation" :areas="areas" :allSessions="allSessionsToday" :initialMode="activateModalMode" :initialSession="sessionToEdit" @close="showActivateMenuModal = false" />
            <DeactivateMenuConfirmationModal :show="showDeactivateMenuModal" :provider="sessionToDeactivate" :todayOrdersByArea="[]" @close="showDeactivateMenuModal = false" @confirm="confirmDeactivation" />
            <DeleteSessionModal :show="showDeleteSessionModal" :session="sessionToDelete" @close="showDeleteSessionModal = false" />
        </template>
        <template v-if="user.role === 'area_manager' || user.role === 'acquisitions_manager' || user.role === 'admin'">
            <SubmitOrdersConfirmationModal :show="showSubmitConfirmation" :mealType="pendingSubmissionMealType" :count="selectedOrderIds[pendingSubmissionMealType]?.length || 0" @close="showSubmitConfirmation = false" @confirm="confirmSubmitAreaOrders" />
        </template>
        <PlaceOrderModal :show="showPlaceOrderModal" :menu="selectedMenuForOrder" :existingOrder="editingOrder" :availableOptions="menusForSelection" @close="showPlaceOrderModal = false" />
    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.2); }
@keyframes glow-green { 0%, 100% { border-color: rgb(74, 222, 128); box-shadow: 0 0 10px rgba(74, 222, 128, 0.2); } 50% { border-color: rgb(34, 197, 94); box-shadow: 0 0 30px rgba(34, 197, 94, 0.4); } }
.animate-glow-green { animation: glow-green 2s infinite; }
</style>
