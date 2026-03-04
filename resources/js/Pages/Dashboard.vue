<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import ActivateMenuModal from '@/Pages/Admin/Partials/ActivateMenuModal.vue';
import DeactivateMenuConfirmationModal from '@/Pages/Admin/Partials/DeactivateMenuConfirmationModal.vue';
import SubmitOrdersConfirmationModal from '@/Pages/Admin/Partials/SubmitOrdersConfirmationModal.vue';
import DeleteSessionModal from '@/Pages/Admin/Partials/DeleteSessionModal.vue';
import PlaceOrderModal from '@/Pages/Admin/Partials/PlaceOrderModal.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { 
    CheckBadgeIcon, ClockIcon, UserGroupIcon, ClipboardDocumentListIcon, PencilSquareIcon, PlusIcon, UserIcon, 
    ChevronRightIcon, TrashIcon, XMarkIcon, CalendarDaysIcon, ListBulletIcon, ChatBubbleLeftRightIcon,
    BuildingStorefrontIcon, InformationCircleIcon, ClipboardDocumentCheckIcon, DocumentIcon, BuildingOfficeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    auth: Object, userRole: String, providers: Array, areas: Array, submittedAreasToday: Array,
    dishSummaryToday: Array, totalOrdersToday: Number, openSessions: Array, closedTodaySessions: Array,
    allOpenSessionsToday: Array, allSessionsToday: Array, myOrdersToday: Array, availableMenus: Array,
    orderHistory: Array, pendingAuthorizations: Array, teamOrders: Array, area: Object,
    activeMealTypes: Array, historicalSessions: Array,
});

const user = props.auth.user;

// --- STATE DEFINITIONS (TOP LEVEL TO AVOID INITIALIZATION ERRORS) ---
const activeTab = ref('global'); 
const sidebarMode = ref('auth'); 
const selectedSessionForAuth = ref(null);
const visitedModes = ref(new Set(['auth']));
const selectedSessionId = ref(null);
const activeTimers = ref({});
const authorizedUserIds = ref({}); 
const processingAuthorizations = ref({}); 
const authStatus = ref({});
const selectedOrderIds = ref({}); 
const showSubmitConfirmation = ref(false);
const pendingSubmissionMealType = ref('');

// --- WATCHERS & COMPUTEDS ---
watch(() => props.openSessions, (sessions) => {
    if (sessions?.length > 0) {
        if (!selectedSessionId.value || !sessions.find(s => s.id === selectedSessionId.value)) {
            selectedSessionId.value = sessions[0].id;
        }
        if (!selectedSessionForAuth.value) {
            selectedSessionForAuth.value = sessions[0].id;
        }
    } else {
        selectedSessionId.value = null;
    }
}, { immediate: true });

const activeSession = computed(() => props.openSessions?.find(s => s.id === selectedSessionId.value) || props.openSessions?.[0]);
const activeDishSummary = computed(() => (props.dishSummaryToday?.find(s => s.meal_type === activeSession.value?.meal_type && s.provider_id === activeSession.value?.provider_id))?.dishes || []);
const activeTotalOrders = computed(() => (props.dishSummaryToday?.find(s => s.meal_type === activeSession.value?.meal_type && s.provider_id === activeSession.value?.provider_id))?.total || 0);

const activeAuthSession = computed(() => props.openSessions?.find(s => s.id === selectedSessionForAuth.value) || props.openSessions?.[0]);

// --- Intelligent Workflow Logic ---
watch(sidebarMode, (newVal) => { visitedModes.value.add(newVal); });

const shouldGlowAuth = computed(() => {
    if (sidebarMode.value === 'auth') return false;
    const sessionsForMyArea = props.openSessions?.filter(s => s.is_open_for_my_area) || [];
    return sessionsForMyArea.some(s => (s.authorized_count || 0) <= 1) && !visitedModes.value.has('auth');
});

const shouldGlowMenu = computed(() => {
    if (sidebarMode.value === 'menu') return false;
    const isAuthorized = props.openSessions?.some(s => authorizedUserIds.value[s.id]?.includes(user.id));
    return isAuthorized && props.myOrdersToday?.length === 0 && !visitedModes.value.has('menu');
});

const pendingSubmissionCount = computed(() => {
    let count = 0;
    props.teamOrders?.forEach(m => {
        count += m.orders?.filter(o => o.status === 'submitted_by_user').length || 0;
    });
    return count;
});

const isSubmissionReady = computed(() => {
    if (!activeAuthSession.value) return false;
    const s = activeAuthSession.value;
    const authCount = s.authorized_count || 0;
    if (authCount === 0) return false;
    const orders = props.teamOrders?.filter(m => 
        m.orders?.some(o => o.meal_type === s.meal_type && (o.status === 'submitted_by_user' || o.status === 'submitted_by_manager'))
    ).length || 0;
    return orders >= authCount && authCount > 0;
});

const hasPendingActionsInMyArea = computed(() => {
    return shouldGlowAuth.value || pendingSubmissionCount.value > 0;
});

// --- Timer & Refresh Logic ---
const updateTimers = () => {
    const now = new Date();
    props.openSessions?.forEach(session => {
        if (session.activated_at) {
            const diff = Math.floor((now - new Date(session.activated_at)) / 1000);
            activeTimers.value[session.id] = `${Math.floor(diff / 3600).toString().padStart(2, '0')}:${Math.floor((diff % 3600) / 60).toString().padStart(2, '0')}:${(diff % 60).toString().padStart(2, '0')}`;
        }
    });
};

let timerInterval, refreshInterval;
onMounted(() => {
    updateTimers(); timerInterval = setInterval(updateTimers, 1000);
    refreshInterval = setInterval(() => { router.reload({ preserveScroll: true, only: ['providers', 'dishSummaryToday', 'openSessions', 'myOrdersToday', 'availableMenus', 'teamOrders'] }); }, 5000);
    if (user.role === 'area_manager' || user.role === 'diner') { activeTab.value = 'my-area'; }
});
onUnmounted(() => { clearInterval(timerInterval); clearInterval(refreshInterval); });

// --- Modals & Actions ---
const roleName = { 'admin': 'Administrador', 'acquisitions_manager': 'Adquisiciones', 'area_manager': 'Gerente de Área', 'diner': 'Comensal' }[user.role];
const showActivateMenuModal = ref(false), selectedProviderForActivation = ref(null), activateModalMode = ref('new'), sessionToEdit = ref(null);
const openActivateMenuModal = (provider) => { selectedProviderForActivation.value = provider; activateModalMode.value = 'new'; sessionToEdit.value = null; showActivateMenuModal.value = true; };
const openEditSessionModal = (status, provider) => { selectedProviderForActivation.value = provider; activateModalMode.value = 'edit'; sessionToEdit.value = status; showActivateMenuModal.value = true; };
const showDeactivateMenuModal = ref(false), sessionToDeactivate = ref(null);
const openDeactivateMenuModal = (session, provider) => { sessionToDeactivate.value = { ...session, provider_name: provider?.name }; showDeactivateMenuModal.value = true; };
const confirmDeactivation = () => { router.patch(route('dashboard.providers.deactivate', sessionToDeactivate.value.provider_id), { date: new Date().toISOString().split('T')[0], meal_type: sessionToDeactivate.value.meal_type }, { preserveScroll: true, onSuccess: () => { showDeactivateMenuModal.value = false; } }); };
const showDeleteSessionModal = ref(false), sessionToDelete = ref(null);
const openDeleteSessionModal = (session, provider) => { sessionToDelete.value = { ...session, provider_name: provider.name }; showDeleteSessionModal.value = true; };

const removeAreaFromSession = (session, areaId) => {
    if (!confirm('¿Quitar área?')) return;
    const currentAreas = Array.isArray(session.selected_area_ids) ? session.selected_area_ids : JSON.parse(session.selected_area_ids || '[]');
    const newAreas = currentAreas.filter(id => parseInt(id) !== parseInt(areaId));
    router.patch(route('dashboard.sessions.updateAreas', session.id), { selected_area_ids: newAreas }, { preserveScroll: true });
};

// --- Diner / Menu Logic ---
const showPlaceOrderModal = ref(false), selectedMenuForOrder = ref(null), editingOrder = ref(null), menusForSelection = ref([]);
const openPlaceOrderModal = (menu) => { selectedMenuForOrder.value = menu; editingOrder.value = null; menusForSelection.value = [menu]; showPlaceOrderModal.value = true; };
const openEditOrderModal = (order) => { editingOrder.value = order; menusForSelection.value = props.availableMenus.filter(m => m.meal_type === order.meal_type); selectedMenuForOrder.value = order.daily_menu; showPlaceOrderModal.value = true; };

const isSessionOpenForMe = (mealType) => props.openSessions?.some(s => s.meal_type === mealType && (s.is_open_for_my_area || s.selected_area_ids?.includes(parseInt(user.area_id))));
const hasOpenSessionsForArea = computed(() => props.openSessions?.some(s => s.is_open_for_my_area || s.selected_area_ids?.includes(parseInt(user.area_id))));

const groupedAvailableMenus = computed(() => {
    const groups = {}; if (!props.availableMenus) return groups;
    props.availableMenus.filter(m => !m.already_ordered).forEach(m => { if (!groups[m.meal_type]) groups[m.meal_type] = []; groups[m.meal_type].push(m); });
    return groups;
});
const hasMenus = computed(() => Object.keys(groupedAvailableMenus.value).length > 0);

// --- Authorizations Logic (Auto-Save) ---
watch(() => props.teamOrders, (newTeam) => { 
    if (!newTeam) return; 
    props.openSessions?.forEach(s => { 
        if (authStatus.value[s.id] !== 'dirty') {
            authorizedUserIds.value[s.id] = newTeam.filter(m => m.authorized_sessions?.includes(s.id)).map(m => m.id); 
        }
    }); 
}, { immediate: true, deep: true });

const toggleUserAuthorization = (sId, uId) => { 
    if (!authorizedUserIds.value[sId]) authorizedUserIds.value[sId] = []; 
    const idx = authorizedUserIds.value[sId].indexOf(uId); 
    if (idx > -1) authorizedUserIds.value[sId].splice(idx, 1); 
    else authorizedUserIds.value[sId].push(uId); 
    authStatus.value[sId] = 'dirty';
    saveAuthorizations(sId);
};

const saveAuthorizations = (sId) => { 
    processingAuthorizations.value[sId] = true; 
    router.post(route('orders.authorizeDiners'), { provider_daily_status_id: sId, user_ids: authorizedUserIds.value[sId] || [] }, { 
        preserveScroll: true, 
        onSuccess: () => { 
            processingAuthorizations.value[sId] = false; 
            authStatus.value[sId] = 'saved'; 
            setTimeout(() => { authStatus.value[sId] = null; }, 3000); 
        } 
    }); 
};

const selectAllForAuth = (sessionId) => {
    authorizedUserIds.value[sessionId] = props.teamOrders.map(m => m.id);
    saveAuthorizations(sessionId);
};

const deselectAllForAuth = (sessionId) => {
    authorizedUserIds.value[sessionId] = [];
    saveAuthorizations(sessionId);
};

// --- Colors & Themes ---
const mealTypeColors = { 'Desayuno': 'bg-amber-500 shadow-amber-900/40', 'Comida': 'bg-indigo-600 shadow-indigo-900/40', 'Cena': 'bg-purple-700 shadow-purple-900/40', 'Extra': 'bg-teal-600 shadow-teal-900/40' };
const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200', 'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200', 'Cena': 'bg-purple-100 text-purple-700 border-purple-200', 'Extra': 'bg-teal-100 text-teal-700 border-teal-200' };
const mealTypeCardColors = { 'Desayuno': 'bg-white/20 border-white/30 hover:bg-white/40', 'Comida': 'bg-white/10 border-white/20 hover:bg-white/20', 'Cena': 'bg-white/10 border-white/20 hover:bg-white/20', 'Extra': 'bg-white/10 border-white/20 hover:bg-white/20' };

const providerThemeColors = [
    'bg-indigo-600 shadow-indigo-900/40', 'bg-emerald-600 shadow-emerald-900/40',
    'bg-rose-600 shadow-rose-900/40', 'bg-amber-600 shadow-amber-900/40',
];
const getProviderTheme = (id) => providerThemeColors[id % providerThemeColors.length];

const providerCardColors = [
    { border: 'border-indigo-500', icon: 'bg-indigo-600' }, { border: 'border-emerald-500', icon: 'bg-emerald-600' },
    { border: 'border-rose-500', icon: 'bg-rose-600' }, { border: 'border-amber-500', icon: 'bg-amber-600' },
];
const getProviderCardStyle = (idx) => providerCardColors[idx % providerCardColors.length];

// --- Order Submission ---
const toggleOrderSelection = (mType, id) => { if (!selectedOrderIds.value[mType]) selectedOrderIds.value[mType] = []; const idx = selectedOrderIds.value[mType].indexOf(id); if (idx > -1) selectedOrderIds.value[mType].splice(idx, 1); else selectedOrderIds.value[mType].push(id); };
const confirmSubmitAreaOrders = () => { router.post(route('orders.areaSubmit'), { meal_type: pendingSubmissionMealType.value, order_ids: selectedOrderIds.value[pendingSubmissionMealType.value] }, { preserveScroll: true, onSuccess: () => { selectedOrderIds.value[pendingSubmissionMealType.value] = []; showSubmitConfirmation.value = false; } }); };
const selectAllPending = (mType) => { selectedOrderIds.value[mType] = props.teamOrders.map(m => m.orders.find(o => o.meal_type === mType && o.status === 'submitted_by_user')).filter(Boolean).map(o => o.id); };

const formatTimeShort = (d) => d ? new Date(d).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) : '';
const hasAnyTeamOrders = computed(() => props.teamOrders?.some(m => m.orders?.length > 0));
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center px-4">
                <div class="flex items-center gap-6">
                    <div class="relative group">
                        <img :src="user.avatar_url" class="h-14 w-14 rounded-full border-2 border-indigo-500 shadow-2xl object-cover transition-transform group-hover:scale-110" />
                        <div v-if="$page.props.auth.orderStatus" class="absolute -top-1 -right-1 h-5 w-5 rounded-full border-2 border-white animate-pulse shadow-md" :class="{'bg-red-500': $page.props.auth.orderStatus === 'red','bg-amber-500': $page.props.auth.orderStatus === 'amber','bg-green-500': $page.props.auth.orderStatus === 'green'}"></div>
                    </div>
                    <div>
                        <div class="flex items-center gap-4">
                            <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 uppercase tracking-tighter leading-none">{{ user.name }}</h2>
                            <span class="text-[8px] font-black text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded-lg border border-indigo-100 uppercase tracking-widest">{{ roleName }}</span>
                        </div>
                        <div class="flex items-center gap-4 mt-2">
                            <span v-if="user.area_id" class="text-[9px] font-black text-gray-400 uppercase tracking-widest flex items-center bg-gray-50 dark:bg-gray-900 px-2 py-1 rounded-md">
                                <BuildingOfficeIcon class="h-3 w-3 mr-1.5" /> {{ user.area?.name }}
                            </span>
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest flex items-center border-l pl-4">
                                <CalendarDaysIcon class="h-3 w-3 mr-1.5" /> {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'short' }) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div v-if="user.area_id && (user.role === 'acquisitions_manager' || user.role === 'admin')" class="flex bg-gray-100 dark:bg-gray-900 p-1.5 rounded-[1.5rem] border shadow-inner">
                    <button @click="activeTab = 'global'" 
                            class="px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                            :class="activeTab === 'global' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-md' : 'text-gray-500 hover:text-gray-700'">
                        Monitor Global
                    </button>
                    <button @click="activeTab = 'my-area'" 
                            class="px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all relative"
                            :class="[
                                activeTab === 'my-area' ? 'bg-white dark:bg-gray-800 text-emerald-600 shadow-md' : 'text-gray-500 hover:text-gray-700',
                                activeTab !== 'my-area' && hasPendingActionsInMyArea ? 'animate-pulse bg-emerald-50' : ''
                            ]">
                        Mi Área
                        <div v-if="hasPendingActionsInMyArea && activeTab !== 'my-area'" class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full animate-ping shadow-sm"></div>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-[85%] mx-auto space-y-12">
                
                <!-- TAB MONITOR GLOBAL -->
                <div v-if="activeTab === 'global'" class="space-y-12">
                    <div v-if="(user.role === 'acquisitions_manager' || user.role === 'admin') && openSessions.length > 0" class="bg-white dark:bg-gray-800 rounded-[3.5rem] border-l-[12px] border-indigo-500 shadow-2xl p-10">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                            <!-- Sesiones (30%) -->
                            <div class="lg:col-span-4 space-y-6">
                                <div v-for="session in openSessions" :key="session.id" @click="selectedSessionId = session.id" 
                                     class="p-8 border-2 rounded-[3.5rem] flex flex-col space-y-6 cursor-pointer transition-all relative" 
                                     :class="selectedSessionId === session.id ? 'border-indigo-500 shadow-2xl scale-[1.02] bg-indigo-50/5' : 'border-gray-100 opacity-70 hover:opacity-100 hover:border-indigo-200'">
                                    <div class="flex justify-between items-start">
                                        <p class="font-black text-3xl uppercase tracking-tighter">{{ session.meal_type }}</p>
                                        <div class="h-12 w-12 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-400 shadow-inner" :class="selectedSessionId === session.id ? 'bg-indigo-600 text-white shadow-indigo-200' : ''">
                                            <ChevronRightIcon class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <p class="text-5xl font-black tabular-nums tracking-tighter text-indigo-600">{{ activeTimers[session.id] || '00:00:00' }}</p>
                                    <button @click.stop="openDeactivateMenuModal(session, session.provider)" class="w-full py-4 bg-red-50 text-red-600 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest border border-red-100 shadow-sm">Finalizar Servicio</button>
                                </div>
                            </div>
                            <!-- Monitor Central (70%) -->
                            <div v-if="activeSession" class="lg:col-span-8 rounded-[4rem] p-10 text-white shadow-2xl flex flex-col transition-all duration-700" :class="getProviderTheme(activeSession.provider_id)">
                                <div class="flex justify-between items-start mb-10">
                                    <div><h5 class="text-[11px] font-black uppercase tracking-[0.5em] opacity-70 mb-2">Monitor Consolidado</h5><p class="text-4xl font-black uppercase tracking-tighter leading-none">{{ activeSession.provider?.name }}</p></div>
                                    <div class="bg-white/20 px-6 py-3 rounded-2xl border border-white/30 text-[11px] font-black uppercase tracking-widest backdrop-blur-2xl shadow-xl">{{ activeSession.meal_type }}</div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-10 items-center mb-10 bg-black/10 rounded-[3rem] p-10 border border-white/10 shadow-inner">
                                    <div class="text-left border-r border-white/10 pr-10 col-span-1"><p class="text-[80px] font-black leading-none tracking-tighter">{{ activeTotalOrders }}</p><p class="text-[11px] font-black uppercase opacity-60 mt-4 leading-none">Platillos</p></div>
                                    <div class="col-span-2 space-y-4 max-h-48 overflow-y-auto pr-6 custom-scrollbar">
                                        <div v-for="dish in activeDishSummary" :key="dish.name" class="flex justify-between items-center text-base border-b border-white/10 pb-3 last:border-0"><span class="font-bold truncate mr-4">{{ dish.name }}</span><span class="font-black bg-white text-gray-900 px-3 py-1 rounded-xl shadow-2xl">{{ dish.count }}</span></div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-6"><p class="text-[11px] font-black uppercase tracking-[0.5em] opacity-60">Dependencias en Seguimiento:</p><button @click="openEditSessionModal(activeSession, activeSession.provider)" class="text-[10px] font-black uppercase bg-white/20 hover:bg-white/30 text-white px-6 py-2.5 rounded-2xl border border-white/20 shadow-xl transition-all">Gestionar Áreas</button></div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-4 overflow-y-auto max-h-72 pr-4 custom-scrollbar">
                                        <div v-for="areaS in activeSession.areas_status" :key="areaS.id" class="p-4 rounded-[1.5rem] border border-white/20 text-[10px] font-black uppercase flex justify-between items-center transition-all hover:scale-[1.05] cursor-pointer group/area shadow-sm" :class="areaS.is_submitted ? 'bg-emerald-500/40 border-emerald-400 shadow-emerald-900/20' : 'bg-white/10 border-white/10 hover:bg-white/20'" @click="!areaS.is_submitted && !areaS.is_pending ? removeAreaFromSession(activeSession, areaS.id) : null">
                                            <div class="flex-1 min-w-0 mr-3"><span class="truncate block leading-tight">{{ areaS.name }}</span><span class="text-[8px] opacity-60 block mt-1.5">{{ areaS.submitted_count }}/{{ areaS.order_count }} pedidos</span></div>
                                            <div class="shrink-0"><CheckBadgeIcon v-if="areaS.is_submitted" class="h-6 w-6 text-green-300 drop-shadow-lg" /><div v-else-if="areaS.is_pending" class="h-4 w-4 rounded-full border-2 border-amber-300 flex items-center justify-center animate-pulse shadow-sm"><div class="h-1.5 w-1.5 bg-amber-300 rounded-full"></div></div><div v-else class="text-white/40 group-hover/area:text-red-300 transition-colors"><TrashIcon class="h-5 w-5" /></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catálogo Proveedores -->
                    <div v-if="user.role === 'acquisitions_manager' || user.role === 'admin'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                        <div v-for="(provider, idx) in providers" :key="provider.id" class="flex flex-col gap-6">
                            <div @click="openActivateMenuModal(provider)" class="bg-white dark:bg-gray-800 rounded-[3.5rem] p-10 border-2 shadow-2xl cursor-pointer hover:scale-[1.03] transition-all text-center flex-1 relative overflow-hidden" :class="getProviderCardStyle(idx).border">
                                <div class="absolute -right-6 -top-6 h-32 w-32 opacity-5 rounded-full blur-3xl" :class="getProviderCardStyle(idx).icon"></div>
                                <div class="h-20 w-20 rounded-[2rem] flex items-center justify-center text-white mx-auto mb-8 shadow-2xl" :class="getProviderCardStyle(idx).icon"><BuildingStorefrontIcon class="h-10 w-10" /></div>
                                <h4 class="font-black text-2xl uppercase tracking-tighter text-gray-900 dark:text-white leading-tight mb-2">{{ provider.name }}</h4>
                                <p class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em]">Iniciar Sesión</p>
                            </div>
                            <div v-for="status in provider.dailyStatuses" :key="status.id" class="bg-white dark:bg-gray-800 p-6 rounded-[3rem] border shadow-xl space-y-5 border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black px-4 py-1.5 rounded-xl border uppercase tracking-widest shadow-sm" :class="mealTypeTagColors[status.meal_type]">{{ status.meal_type }}</span>
                                    <div class="flex gap-2">
                                        <Link :href="route('admin.orders.send', { provider: provider.id, date: new Date().toISOString().split('T')[0], meal_type: status.meal_type })" class="p-3 text-indigo-600 hover:bg-indigo-50 rounded-2xl transition-all shadow-sm border border-indigo-50"><ChatBubbleLeftRightIcon class="h-5 w-5" /></Link>
                                        <button @click="openDeleteSessionModal(status, provider)" class="p-3 text-gray-400 hover:text-red-600 rounded-2xl transition-all"><TrashIcon class="h-5 w-5" /></button>
                                    </div>
                                </div>
                                <button v-if="status.status === 'open'" @click="openDeactivateMenuModal(status, provider)" class="w-full py-4 bg-red-50 text-red-600 rounded-[1.5rem] text-[10px] font-black uppercase border border-red-100 shadow-sm">Finalizar</button>
                                <button v-else @click="openEditSessionModal(status, provider)" class="w-full py-4 bg-indigo-50 text-indigo-600 rounded-[1.5rem] text-[10px] font-black uppercase border border-indigo-100 shadow-sm flex items-center justify-center gap-3"><ClockIcon class="h-4 w-4" /> Reabrir</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB MI ÁREA (MANAGER SIDEBAR 25/75 PREMIUM) -->
                <div v-if="activeTab === 'my-area' && user.role !== 'diner'" class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                    <!-- Sidebar (25%) -->
                    <div class="lg:col-span-3 space-y-8">
                        <div class="bg-white dark:bg-gray-800 rounded-[3.5rem] p-8 shadow-2xl border border-gray-100 sticky top-24">
                            <h4 class="text-[11px] font-black uppercase tracking-[0.4em] text-gray-400 mb-10 px-4">Panel de Control</h4>
                            <div class="space-y-5">
                                <button v-for="mode in [{id:'auth',l:'Habilitar',s:'Personal',i:UserIcon,c:'indigo'},{id:'submit',l:'Enviar',s:'A Cocina',i:ClipboardDocumentCheckIcon,c:'emerald'},{id:'menu',l:'Mi Menú',s:'Personal',i:BuildingStorefrontIcon,c:'amber'},{id:'justification',l:'Justificar',s:'Historial',i:PencilSquareIcon,c:'rose'}]" :key="mode.id" @click="sidebarMode = mode.id" 
                                class="w-full p-6 rounded-[2.5rem] border-2 transition-all flex items-center gap-6 text-left group shadow-sm relative overflow-hidden active:scale-95" 
                                :class="[
                                    sidebarMode === mode.id ? `border-${mode.c}-500 bg-${mode.c}-50 shadow-xl scale-[1.02] z-10` : 'border-transparent hover:bg-gray-50',
                                    mode.id === 'auth' && shouldGlowAuth ? 'animate-glow-indigo border-indigo-400 shadow-indigo-200' : '',
                                    mode.id === 'menu' && shouldGlowMenu ? 'animate-glow-amber border-amber-400 shadow-amber-200' : ''
                                ]">
                                    <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center transition-all shadow-lg group-hover:scale-110" :class="sidebarMode === mode.id ? `bg-${mode.c}-600 text-white shadow-${mode.c}-900/30` : 'bg-gray-100 text-gray-400'">
                                        <component :is="mode.i" class="h-8 w-8" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[15px] font-black uppercase tracking-tight" :class="sidebarMode === mode.id ? `text-${mode.c}-600` : 'text-gray-500'">{{ mode.l }}</p>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1.5 leading-none">{{ mode.s }}</p>
                                    </div>
                                    <!-- Badges & Workflow Alerts -->
                                    <div v-if="mode.id === 'submit' && pendingSubmissionCount > 0" 
                                         class="h-7 min-w-[1.8rem] px-2 flex items-center justify-center rounded-xl text-[11px] font-black transition-all shadow-md"
                                         :class="isSubmissionReady ? 'bg-emerald-500 text-white animate-bounce' : 'bg-amber-100 text-amber-600 animate-pulse'">
                                        {{ pendingSubmissionCount }}
                                    </div>
                                    <div v-if="(mode.id === 'auth' && shouldGlowAuth) || (mode.id === 'menu' && shouldGlowMenu)" class="absolute top-4 right-4 h-3 w-3 rounded-full bg-red-500 shadow-2xl animate-ping"></div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content (75%) -->
                    <div class="lg:col-span-9">
                        <!-- SECCIÓN: AUTORIZACIÓN (GERENTE) -->
                        <div v-if="sidebarMode === 'auth' && activeAuthSession" class="bg-white dark:bg-gray-800 rounded-[4rem] border shadow-2xl overflow-hidden transition-all duration-700">
                            <div class="px-12 py-10 text-white flex justify-between items-center shadow-2xl" :class="getProviderTheme(activeAuthSession.provider_id)">
                                <div class="flex items-center gap-10">
                                    <div class="h-20 w-20 bg-white/20 rounded-[2.5rem] flex items-center justify-center backdrop-blur-3xl border border-white/20 shadow-inner"><UserIcon class="h-10 w-10 text-white" /></div>
                                    <div><h5 class="text-4xl font-black uppercase tracking-tighter leading-none mb-3">Habilitar Personal</h5><p class="text-[11px] font-bold opacity-80 uppercase tracking-widest">{{ activeAuthSession.meal_type }} • {{ activeAuthSession.provider?.name }}</p></div>
                                </div>
                                <div class="flex gap-4"><button @click="selectAllForAuth(activeAuthSession.id)" class="px-8 py-3.5 bg-white/20 hover:bg-white/30 rounded-[1.5rem] text-[11px] font-black uppercase tracking-widest shadow-xl transition-all">Todos</button><button @click="deselectAllForAuth(activeAuthSession.id)" class="px-8 py-3.5 bg-black/10 hover:bg-black/20 rounded-[1.5rem] text-[11px] font-black uppercase tracking-widest transition-all">Ninguno</button></div>
                            </div>
                            <div class="p-12">
                                <p class="text-[11px] font-black uppercase tracking-[0.4em] text-gray-400 mb-10 text-center">Toca el nombre para autorizar automáticamente:</p>
                                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                                                                            <div v-for="m in teamOrders" :key="'auth-' + activeAuthSession.id + '-' + m.id" @click="toggleUserAuthorization(activeAuthSession.id, m.id)" 
                                                                             class="flex items-center p-4 rounded-[2.5rem] border-2 transition-all cursor-pointer relative group shadow-sm hover:scale-[1.05]" 
                                                                             :class="authorizedUserIds[activeAuthSession.id]?.includes(m.id) ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-emerald-500/10' : 'bg-gray-50 border-transparent text-gray-400 opacity-60'">
                                                                            <img :src="m.avatar_url" class="h-10 w-10 rounded-full mr-3 border-2 border-white shadow-md object-cover transition-transform group-hover:scale-110" />
                                                                            <p class="text-[8px] font-black uppercase truncate tracking-tighter">{{ m.name }}</p>
                                                                            <div v-if="authorizedUserIds[activeAuthSession.id]?.includes(m.id)" class="absolute -top-2 -right-2 bg-emerald-500 text-white rounded-full p-1 shadow-2xl border-4 border-white"><CheckBadgeIcon class="h-4 w-4" /></div>
                                    
                                        <!-- Loader -->
                                        <div v-if="processingAuthorizations[activeAuthSession.id]" class="absolute inset-0 bg-white/20 backdrop-blur-[1px] rounded-[2.5rem] flex items-center justify-center">
                                            <div class="h-5 w-5 border-3 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN: ENVIAR PEDIDOS (GERENTE) -->
                        <div v-else-if="sidebarMode === 'submit' && hasAnyTeamOrders" class="space-y-12">
                            <div v-for="mType in ['Desayuno', 'Comida', 'Cena', 'Extra']" :key="mType">
                                <div v-if="teamOrders.some(m => m.orders.some(o => o.meal_type === mType))" class="bg-white dark:bg-gray-800 rounded-[4rem] border-2 overflow-hidden shadow-2xl mb-12 border-gray-50">
                                    <div class="bg-gray-900 px-12 py-10 text-white flex justify-between items-center shadow-2xl">
                                        <div class="flex items-center gap-8"><div class="h-16 w-16 bg-white/10 rounded-[2rem] flex items-center justify-center backdrop-blur-xl border border-white/10 shadow-inner"><ClipboardDocumentListIcon class="h-8 w-8" /></div><h5 class="text-4xl font-black uppercase tracking-tighter leading-none">{{ mType }}</h5></div>
                                        <div class="bg-white/10 px-8 py-3 rounded-[1.5rem] border border-white/10 text-[11px] font-black uppercase tracking-widest backdrop-blur-2xl">{{ teamOrders.filter(m => m.orders.some(o => o.meal_type === mType)).length }} Solicitudes</div>
                                    </div>
                                    <div class="p-12">
                                        <div class="flex justify-between items-center mb-10"><p class="text-[11px] font-black uppercase tracking-[0.4em] text-gray-400 italic px-2">Selecciona para firma de área:</p><button @click="selectAllPending(mType)" class="text-[11px] font-black uppercase text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-8 py-4 rounded-[1.5rem] transition-all shadow-sm border border-indigo-100">✓ Seleccionar Pendientes</button></div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 max-h-[600px] overflow-y-auto pr-6 custom-scrollbar">
                                            <div v-for="m in teamOrders" :key="m.id" class="flex items-center p-6 rounded-[3rem] border-2 transition-all shadow-md group/card" :class="m.orders.some(o => o.meal_type === mType) ? 'border-indigo-100 bg-white shadow-indigo-500/5' : 'border-gray-50 opacity-30 grayscale blur-[1px]'">
                                                <div class="mr-6"><div v-if="m.orders.find(o => o.meal_type === mType && o.status === 'submitted_by_user')" class="flex items-center"><Checkbox :checked="selectedOrderIds[mType]?.includes(m.orders.find(o => o.meal_type === mType).id)" @change="toggleOrderSelection(mType, m.orders.find(o => o.meal_type === mType).id)" class="h-7 w-7 rounded-xl text-emerald-600 focus:ring-emerald-500 transition-transform group-hover/card:scale-110" /></div><div v-else class="w-7 flex justify-center"><CheckBadgeIcon v-if="m.orders.some(o => o.meal_type === mType && o.status === 'submitted_by_manager')" class="h-8 w-8 text-emerald-500 drop-shadow-md" /><div v-else class="h-7 w-7 rounded-2xl border-2 border-gray-100"></div></div></div>
                                                <img :src="m.avatar_url" class="h-14 w-14 rounded-full border-2 border-white shadow-xl mr-5 object-cover" />
                                                <div class="flex-1 min-w-0"><p class="text-[13px] font-black truncate uppercase tracking-tight text-gray-800">{{ m.name }}</p><p v-if="m.orders.find(o => o.meal_type === mType)" class="text-[11px] font-bold text-indigo-600 truncate italic mt-1.5 leading-none">"{{ m.orders.find(o => o.meal_type === mType).platillo }}"</p></div>
                                            </div>
                                        </div>
                                        <div class="mt-16 flex justify-center"><button @click="pendingSubmissionMealType = mType; showSubmitConfirmation = true;" :disabled="!selectedOrderIds[mType]?.length" class="px-24 py-6 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[2.5rem] font-black uppercase text-[12px] tracking-[0.5em] transition-all shadow-2xl shadow-emerald-900/30 active:scale-95 disabled:opacity-30">Enviar {{ selectedOrderIds[mType]?.length || 0 }} a Cocina</button></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN: MI MENÚ (GERENTE) -->
                        <div v-else-if="sidebarMode === 'menu'" class="space-y-12">
                            <div class="bg-white dark:bg-gray-800 rounded-[4rem] p-12 shadow-2xl border-2 border-amber-100 shadow-amber-500/5">
                                <div class="flex justify-between items-center mb-12">
                                    <div class="flex items-center gap-10"><div class="h-20 w-20 bg-amber-600 rounded-[2.5rem] flex items-center justify-center shadow-2xl shadow-amber-900/30"><BuildingStorefrontIcon class="h-10 w-10 text-white" /></div><div><h5 class="text-4xl font-black uppercase tracking-tighter leading-none text-gray-800 dark:text-white mb-2">Mi Menú Personal</h5><p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.4em]">Selecciona tu platillo hoy</p></div></div>
                                    <div v-if="hasDirtyAuths" class="px-8 py-4 bg-rose-50 text-rose-600 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 border-rose-100 flex items-center gap-4 animate-pulse shadow-md"><InformationCircleIcon class="h-5 w-5" /> Habilita a tu equipo primero</div>
                                </div>
                                <div v-if="!hasDirtyAuths" class="space-y-12">
                                    <div v-if="myOrdersToday?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                        <div v-for="order in myOrdersToday" :key="order.id" class="bg-white dark:bg-gray-900 border-2 rounded-[3.5rem] p-10 shadow-2xl transition-all" :class="isSessionOpenForMe(order.meal_type) ? 'border-emerald-400 animate-glow-green' : 'border-gray-100 opacity-80'">
                                            <div class="flex justify-between items-start mb-10"><div class="flex items-center gap-6"><div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl" :class="isSessionOpenForMe(order.meal_type) ? (getProviderTheme(activeSession?.provider_id || 0)) : 'bg-gray-400'"><CheckBadgeIcon class="h-10 w-10" /></div><div><span class="text-[11px] font-black px-5 py-2 rounded-xl border uppercase shadow-md tracking-widest block mb-3" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span><span class="text-[11px] font-black uppercase tracking-widest" :class="isSessionOpenForMe(order.meal_type) ? 'text-emerald-500' : 'text-gray-400'">{{ isSessionOpenForMe(order.meal_type) ? 'Servicio Activo' : 'Cerrado' }}</span></div></div><button v-if="isSessionOpenForMe(order.meal_type)" @click="openEditOrderModal(order)" class="p-5 text-indigo-500 hover:bg-indigo-50 rounded-[1.5rem] border shadow-2xl transition-all"><PencilSquareIcon class="h-8 w-8" /></button></div>
                                            <p class="text-xl font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter leading-none">{{ order.daily_menu.name }}</p>
                                        </div>
                                    </div>
                                    <div v-if="hasMenus" class="space-y-12">
                                        <div v-for="(menus, mType) in groupedAvailableMenus" :key="mType" class="rounded-[4rem] p-16 text-white shadow-2xl relative overflow-hidden flex flex-col min-h-[300px] transition-all" :class="getProviderTheme(activeSession?.provider_id || 0)">
                                            <div class="relative z-10 mb-12 flex justify-between items-end"><div><h4 class="text-5xl font-black uppercase tracking-tighter leading-none">{{ mType }}</h4><p class="text-[11px] font-bold opacity-70 uppercase tracking-[0.5em] mt-6 leading-none">Platillos para hoy:</p></div><span class="bg-white/20 px-8 py-3 rounded-2xl border border-white/30 text-[11px] font-black uppercase tracking-widest backdrop-blur-2xl shadow-2xl">Selecciona uno</span></div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 relative z-10">
                                                <div v-for="menu in menus" :key="menu.id" @click="openPlaceOrderModal(menu)" 
                                                     class="p-10 backdrop-blur-3xl border rounded-[3.5rem] cursor-pointer group flex items-center transition-all hover:scale-[1.05] hover:bg-white/20 shadow-2xl" 
                                                     :class="mealTypeCardColors[mType] || 'bg-white/10 border-white/20'">
                                                    <div class="flex-1 min-w-0">
                                                        <h5 class="text-[16px] font-black truncate uppercase tracking-tight leading-tight transition-transform group-hover:translate-x-2">{{ menu.name }}</h5>
                                                        <p class="text-[11px] opacity-80 italic line-clamp-2 mt-3 leading-relaxed font-medium">{{ menu.description || 'Consulta ingredientes.' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="myOrdersToday?.length === 0" class="text-center py-40 bg-gray-50 dark:bg-gray-900/50 rounded-[4rem] border-2 border-dashed shadow-inner"><BuildingStorefrontIcon class="h-24 w-24 text-gray-200 dark:text-gray-700 mx-auto mb-10 shadow-sm" /><p class="text-gray-400 font-black uppercase tracking-[0.4em] text-sm">No hay platillos disponibles hoy</p></div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="sidebarMode === 'justification'" class="space-y-12"><div class="bg-white dark:bg-gray-800 rounded-[4rem] p-12 shadow-2xl border-2 border-rose-100 shadow-rose-500/5"><div class="flex items-center gap-10 mb-12"><div class="h-20 w-20 bg-rose-600 rounded-[2.5rem] flex items-center justify-center shadow-2xl shadow-rose-900/30"><PencilSquareIcon class="h-10 w-10 text-white" /></div><div><h5 class="text-4xl font-black uppercase tracking-tighter leading-none text-gray-800 dark:text-white mb-2">Historial de Consumo</h5><p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.3em]">Tus últimas actividades</p></div></div><div v-if="orderHistory?.length > 0" class="space-y-8"><div v-for="order in orderHistory.slice(0, 5)" :key="'hist-' + order.id" class="bg-gray-50 rounded-[3rem] p-10 border-2 border-transparent hover:border-rose-200 transition-all group shadow-sm"><div class="flex flex-col md:flex-row md:items-center justify-between gap-10"><div class="flex items-center gap-10"><div class="h-20 w-20 rounded-[2rem] bg-white flex flex-col items-center justify-center shadow-xl border"><span class="text-[10px] font-black uppercase text-gray-400 mb-1">{{ new Date(order.daily_menu.available_on).toLocaleDateString('es-ES', { month: 'short' }) }}</span><span class="text-3xl font-black text-gray-800 leading-none">{{ new Date(order.daily_menu.available_on).getDate() }}</span></div><div><div class="flex items-center gap-5 mb-4"><span class="text-[10px] font-black px-4 py-2 rounded-xl border uppercase tracking-widest shadow-sm" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span><span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ order.daily_menu.provider.name }}</span></div><h6 class="text-3xl font-black text-gray-800 uppercase tracking-tighter leading-none truncate">{{ order.daily_menu.name }}</h6></div></div><div v-if="!order.activity_performed" class="flex items-center gap-8"><p class="text-[11px] font-black text-rose-500 uppercase tracking-[0.2em] animate-pulse whitespace-nowrap">Pendiente</p><Link :href="route('justification.index')" class="text-[11px] font-black uppercase bg-gray-900 text-white px-10 py-5 rounded-2xl hover:bg-rose-600 transition-all shadow-2xl active:scale-95">Justificar</Link></div><div v-else class="bg-emerald-50 px-10 py-5 rounded-3xl border border-emerald-100 flex items-center gap-6 shadow-sm"><CheckBadgeIcon class="h-8 w-8 text-emerald-500" /><p class="text-[12px] text-emerald-700 italic font-bold">Actividad Justificada</p></div></div></div></div><div v-else class="text-center py-40"><ClipboardDocumentCheckIcon class="h-24 w-24 text-gray-100 mx-auto mb-10 shadow-inner" /><p class="text-gray-400 font-black uppercase tracking-[0.3em] text-sm">No hay historial registrado</p></div></div></div>
                    </div>
                </div>

                <!-- TAB MI ÁREA (DINER FULL WIDTH PREMIUM) -->
                <div v-if="activeTab === 'my-area' && user.role === 'diner'" class="space-y-12">
                    <div v-if="hasOpenSessionsForArea && !hasMenus && myOrdersToday?.length === 0" class="bg-amber-50 dark:bg-amber-900/10 border-2 border-amber-200 rounded-[4rem] p-16 shadow-2xl flex items-center gap-12">
                        <div class="h-24 w-24 bg-white rounded-[2.5rem] flex items-center justify-center shadow-2xl animate-pulse"><ClockIcon class="h-12 w-12 text-amber-500" /></div>
                        <div><h5 class="text-4xl font-black text-amber-800 uppercase tracking-tighter leading-none mb-4">Esperando Autorización</h5><p class="text-sm font-bold text-amber-600 uppercase tracking-widest leading-relaxed max-w-2xl">Tu gerente de área ya abrió el servicio, pero aún no habilita tu acceso personal para hoy. Por favor, contacta con tu superior.</p></div>
                    </div>
                    <div v-if="hasOpenSessionsForArea || myOrdersToday?.length > 0" class="w-full space-y-12">
                        <div v-if="myOrdersToday?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10">
                            <div v-for="order in myOrdersToday" :key="order.id" class="bg-white border-2 rounded-[4rem] p-12 shadow-2xl transition-all" :class="isSessionOpenForMe(order.meal_type) ? 'border-emerald-400 animate-glow-green' : 'border-gray-100 opacity-90'">
                                <div class="flex justify-between items-start mb-10">
                                    <div class="flex items-center gap-6">
                                        <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl" :class="isSessionOpenForMe(order.meal_type) ? (getProviderTheme(activeSession?.provider_id || 0)) : 'bg-gray-400'"><CheckBadgeIcon class="h-10 w-10" /></div>
                                        <div><span class="text-[11px] font-black px-5 py-2 rounded-xl border uppercase shadow-md tracking-widest block mb-2" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span></div>
                                    </div>
                                    <button v-if="isSessionOpenForMe(order.meal_type)" @click="openEditOrderModal(order)" class="p-5 text-indigo-500 hover:bg-indigo-50 rounded-[1.5rem] transition-all border shadow-2xl"><PencilSquareIcon class="h-8 w-8" /></button>
                                </div>
                                <div class="mb-12"><p class="text-xl font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter leading-none">{{ order.daily_menu.name }}</p></div>
                                <div class="flex items-center text-[12px] font-black uppercase tracking-[0.2em]" :class="order.status === 'submitted_by_manager' ? 'text-emerald-600' : 'text-amber-500'"><div class="h-4 w-4 rounded-full mr-5" :class="order.status === 'submitted_by_manager' ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-amber-500 animate-pulse shadow-amber-500/50'"></div>{{ order.status === 'submitted_by_manager' ? 'Enviado a Cocina' : 'Esperando Firma' }}</div>
                            </div>
                        </div>
                        <div v-for="(menus, mType) in groupedAvailableMenus" :key="mType" class="rounded-[4.5rem] p-20 text-white shadow-2xl relative overflow-hidden flex flex-col min-h-[400px] transition-all" :class="getProviderTheme(activeSession?.provider_id || 0)">
                            <div class="relative z-10 mb-16 flex justify-between items-end"><div><h4 class="text-6xl font-black uppercase tracking-tighter leading-none">{{ mType }}</h4><p class="text-[12px] font-bold opacity-70 uppercase tracking-[0.6em] mt-8 leading-none">Platillos para hoy:</p></div><span class="bg-white/20 px-10 py-4 rounded-[2rem] border border-white/30 text-[12px] font-black uppercase tracking-widest backdrop-blur-2xl shadow-2xl">Selecciona uno</span></div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10 relative z-10 flex-1">
                                <div v-for="menu in menus" :key="menu.id" @click="openPlaceOrderModal(menu)" 
                                     class="p-12 backdrop-blur-3xl border rounded-[4rem] cursor-pointer group flex items-center transition-all hover:scale-[1.05] hover:bg-white/20 shadow-2xl shadow-black/10" 
                                     :class="mealTypeCardColors[mType] || 'bg-white/10 border-white/20'">
                                    <div class="flex-1 min-w-0">
                                        <h5 class="text-[16px] font-black truncate uppercase tracking-tight leading-tight transition-transform group-hover:translate-x-2">{{ menu.name }}</h5>
                                        <p class="text-[12px] opacity-80 italic line-clamp-2 mt-4 leading-relaxed font-medium">{{ menu.description || 'Consulta ingredientes.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-60 bg-white dark:bg-gray-800 rounded-[4rem] border-[4px] border-dashed border-gray-100 shadow-2xl"><BuildingStorefrontIcon class="h-32 w-32 text-gray-100 mx-auto mb-12 shadow-inner" /><h4 class="text-5xl font-black text-gray-800 uppercase tracking-tighter mb-8">Sin Alimentos Abiertos</h4><p class="text-gray-400 font-bold uppercase tracking-[0.4em] text-sm">El servicio para tu dependencia no ha iniciado.</p></div>
                </div>
            </div>
        </div>

        <ActivateMenuModal :show="showActivateMenuModal" :provider="selectedProviderForActivation" :areas="areas" :allSessions="allSessionsToday" :initialMode="activateModalMode" :initialSession="sessionToEdit" @close="showActivateMenuModal = false" />
        <DeactivateMenuConfirmationModal :show="showDeactivateMenuModal" :provider="sessionToDeactivate" :todayOrdersByArea="[]" @close="showDeactivateMenuModal = false" @confirm="confirmDeactivation" />
        <DeleteSessionModal :show="showDeleteSessionModal" :session="sessionToDelete" @close="showDeleteSessionModal = false" />
        <SubmitOrdersConfirmationModal :show="showSubmitConfirmation" :mealType="pendingSubmissionMealType" :count="selectedOrderIds[pendingSubmissionMealType]?.length || 0" @close="showSubmitConfirmation = false" @confirm="confirmSubmitAreaOrders" />
        <PlaceOrderModal :show="showPlaceOrderModal" :menu="selectedMenuForOrder" :existingOrder="editingOrder" :availableOptions="menusForSelection" @close="showPlaceOrderModal = false" />
    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.1); border-radius: 20px; }
@keyframes glow-green { 0%, 100% { border-color: rgb(74, 222, 128); box-shadow: 0 0 20px rgba(74, 222, 128, 0.3); } 50% { border-color: rgb(34, 197, 94); box-shadow: 0 0 40px rgba(34, 197, 94, 0.5); } }
.animate-glow-green { animation: glow-green 2s infinite; }
@keyframes glow-indigo { 0%, 100% { border-color: rgba(99, 102, 241, 0.3); } 50% { border-color: rgba(99, 102, 241, 1); box-shadow: 0 0 20px rgba(99, 102, 241, 0.4); } }
.animate-glow-indigo { animation: glow-indigo 1.5s infinite; }
@keyframes glow-amber { 0%, 100% { border-color: rgba(245, 158, 11, 0.3); } 50% { border-color: rgba(245, 158, 11, 1); box-shadow: 0 0 20px rgba(245, 158, 11, 0.4); } }
.animate-glow-amber { animation: glow-amber 1.5s infinite; }
</style>
