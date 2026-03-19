<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import ActivateMenuModal from '@/Pages/Admin/Partials/ActivateMenuModal.vue';
import DeactivateMenuConfirmationModal from '@/Pages/Admin/Partials/DeactivateMenuConfirmationModal.vue';
import SubmitOrdersConfirmationModal from '@/Pages/Admin/Partials/SubmitOrdersConfirmationModal.vue';
import DeleteSessionModal from '@/Pages/Admin/Partials/DeleteSessionModal.vue';
import PlaceOrderModal from '@/Pages/Admin/Partials/PlaceOrderModal.vue';
import Modal from '@/Components/Modal.vue';
import Checkbox from '@/Components/Checkbox.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    CheckBadgeIcon, ClockIcon, UserGroupIcon, ClipboardDocumentListIcon, PencilSquareIcon, PlusIcon, UserIcon, 
    ChevronRightIcon, TrashIcon, XMarkIcon, CalendarDaysIcon, ListBulletIcon, ChatBubbleLeftRightIcon,
    BuildingStorefrontIcon, InformationCircleIcon, ClipboardDocumentCheckIcon, DocumentIcon, BuildingOfficeIcon,
    WrenchScrewdriverIcon, DocumentChartBarIcon, ExclamationTriangleIcon, TableCellsIcon, PhotoIcon, ArrowLeftIcon,
    UserPlusIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    auth: Object, userRole: String, providers: Array, areas: Array, submittedAreasToday: Array,
    dishSummaryToday: Array, totalOrdersToday: Number, openSessions: Array, closedTodaySessions: Array,
    allOpenSessionsToday: Array, allSessionsToday: Array, myOrdersToday: Array, availableMenus: Array,
    orderHistory: Array, pendingAuthorizations: Array, teamOrders: Array, area: Object,
    activeMealTypes: Array, historicalSessions: Array, operationMode: { type: String, default: 'complete' },
    groupedHistory: { type: Array, default: () => [] }
});

const user = props.auth.user;

// --- STATE DEFINITIONS ---
const activeTab = ref('global'); 
const sidebarMode = ref('auth'); 
const selectedSessionForAuth = ref(null);
const selectedHistorySession = ref(null);
const visitedModes = ref(new Set(['auth']));
const selectedSessionId = ref(null);
const activeTimers = ref({});
const authorizedUserIds = ref({}); 
const processingAuthorizations = ref({}); 
const processingJustifications = ref({}); 
const authStatus = ref({});

// --- Justification Logic ---
const saveActivity = (orderId, activity) => {
    processingJustifications.value[orderId] = true;
    router.put(route('orders.updateJustification', orderId), {
        activity_performed: activity
    }, {
        preserveScroll: true,
        onSuccess: () => {
            processingJustifications.value[orderId] = false;
        },
        onError: () => {
            processingJustifications.value[orderId] = false;
        }
    });
};
const selectedOrderIds = ref({}); 
const showSubmitConfirmation = ref(false);
const showQuickMemberModal = ref(false);
const pendingSubmissionMealType = ref('');

const quickMemberForm = useForm({
    first_name: '',
    last_name: '',
    second_last_name: '',
});

const submitQuickMember = () => {
    quickMemberForm.post(route('team.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showQuickMemberModal.value = false;
            quickMemberForm.reset();
        }
    });
};
const simpleModeSelectedMember = ref(null);
const evidenceFileInput = ref(null);
const uploadingEvidenceSessionId = ref(null);

const triggerEvidenceUpload = (sessionId) => {
    uploadingEvidenceSessionId.value = sessionId;
    evidenceFileInput.value.click();
};

const handleEvidenceFileChange = (e) => {
    const file = e.target.files[0];
    if (!file || !uploadingEvidenceSessionId.value) return;

    // Optional: Visual feedback start
    router.post(route('sessions.uploadEvidence', uploadingEvidenceSessionId.value), {
        image: file
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadingEvidenceSessionId.value = null;
        },
        onError: () => {
            uploadingEvidenceSessionId.value = null;
            alert('Error al subir la imagen. Verifica el formato y tamaño.');
        },
        onFinish: () => {
            uploadingEvidenceSessionId.value = null;
        }
    });
};

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

// --- Simple Mode Selector ---
const openSimpleModeSelector = (member) => {
    if (props.operationMode !== 'simple') {
        toggleUserAuthorization(activeAuthSession.value.id, member.id);
        return;
    }
    simpleModeSelectedMember.value = member;
    const existing = member.orders?.find(o => o.meal_type === activeAuthSession.value.meal_type);
    if (existing) {
        editingOrder.value = { 
            id: existing.id, 
            user_id: member.id, 
            user_name: member.name, 
            daily_menu_id: existing.daily_menu_id, 
            preferences: existing.preferences, 
            meal_type: existing.meal_type 
        };
        const menu = props.availableMenus?.find(m => m.id === existing.daily_menu_id);
        selectedMenuForOrder.value = menu || { name: existing.platillo, provider: { name: '?' } };
        menusForSelection.value = props.availableMenus?.filter(m => m.meal_type === existing.meal_type);
    } else {
        editingOrder.value = { 
            user_id: member.id, 
            user_name: member.name, 
            meal_type: activeAuthSession.value.meal_type,
            daily_menu_id: null // Explicitly null for new
        };
        selectedMenuForOrder.value = null;
        menusForSelection.value = props.availableMenus?.filter(m => m.meal_type === activeAuthSession.value.meal_type);
    }
    showPlaceOrderModal.value = true;
};

// --- Timer & Refresh ---
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
    
    // Default tab for managers and diners
    if (user.role === 'area_manager' || user.role === 'diner') {
        activeTab.value = 'my-area';
    }
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

// --- Diner Logic ---
const showPlaceOrderModal = ref(false), selectedMenuForOrder = ref(null), editingOrder = ref(null), menusForSelection = ref([]);
const showDeleteOrderConfirmation = ref(false), orderToDeleteId = ref(null);

const openPlaceOrderModal = (menu) => { selectedMenuForOrder.value = menu; editingOrder.value = null; menusForSelection.value = [menu]; showPlaceOrderModal.value = true; };
const openEditOrderModal = (order) => { editingOrder.value = order; menusForSelection.value = props.availableMenus.filter(m => m.meal_type === order.meal_type); selectedMenuForOrder.value = order.daily_menu; showPlaceOrderModal.value = true; };

const deleteOrder = (orderId) => {
    orderToDeleteId.value = orderId;
    showDeleteOrderConfirmation.value = true;
};

const confirmDeleteOrder = () => {
    if (!orderToDeleteId.value) return;
    router.delete(route('orders.destroy', orderToDeleteId.value), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteOrderConfirmation.value = false;
            orderToDeleteId.value = null;
        }
    });
};

const isSessionOpenForMe = (mealType) => props.openSessions?.some(s => s.meal_type === mealType && (s.is_open_for_my_area || s.selected_area_ids?.includes(parseInt(user.area_id))));
const hasOpenSessionsForArea = computed(() => props.openSessions?.some(s => s.is_open_for_my_area || s.selected_area_ids?.includes(parseInt(user.area_id))));

const groupedAvailableMenus = computed(() => {
    const groups = {}; if (!props.availableMenus) return groups;
    props.availableMenus.filter(m => !m.already_ordered).forEach(m => { if (!groups[m.meal_type]) groups[m.meal_type] = []; groups[m.meal_type].push(m); });
    return groups;
});
const hasMenus = computed(() => Object.keys(groupedAvailableMenus.value).length > 0);

// --- Authorizations Logic ---
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
    if (idx > -1) authorizedUserIds.value[sId].splice(idx, 1); else authorizedUserIds.value[sId].push(uId); 
    authStatus.value[sId] = 'dirty'; saveAuthorizations(sId);
};

const saveAuthorizations = (sId) => { 
    processingAuthorizations.value[sId] = true; 
    router.post(route('orders.authorizeDiners'), { provider_daily_status_id: sId, user_ids: authorizedUserIds.value[sId] || [] }, { 
        preserveScroll: true, onSuccess: () => { processingAuthorizations.value[sId] = false; authStatus.value[sId] = 'saved'; setTimeout(() => { authStatus.value[sId] = null; }, 3000); } 
    }); 
};

const selectAllForAuth = (sessionId) => { authorizedUserIds.value[sessionId] = props.teamOrders.map(m => m.id); saveAuthorizations(sessionId); };
const deselectAllForAuth = (sessionId) => { authorizedUserIds.value[sessionId] = []; saveAuthorizations(sessionId); };

// --- Submissions ---
const toggleOrderSelection = (mType, oId) => { if (!selectedOrderIds.value[mType]) selectedOrderIds.value[mType] = []; const idx = selectedOrderIds.value[mType].indexOf(oId); if (idx > -1) selectedOrderIds.value[mType].splice(idx, 1); else selectedOrderIds.value[mType].push(oId); };
const selectAllPending = (mType) => { const pending = []; props.teamOrders.forEach(m => { const o = m.orders?.find(ord => ord.meal_type === mType && ord.status === 'submitted_by_user'); if (o) pending.push(o.id); }); selectedOrderIds.value[mType] = pending; };
const confirmSubmitAreaOrders = () => { 
    if (!pendingSubmissionMealType.value) return;
    
    // Safety: recalculate IDs just before sending to be sure
    const ids = [];
    props.teamOrders.forEach(m => {
        const o = m.orders?.find(ord => ord.meal_type === pendingSubmissionMealType.value && ord.status === 'submitted_by_user');
        if (o) ids.push(o.id);
    });

    if (ids.length === 0) {
        showSubmitConfirmation.value = false;
        return;
    }

    router.post(route('orders.areaSubmit'), { 
        order_ids: ids, 
        meal_type: pendingSubmissionMealType.value 
    }, { 
        preserveScroll: true, 
        onSuccess: () => { 
            setTimeout(() => {
                showSubmitConfirmation.value = false; 
                selectedOrderIds.value[pendingSubmissionMealType.value] = []; 
                router.reload({ only: ['teamOrders', 'openSessions', 'dishSummaryToday'] });
            }, 1000);
        } 
    }); 
};

const prepareAndSubmitBatch = () => {
    if (!activeAuthSession.value) return;
    const mType = activeAuthSession.value.meal_type;
    
    // Find all users in the team that have a "submitted_by_user" order for this meal type
    const ids = [];
    props.teamOrders.forEach(m => {
        const o = m.orders?.find(ord => ord.meal_type === mType && ord.status === 'submitted_by_user');
        if (o) ids.push(o.id);
    });
    
    if (ids.length === 0) return alert('No hay nuevos pedidos pendientes de confirmación final.');
    
    pendingSubmissionMealType.value = mType;
    showSubmitConfirmation.value = true;
};

// --- Formats ---
const formattedToday = new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200', 'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200', 'Cena': 'bg-purple-100 text-purple-700 border-purple-200', 'Extra': 'bg-teal-100 text-teal-700 border-teal-200' };
const getProviderTheme = (id) => [ 'bg-indigo-600', 'bg-emerald-600', 'bg-rose-600', 'bg-amber-600', 'bg-purple-600', 'bg-cyan-600' ][id % 6];
const getProviderCardStyle = (idx) => { const themes = [ { border: 'border-indigo-100', icon: 'bg-indigo-600' }, { border: 'border-emerald-100', icon: 'bg-emerald-600' }, { border: 'border-rose-100', icon: 'bg-rose-600' }, { border: 'border-amber-100', icon: 'bg-amber-600' } ]; return themes[idx % themes.length]; };
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-[85%] mx-auto space-y-12">
                
                <!-- TABS NAVEGACIÓN (ADMIN/ADQ ONLY) -->
                <div v-if="user.role === 'admin' || user.role === 'acquisitions_manager'" class="flex justify-center">
                    <div class="bg-white dark:bg-gray-900 p-2 rounded-[2.5rem] shadow-2xl border flex gap-2">
                        <button @click="activeTab = 'global'" 
                                class="px-10 py-4 rounded-[2rem] text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-4"
                                :class="activeTab === 'global' ? 'bg-indigo-600 text-white shadow-2xl scale-[1.05]' : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'">
                            <TableCellsIcon class="h-5 w-5" /> Monitor Global
                        </button>
                        <button v-if="user.area_id" @click="activeTab = 'my-area'" 
                                class="px-10 py-4 rounded-[2rem] text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-4"
                                :class="activeTab === 'my-area' ? 'bg-indigo-600 text-white shadow-2xl scale-[1.05]' : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'">
                            <BuildingOfficeIcon class="h-5 w-5" /> Mi Área
                        </button>
                    </div>
                </div>

                <!-- HEADER ORIGINAL -->
                <div v-if="activeTab === 'my-area' || user.role === 'diner' || user.role === 'area_manager'" class="bg-white dark:bg-gray-800 rounded-[3.5rem] p-10 shadow-2xl border-2 border-indigo-50 flex flex-col md:flex-row justify-between items-center gap-10">
                    <div class="flex items-center gap-8">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
                            <img :src="user.avatar_url" class="relative h-24 w-24 rounded-full border-4 border-white shadow-2xl object-cover" />
                        </div>
                        <div>
                            <h2 class="text-4xl font-black text-gray-800 dark:text-white uppercase tracking-tighter leading-none">{{ user.name }}</h2>
                            <div class="flex items-center gap-4 mt-3">
                                <span class="bg-indigo-600 text-white text-[10px] font-black px-4 py-1.5 rounded-xl uppercase tracking-widest shadow-lg">{{ roleName }}</span>
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-[11px] border-l-2 pl-4 border-gray-100">{{ props.area?.name || 'Dirección General' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-right hidden md:block border-l-2 pl-10 border-gray-50">
                        <p class="text-gray-400 font-black uppercase tracking-[0.3em] text-[10px] mb-2">Fecha del Sistema</p>
                        <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter">{{ formattedToday }}</p>
                    </div>
                </div>

                <!-- VISTA MONITOR GLOBAL (ACQUISITIONS/ADMIN) -->
                <div v-if="activeTab === 'global' && (user.role === 'acquisitions_manager' || user.role === 'admin')" class="space-y-12">
                    <div v-if="openSessions?.length > 0" class="bg-white dark:bg-gray-800 rounded-[4.5rem] p-8 shadow-2xl border-4 border-indigo-50 overflow-hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                            <!-- Sidebar Sesiones -->
                            <div class="lg:col-span-4 space-y-6 max-h-[600px] overflow-y-auto pr-4 custom-scrollbar">
                                <div v-for="session in openSessions" :key="session.id" @click="selectedSessionId = session.id" 
                                     class="p-8 rounded-[3rem] border-2 transition-all cursor-pointer flex flex-col gap-6"
                                     :class="selectedSessionId === session.id ? 'border-indigo-500 bg-indigo-50/50 shadow-xl scale-[1.02]' : 'border-gray-100 hover:border-indigo-200'">
                                    <div class="flex justify-between items-start">
                                        <div><span class="text-[10px] font-black px-4 py-1.5 rounded-xl border uppercase shadow-sm" :class="mealTypeTagColors[session.meal_type]">{{ session.meal_type }}</span><h6 class="text-xl font-black mt-4 uppercase tracking-tighter text-gray-800">{{ session.provider?.name }}</h6></div>
                                        <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-white shadow-2xl" :class="getProviderTheme(session.provider_id)"><BuildingStorefrontIcon class="h-7 w-7" /></div>
                                    </div>
                                    <p class="text-5xl font-black tabular-nums tracking-tighter text-indigo-600">{{ activeTimers[session.id] || '00:00:00' }}</p>
                                    <button @click.stop="openDeactivateMenuModal(session, session.provider)" class="w-full py-4 bg-red-50 text-red-600 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest border border-red-100 shadow-sm">Finalizar Servicio</button>
                                </div>
                            </div>
                            <!-- Monitor Central -->
                            <div v-if="activeSession" class="lg:col-span-8 rounded-[4rem] p-8 text-white shadow-2xl flex flex-col" :class="getProviderTheme(activeSession.provider_id)">
                                <div class="flex justify-between items-start mb-10">
                                    <div><h5 class="text-[11px] font-black uppercase tracking-[0.5em] opacity-70 mb-2">Monitor Consolidado (Tiempo Real)</h5><p class="text-4xl font-black uppercase tracking-tighter leading-none">{{ activeSession.provider?.name }}</p></div>
                                    <div class="bg-white/20 px-6 py-3 rounded-2xl border border-white/30 text-[11px] font-black uppercase tracking-widest backdrop-blur-2xl shadow-xl">{{ activeSession.meal_type }}</div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-10 items-center mb-10 bg-black/10 rounded-[3rem] p-10 border border-white/10 shadow-inner">
                                    <div class="text-left border-r border-white/10 pr-10 col-span-1"><p class="text-[70px] font-black leading-none tracking-tighter">{{ activeTotalOrders }}</p><p class="text-[11px] font-black uppercase opacity-60 mt-4 leading-none">Platillos</p></div>
                                    <div class="col-span-2 space-y-4 max-h-48 overflow-y-auto pr-6 custom-scrollbar">
                                        <div v-for="dish in activeDishSummary" :key="dish.name" class="flex justify-between items-center text-base border-b border-white/10 pb-3 last:border-0"><span class="font-bold truncate mr-4">{{ dish.name }}</span><span class="font-black bg-white text-gray-900 px-3 py-1 rounded-xl shadow-2xl">{{ dish.count }}</span></div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-6"><p class="text-[11px] font-black uppercase tracking-[0.5em] opacity-60">Dependencias:</p><button @click="openEditSessionModal(activeSession, activeSession.provider)" class="text-[10px] font-black uppercase bg-white/20 hover:bg-white/30 text-white px-6 py-2.5 rounded-2xl border border-white/20 shadow-xl transition-all">Gestionar Áreas</button></div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 overflow-y-auto max-h-72 pr-4 custom-scrollbar">
                                        <div v-for="areaS in activeSession.areas_status" :key="areaS.id" class="p-4 rounded-[1.5rem] border border-white/20 text-[10px] font-black uppercase flex justify-between items-center transition-all hover:scale-[1.05] cursor-pointer group/area shadow-sm" :class="areaS.is_submitted ? 'bg-emerald-500/40 border-emerald-400 shadow-emerald-900/20' : 'bg-white/10 border-white/10 hover:bg-white/20'" @click="!areaS.is_submitted && !areaS.is_pending ? removeAreaFromSession(activeSession, areaS.id) : null">
                                            <div class="flex-1 min-w-0 mr-3"><span class="truncate block leading-tight">{{ areaS.name }}</span><span class="text-[8px] opacity-60 block mt-1.5">{{ areaS.submitted_count }}/{{ areaS.order_count }} pedidos</span></div>
                                            <div class="shrink-0">
                                                <CheckBadgeIcon v-if="areaS.is_submitted" class="h-6 w-6 text-green-300 drop-shadow-lg" />
                                                <div v-else-if="areaS.is_pending" class="h-4 w-4 rounded-full border-2 border-amber-300 flex items-center justify-center animate-pulse shadow-sm shadow-amber-500/50">
                                                    <div class="h-1.5 w-1.5 bg-amber-300 rounded-full"></div>
                                                </div>
                                                <div v-else class="text-white/40 group-hover/area:text-red-300 transition-colors"><TrashIcon class="h-5 w-5" /></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catálogo Proveedores Ultra-Compacto -->
                    <div v-if="activeTab === 'global' && (user.role === 'acquisitions_manager' || user.role === 'admin')" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                        <div v-for="(provider, idx) in providers" :key="provider.id" class="group flex flex-col h-full">
                            <!-- Card Principal -->
                            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-[2.5rem] p-6 border-2 shadow-xl transition-all flex flex-col items-center justify-center relative overflow-hidden h-full min-h-[220px]" 
                                 :class="[
                                    getProviderCardStyle(idx).border,
                                    provider.dailyStatuses?.some(s => s.status === 'open') ? 'ring-4 ring-emerald-500/20 border-emerald-400' : ''
                                 ]">
                                
                                <!-- Badge de Estado Activo -->
                                <div v-if="provider.dailyStatuses?.some(s => s.status === 'open')" class="absolute top-4 right-6 flex items-center gap-1.5 bg-emerald-500 text-white text-[7px] font-black uppercase px-2 py-1 rounded-lg shadow-lg animate-pulse">
                                    <div class="h-1.5 w-1.5 bg-white rounded-full"></div> En Servicio
                                </div>

                                <div @click="openActivateMenuModal(provider)" class="cursor-pointer flex flex-col items-center w-full">
                                    <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-white mb-4 shadow-lg transition-transform group-hover:scale-110 group-hover:rotate-3" :class="getProviderCardStyle(idx).icon">
                                        <BuildingStorefrontIcon class="h-7 w-7" />
                                    </div>
                                    <h4 class="font-black text-base uppercase tracking-tighter text-gray-900 dark:text-white leading-tight mb-1 text-center truncate w-full px-2" :title="provider.name">{{ provider.name }}</h4>
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-[0.2em] group-hover:text-indigo-500 transition-colors">
                                        {{ 
                                            provider.dailyStatuses?.some(s => s.status === 'open') 
                                                ? 'Gestionar Activos' 
                                                : (provider.dailyStatuses?.length > 0 ? 'Ver Cerrados / Nuevo' : 'Iniciar Sesión')
                                        }}
                                    </p>
                                </div>

                                <!-- Acciones de Sesión Compactas (Solo si existen sesiones hoy) -->
                                <div v-if="provider.dailyStatuses?.length > 0" class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 w-full">
                                    <div class="flex flex-col gap-2">
                                        <div v-for="status in provider.dailyStatuses" :key="status.id" 
                                             class="flex items-center justify-between p-2 rounded-xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-800">
                                            <span class="text-[7px] font-black uppercase tracking-tighter px-2 py-0.5 rounded border shadow-sm" :class="mealTypeTagColors[status.meal_type]">{{ status.meal_type }}</span>
                                            
                                            <div class="flex items-center gap-1">
                                                <!-- Reporte -->
                                                <Link :href="route('admin.orders.summary', { provider: provider.id, date: status.date, meal_type: status.meal_type })" 
                                                      class="p-1.5 text-emerald-600 hover:bg-emerald-100 rounded-lg transition-all" title="Ver Reporte">
                                                    <DocumentChartBarIcon class="h-3.5 w-3.5" />
                                                </Link>
                                                <!-- WhatsApp -->
                                                <Link :href="route('admin.orders.send', { provider: provider.id, date: status.date, meal_type: status.meal_type })" 
                                                      class="p-1.5 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-all" title="WhatsApp">
                                                    <ChatBubbleLeftRightIcon class="h-3.5 w-3.5" />
                                                </Link>
                                                <!-- Finalizar / Reabrir -->
                                                <button v-if="status.status === 'open'" @click="openDeactivateMenuModal(status, provider)" 
                                                        class="p-1.5 text-red-600 hover:bg-red-100 rounded-lg transition-all" title="Finalizar">
                                                    <XMarkIcon class="h-3.5 w-3.5" />
                                                </button>
                                                <button v-else @click="openEditSessionModal(status, provider)" 
                                                        class="p-1.5 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-all" title="Reabrir">
                                                    <ArrowPathIcon class="h-3.5 w-3.5" />
                                                </button>
                                                <!-- Borrar -->
                                                <button @click="openDeleteSessionModal(status, provider)" 
                                                        class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition-all" title="Eliminar">
                                                    <TrashIcon class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTENIDO PARA GERENCIA (GERENTES, ADQ, ADMIN EN PESTAÑA MI AREA) -->
                <div v-if="(activeTab === 'my-area' || user.role === 'area_manager') && ['area_manager', 'acquisitions_manager', 'admin'].includes(user.role) && user.role !== 'diner'" class="grid grid-cols-12 gap-12">
                    <div class="col-span-3 space-y-8">
                        <div v-if="user.role !== 'diner'" class="bg-white dark:bg-gray-800 rounded-[3rem] p-8 shadow-2xl border border-indigo-50 sticky top-24">
                            <h4 class="text-[11px] font-black uppercase tracking-[0.4em] text-gray-400 mb-10 px-4">Panel de Control</h4>
                            <div class="space-y-5">
                                <template v-for="mode in [
                                    {id:'auth',l: operationMode === 'simple' ? 'Asignar' : 'Habilitar', s: operationMode === 'simple' ? 'Platillos' : 'Personal', i:UserIcon, c:'indigo'},
                                    {id:'menu',l:'Mi Menú', s:'Personal', i:BuildingStorefrontIcon, c:'amber', hideInSimple: true},
                                    {id:'justification',l:'Justificar', s:'Historial', i:PencilSquareIcon, c:'rose'}
                                ]" :key="mode.id">
                                    <button v-if="!(operationMode === 'simple' && mode.hideInSimple)" @click="sidebarMode = mode.id" 
                                    class="w-full p-6 rounded-[2.5rem] border-2 transition-all flex items-center gap-6 text-left group relative overflow-hidden active:scale-95" 
                                    :class="[ sidebarMode === mode.id ? `border-${mode.c}-500 bg-${mode.c}-50 shadow-xl scale-[1.02]` : 'border-transparent hover:bg-gray-50' ]">
                                        <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center transition-all shadow-lg" :class="sidebarMode === mode.id ? `bg-${mode.c}-600 text-white` : 'bg-gray-100 text-gray-400'">
                                            <component :is="mode.i" class="h-8 w-8" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[15px] font-black uppercase tracking-tight" :class="sidebarMode === mode.id ? `text-${mode.c}-600` : 'text-gray-500'">{{ mode.l }}</p>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1.5">{{ mode.s }}</p>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-9">
                        <!-- GERENTE: HABILITAR / ASIGNAR -->
                        <div v-if="sidebarMode === 'auth' && activeAuthSession" class="bg-white dark:bg-gray-800 rounded-[4rem] border shadow-2xl overflow-hidden">
                            <div class="px-12 py-10 text-white flex justify-between items-center shadow-2xl" :class="getProviderTheme(activeAuthSession.provider_id)">
                                <div class="flex items-center gap-10">
                                    <div class="h-20 w-20 bg-white/20 rounded-[2.5rem] flex items-center justify-center backdrop-blur-3xl border border-white/20 shadow-inner"><UserIcon class="h-10 w-10 text-white" /></div>
                                    <div><h5 class="text-4xl font-black uppercase tracking-tighter leading-none mb-3">{{ operationMode === 'simple' ? 'Asignar Platillos' : 'Habilitar Personal' }}</h5><p class="text-[11px] font-bold opacity-80 uppercase tracking-widest">{{ activeAuthSession.meal_type }} • {{ activeAuthSession.provider?.name }}</p></div>
                                </div>
                                <div v-if="operationMode === 'complete'" class="flex gap-4">
                                    <button @click="selectAllForAuth(activeAuthSession.id)" class="px-8 py-3.5 bg-white/20 hover:bg-white/30 rounded-[1.5rem] text-[11px] font-black uppercase shadow-xl transition-all">Todos</button>
                                    <button @click="deselectAllForAuth(activeAuthSession.id)" class="px-8 py-3.5 bg-black/10 hover:bg-black/20 rounded-[1.5rem] text-[11px] font-black uppercase shadow-xl transition-all">Ninguno</button>
                                </div>
                                <div v-else class="bg-white/20 px-8 py-3.5 rounded-[1.5rem] border border-white/30 text-[10px] font-black uppercase tracking-widest backdrop-blur-md">Gestión Directa</div>
                            </div>
                            <div class="p-12">
                                <p class="text-[11px] font-black uppercase tracking-[0.4em] text-gray-400 mb-10 text-center">{{ operationMode === 'simple' ? 'Toca a un integrante para elegir su platillo:' : 'Toca el nombre para autorizar automáticamente:' }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    <div v-for="m in teamOrders" :key="'auth-' + activeAuthSession.id + '-' + m.id" @click="openSimpleModeSelector(m)" 
                                         class="flex flex-col p-4 rounded-[2.5rem] border-2 transition-all cursor-pointer relative group shadow-sm hover:scale-[1.02]" 
                                         :class="[ operationMode === 'simple' ? (m.orders.some(o => o.meal_type === activeAuthSession.meal_type) ? 'bg-indigo-50 border-indigo-500 shadow-indigo-100' : 'bg-gray-50 border-transparent opacity-80') : (authorizedUserIds[activeAuthSession.id]?.includes(m.id) ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-emerald-500/10' : 'bg-gray-50 border-transparent text-gray-400 opacity-60') ]">
                                        <div class="flex items-center mb-3">
                                            <img :src="m.avatar_url" class="h-10 w-10 rounded-full mr-3 border-2 border-white shadow-md object-cover transition-transform group-hover:scale-110" />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[10px] font-black uppercase truncate tracking-tight text-gray-800 leading-tight">{{ m.name }}</p>
                                                <p v-if="operationMode === 'simple' && !m.orders.some(o => o.meal_type === activeAuthSession.meal_type)" class="text-[7px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Pendiente</p>
                                            </div>
                                        </div>
                                        <div v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type)" class="mt-1 space-y-1.5 border-t pt-3 border-indigo-100">
                                            <p class="text-[9px] font-black text-indigo-600 uppercase tracking-tighter leading-none">{{ m.orders.find(o => o.meal_type === activeAuthSession.meal_type).platillo }}</p>
                                            <p v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type).preferences" class="text-[8px] font-bold text-rose-500 italic leading-none truncate">* {{ m.orders.find(o => o.meal_type === activeAuthSession.meal_type).preferences }}</p>
                                        </div>
                                        
                                        <!-- Indicators & Deletion -->
                                        <div v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type)" class="absolute -top-1.5 -right-1.5 flex gap-1">
                                            <button @click.stop="deleteOrder(m.orders.find(o => o.meal_type === activeAuthSession.meal_type).id)" 
                                                    class="bg-white text-red-500 rounded-full p-1 shadow-xl border border-red-100 hover:bg-red-50 transition-all"
                                                    title="Eliminar Pedido">
                                                <TrashIcon class="h-3.5 w-3.5" />
                                            </button>
                                            <div v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type).status === 'submitted_by_manager'" 
                                                 class="bg-emerald-500 text-white rounded-full p-1 shadow-2xl border-2 border-white" title="Confirmado">
                                                <CheckBadgeIcon class="h-4 w-4" />
                                            </div>
                                            <div v-else class="bg-indigo-600 text-white rounded-full p-1 shadow-2xl border-2 border-white" title="Asignado">
                                                <CheckBadgeIcon class="h-4 w-4" />
                                            </div>
                                        </div>
                                        <div v-else-if="operationMode === 'complete' && authorizedUserIds[activeAuthSession.id]?.includes(m.id)" class="absolute -top-1.5 -right-1.5 bg-emerald-500 text-white rounded-full p-1 shadow-2xl border-2 border-white"><CheckBadgeIcon class="h-4 w-4" /></div>
                                        
                                        <div v-if="processingAuthorizations[activeAuthSession.id]" class="absolute inset-0 bg-white/20 backdrop-blur-[1px] rounded-[2.5rem] flex items-center justify-center"><div class="h-4 w-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div></div>
                                    </div>

                                    <!-- TARJETA ESPECIAL: AGREGAR COMENSAL -->
                                    <div @click="showQuickMemberModal = true" 
                                         class="flex flex-col items-center justify-center p-4 rounded-[2.5rem] border-2 border-dashed border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer group shadow-sm">
                                        <div class="h-10 w-10 rounded-full bg-gray-100 group-hover:bg-indigo-600 flex items-center justify-center text-gray-400 group-hover:text-white transition-all mb-2 shadow-inner">
                                            <PlusIcon class="h-6 w-6" />
                                        </div>
                                        <p class="text-[8px] font-black uppercase text-gray-400 group-hover:text-indigo-600 tracking-widest text-center">Nuevo<br/>Comensal</p>
                                    </div>
                                </div>

                                <!-- SECCIÓN: CONFIRMACIÓN FINAL (PEDIDO LISTO) -->
                                <div v-if="teamOrders.some(m => m.orders.some(o => o.meal_type === activeAuthSession.meal_type))" class="mt-16 p-10 bg-indigo-50/50 rounded-[3rem] border-2 border-dashed border-indigo-200 flex flex-col items-center">
                                    <div class="flex items-center gap-6 mb-8">
                                        <div class="h-12 w-12 bg-white rounded-2xl flex items-center justify-center shadow-lg"><ClipboardDocumentListIcon class="h-6 w-6 text-indigo-600" /></div>
                                        <div>
                                            <p class="text-lg font-black text-indigo-900 uppercase tracking-tighter leading-none">Finalizar Pedido del Equipo</p>
                                            <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mt-1">Hay {{ teamOrders.filter(m => m.orders.some(o => o.meal_type === activeAuthSession.meal_type)).length }} platillos seleccionados</p>
                                        </div>
                                    </div>
                                    
                                    <button @click="pendingSubmissionMealType = activeAuthSession.meal_type; prepareAndSubmitBatch();" 
                                            :disabled="teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user'))"
                                            class="px-24 py-6 rounded-[2.5rem] font-black uppercase text-[12px] tracking-[0.4em] transition-all shadow-2xl flex items-center gap-4"
                                            :class="teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user'))
                                                ? 'bg-emerald-50 text-emerald-500 border-2 border-emerald-200 cursor-default shadow-none'
                                                : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-900/20 active:scale-95'">
                                        <CheckBadgeIcon v-if="teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user'))" class="h-6 w-6" />
                                        {{ teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user')) ? 'Pedido Enviado y Confirmado' : 'Enviar Pedido Final' }}
                                    </button>
                                    <p v-if="!teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user'))" class="mt-6 text-[9px] font-bold text-indigo-400 uppercase tracking-[0.2em] italic text-center max-w-md">
                                        Al presionar este botón, el área se marcará como **"LISTA"** en el monitor de adquisiciones y se bloquearán los cambios.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="sidebarMode === 'auth'" class="h-full flex flex-col items-center justify-center p-20 bg-white dark:bg-gray-800 rounded-[4rem] border-2 border-dashed border-gray-200 text-center shadow-2xl transition-all">
                            <div class="h-24 w-24 bg-gray-50 rounded-[2.5rem] flex items-center justify-center mb-8 shadow-inner animate-pulse"><ClockIcon class="h-12 w-12 text-gray-300" /></div>
                            <h4 class="text-3xl font-black text-gray-800 dark:text-white uppercase tracking-tighter mb-4">Sin Sesión Activa</h4>
                            <p class="text-gray-400 font-bold uppercase tracking-[0.2em] text-[10px] max-w-sm">No hay un servicio de comedor abierto para tu dependencia en este momento.</p>
                        </div>

                        <!-- GERENTE: ENVIAR PEDIDOS (ELIMINADA) -->
                        <div v-else-if="sidebarMode === 'submit'" class="h-full flex flex-col items-center justify-center p-20 bg-white dark:bg-gray-800 rounded-[4rem] border-2 border-dashed border-gray-200 text-center shadow-2xl">
                            <h4 class="text-3xl font-black text-gray-800 dark:text-white uppercase tracking-tighter mb-4">Sección Unificada</h4>
                            <p class="text-gray-400 font-bold uppercase tracking-[0.2em] text-[10px] max-w-sm">Ahora puedes enviar los pedidos directamente desde la pestaña de Asignación.</p>
                        </div>

                        <!-- GERENTE: MI MENÚ -->
                        <div v-else-if="sidebarMode === 'menu'" class="space-y-12">
                            <div class="bg-white dark:bg-gray-800 rounded-[4rem] p-12 shadow-2xl border-2 border-amber-100">
                                <div class="flex justify-between items-center mb-12"><div class="flex items-center gap-10"><div class="h-20 w-20 bg-amber-600 rounded-[2.5rem] flex items-center justify-center shadow-2xl"><BuildingStorefrontIcon class="h-10 w-10 text-white" /></div><div><h5 class="text-4xl font-black uppercase tracking-tighter leading-none text-gray-800 dark:text-white mb-2">Mi Menú Personal</h5><p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.3em]">Selecciona tu platillo hoy</p></div></div></div>
                                <div class="space-y-12">
                                    <div v-if="myOrdersToday?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                        <div v-for="order in myOrdersToday" :key="order.id" class="bg-white dark:bg-gray-900 border-2 rounded-[3.5rem] p-8 shadow-2xl transition-all" :class="isSessionOpenForMe(order.meal_type) ? 'border-emerald-400 animate-glow-green' : 'border-gray-100 opacity-80'">
                                            <div class="flex justify-between items-start mb-10"><div class="flex items-center gap-6"><div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl" :class="isSessionOpenForMe(order.meal_type) ? (getProviderTheme(activeSession?.provider_id || 0)) : 'bg-gray-400'"><CheckBadgeIcon class="h-10 w-10" /></div><div><span class="text-[10px] font-black px-5 py-2 rounded-xl border uppercase shadow-sm tracking-widest block mb-3" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span></div></div><button v-if="isSessionOpenForMe(order.meal_type) && operationMode === 'complete'" @click="openEditOrderModal(order)" class="p-5 text-indigo-500 hover:bg-indigo-50 rounded-[1.5rem] border shadow-2xl transition-all"><PencilSquareIcon class="h-8 w-8" /></button></div>
                                            <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter leading-tight">{{ order.daily_menu.name }}</p>
                                        </div>
                                    </div>
                                    <div v-if="hasMenus && operationMode === 'complete'" class="space-y-12">
                                        <div v-for="(menus, mType) in groupedAvailableMenus" :key="mType" class="rounded-[4rem] p-16 text-white shadow-2xl relative overflow-hidden flex flex-col min-h-[300px] transition-all" :class="getProviderTheme(activeSession?.provider_id || 0)">
                                            <div class="relative z-10 mb-12 flex justify-between items-end"><div><h4 class="text-5xl font-black uppercase tracking-tighter leading-none">{{ mType }}</h4><p class="text-[11px] font-bold opacity-70 uppercase tracking-[0.5em] mt-6 leading-none">Platillos para hoy:</p></div></div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 relative z-10">
                                                <div v-for="menu in menus" :key="menu.id" @click="openPlaceOrderModal(menu)" 
                                                     class="p-8 backdrop-blur-3xl border rounded-[3rem] cursor-pointer group flex items-center transition-all hover:scale-[1.05] hover:bg-white/30 shadow-2xl bg-black/10 border-white/20">
                                                    <div class="flex-1 min-w-0">
                                                        <h5 class="text-[14px] font-black uppercase tracking-tight leading-tight transition-transform group-hover:translate-x-2">{{ menu.name }}</h5>
                                                        <p class="text-[10px] opacity-80 italic line-clamp-2 mt-2 leading-relaxed font-medium">{{ menu.description || 'Consulta ingredientes.' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GERENTE: HISTORIAL -->
                        <div v-else-if="sidebarMode === 'justification'" class="space-y-12">
                            <!-- Header Seccional -->
                            <div class="bg-white dark:bg-gray-800 rounded-[4rem] p-12 shadow-2xl border-2 border-rose-100 flex justify-between items-center">
                                <div class="flex items-center gap-10">
                                    <div class="h-20 w-20 bg-rose-600 rounded-[2.5rem] flex items-center justify-center shadow-2xl shadow-rose-900/30">
                                        <PencilSquareIcon class="h-10 w-10 text-white" />
                                    </div>
                                    <div>
                                        <h5 class="text-4xl font-black uppercase tracking-tighter leading-none text-gray-800 mb-2">Justificación</h5>
                                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.3em]">Gestión de Actividades del Equipo</p>
                                    </div>
                                </div>
                                <button v-if="selectedHistorySession" @click="selectedHistorySession = null" 
                                        class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-3 shadow-sm">
                                    <ArrowLeftIcon class="h-4 w-4" /> Regresar al Listado
                                </button>
                            </div>

                            <!-- MODO: LISTA DE SESIONES (TARJETAS) -->
                            <div v-if="!selectedHistorySession" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Hidden input for evidence -->
                                <input type="file" ref="evidenceFileInput" class="hidden" accept="image/*" @change="handleEvidenceFileChange" />
                                
                                <div v-for="session in groupedHistory" :key="session.date + session.provider_id + session.meal_type" 
                                     class="bg-white dark:bg-gray-800 p-8 rounded-[3.5rem] border-2 shadow-xl transition-all hover:scale-[1.03] flex flex-col justify-between"
                                     :class="[
                                        session.justified_count === session.total_orders 
                                            ? 'border-emerald-400 shadow-emerald-50' 
                                            : (session.justified_count > 0 ? 'border-amber-400 shadow-amber-50' : 'border-rose-100 hover:border-rose-300')
                                     ]">
                                    <div class="flex justify-between items-start mb-8">
                                        <div class="h-16 w-16 rounded-[1.5rem] flex flex-col items-center justify-center bg-gray-50 border shadow-inner cursor-pointer" @click="selectedHistorySession = session">
                                            <span class="text-[9px] font-black uppercase text-gray-400 mb-0.5">{{ new Date(session.date + 'T12:00:00').toLocaleDateString('es-ES', { month: 'short' }) }}</span>
                                            <span class="text-2xl font-black text-gray-800">{{ new Date(session.date + 'T12:00:00').getDate() }}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <div class="text-right">
                                                <span class="text-[9px] font-black px-3 py-1 rounded-lg border uppercase tracking-widest block mb-2 shadow-sm" :class="mealTypeTagColors[session.meal_type]">{{ session.meal_type }}</span>
                                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.2em]">{{ session.provider_name }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Evidence Section: Button or Preview -->
                                    <div class="mb-6">
                                        <div v-if="session.evidence_url" class="rounded-2xl overflow-hidden h-28 border-2 border-emerald-100 shadow-inner group relative">
                                            <img :src="session.evidence_url" class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2">
                                                <button @click="triggerEvidenceUpload(session.id)" class="bg-white/20 hover:bg-white/40 p-2 rounded-full transition-all border border-white/30">
                                                    <PhotoIcon class="h-5 w-5 text-white" />
                                                </button>
                                                <p class="text-white text-[8px] font-black uppercase tracking-widest">Cambiar Foto</p>
                                            </div>
                                        </div>
                                        <button v-else @click="triggerEvidenceUpload(session.id)" 
                                                class="w-full py-6 rounded-2xl border-2 border-dashed border-gray-200 hover:border-rose-400 hover:bg-rose-50 transition-all flex flex-col items-center justify-center gap-2 group">
                                            <div class="h-10 w-10 rounded-full bg-gray-50 group-hover:bg-rose-600 flex items-center justify-center text-gray-400 group-hover:text-white transition-all shadow-inner">
                                                <PhotoIcon v-if="!uploadingEvidenceSessionId || uploadingEvidenceSessionId !== session.id" class="h-6 w-6" />
                                                <ArrowPathIcon v-else class="h-6 w-6 animate-spin" />
                                            </div>
                                            <p class="text-[10px] font-black uppercase text-gray-400 group-hover:text-rose-600 tracking-widest">Subir Evidencia</p>
                                        </button>
                                    </div>
                                    
                                    <div @click="selectedHistorySession = session" class="cursor-pointer">
                                        <div class="space-y-4">
                                            <div class="flex justify-between items-end">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Progreso:</p>
                                                <p class="text-xs font-black" :class="session.justified_count === session.total_orders ? 'text-emerald-600' : 'text-rose-500'">
                                                    {{ session.justified_count }} / {{ session.total_orders }}
                                                </p>
                                            </div>
                                            <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden border">
                                                <div class="h-full transition-all duration-700" 
                                                     :class="session.justified_count === session.total_orders ? 'bg-emerald-500' : 'bg-rose-500'"
                                                     :style="{ width: (session.justified_count / session.total_orders * 100) + '%' }"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-8 pt-6 border-t border-gray-50 flex justify-between items-center">
                                            <p class="text-[9px] font-black uppercase tracking-widest" :class="session.justified_count === session.total_orders ? 'text-emerald-600' : 'text-rose-500'">
                                                {{ session.justified_count === session.total_orders ? 'Completado' : 'Pendientes por justificar' }}
                                            </p>
                                            <div class="px-6 py-3 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-xl shadow-indigo-900/20 active:scale-95 transition-all gap-3 border border-indigo-400">
                                                <span class="text-[10px] font-black uppercase tracking-widest">{{ session.justified_count === session.total_orders ? 'Revisar' : 'Justificar' }}</span>
                                                <ChevronRightIcon class="h-4 w-4" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="groupedHistory?.length === 0" class="col-span-full py-40 text-center bg-white rounded-[4rem] border-2 border-dashed border-gray-100">
                                    <ClipboardDocumentCheckIcon class="h-24 w-24 text-gray-100 mx-auto mb-8" />
                                    <p class="text-gray-400 font-black uppercase tracking-[0.3em] text-sm">No hay sesiones en el historial reciente</p>
                                </div>
                            </div>

                            <!-- MODO: DETALLE DE LA SESIÓN (DRILL-DOWN) -->
                            <div v-else class="space-y-6">
                                <div class="bg-indigo-50 border-2 border-indigo-100 rounded-[3rem] p-8 flex items-center justify-between">
                                    <div class="flex items-center gap-6">
                                        <div class="h-14 w-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg"><UserIcon class="h-7 w-7" /></div>
                                        <div>
                                            <h6 class="text-xl font-black text-indigo-900 uppercase tracking-tight">{{ selectedHistorySession.provider_name }}</h6>
                                            <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-[0.2em]">{{ selectedHistorySession.meal_type }} • {{ selectedHistorySession.date }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[9px] font-black text-indigo-400 uppercase mb-1">Registros en el área:</p>
                                        <p class="text-2xl font-black text-indigo-600">{{ selectedHistorySession.orders.length }}</p>
                                    </div>
                                </div>

                                <div v-for="order in selectedHistorySession.orders" :key="'drill-' + order.id" 
                                     class="bg-white dark:bg-gray-800 p-8 rounded-[3.5rem] border-2 transition-all flex flex-col md:flex-row gap-8 items-center"
                                     :class="order.activity_performed ? 'border-emerald-100' : 'border-rose-100 shadow-xl shadow-rose-500/5'">
                                    
                                    <div class="min-w-[350px] max-w-[350px] shrink-0 flex items-center gap-6">
                                        <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center font-black text-lg uppercase border-4 shadow-xl shrink-0 overflow-hidden" 
                                             :class="order.activity_performed ? 'bg-green-50 text-green-500 border-green-100' : 'bg-orange-50 text-orange-500 border-orange-100'">
                                            <img v-if="order.avatar_url && !order.avatar_url.includes('ui-avatars.com')" :src="order.avatar_url" class="h-full w-full object-cover" />
                                            <span v-else>{{ order.user_name.substring(0,2) }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h6 class="text-base font-black text-gray-800 dark:text-white uppercase tracking-tight leading-none mb-2 truncate" :title="order.user_name">
                                                {{ order.user_name }}
                                            </h6>
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 w-1.5 rounded-full bg-indigo-500 shrink-0"></div>
                                                <p class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest truncate">{{ order.platillo }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-1 w-full">
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                            <PencilSquareIcon class="h-3 w-3" /> Actividad Realizada:
                                        </p>
                                        <div class="relative">
                                            <textarea 
                                                v-model="order.activity_performed" 
                                                @blur="saveActivity(order.id, order.activity_performed)"
                                                @keyup.enter="$event.target.blur()"
                                                placeholder="Describe la actividad para justificar..."
                                                class="w-full bg-gray-50 dark:bg-gray-950 border-2 border-gray-100 dark:border-gray-700 rounded-2xl p-4 text-[11px] font-bold text-gray-700 dark:text-gray-200 placeholder:text-gray-300 focus:border-rose-500 focus:ring-0 transition-all resize-none"
                                                rows="2"
                                            ></textarea>
                                            <div v-if="processingJustifications[order.id]" class="absolute right-4 bottom-4">
                                                <div class="h-4 w-4 border-2 border-rose-500 border-t-transparent rounded-full animate-spin"></div>
                                            </div>
                                            <div v-else-if="order.activity_performed" class="absolute right-4 bottom-4">
                                                <CheckBadgeIcon class="h-5 w-5 text-emerald-500" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTENIDO PARA COMENSALES (FULL WIDTH) -->
                <div v-if="user.role === 'diner'" class="space-y-12">
                    <div v-if="operationMode === 'simple'" class="bg-indigo-50 dark:bg-indigo-900/10 border-2 border-indigo-200 rounded-[4rem] p-16 shadow-2xl flex items-center gap-12">
                        <div class="h-24 w-24 bg-white rounded-[2.5rem] flex items-center justify-center shadow-2xl"><UserGroupIcon class="h-12 w-12 text-indigo-500" /></div>
                        <div><h5 class="text-4xl font-black text-indigo-800 uppercase tracking-tighter leading-none mb-4">Gestión por Gerencia</h5><p class="text-sm font-bold text-indigo-600 uppercase tracking-widest leading-relaxed max-w-2xl">El sistema se encuentra en **Modo Simple**. Tu gerente selecciona los platillos para el equipo.</p></div>
                    </div>
                    
                    <div v-if="myOrdersToday?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10">
                        <div v-for="order in myOrdersToday" :key="order.id" class="bg-white dark:bg-gray-800 border-2 rounded-[4rem] p-12 shadow-2xl transition-all" :class="isSessionOpenForMe(order.meal_type) ? 'border-emerald-400 animate-glow-green' : 'border-gray-100 opacity-90'">
                            <div class="flex justify-between items-start mb-10"><div class="flex items-center gap-6"><div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl" :class="getProviderTheme(activeSession?.provider_id || 0)"><CheckBadgeIcon class="h-10 w-10" /></div><span class="text-[11px] font-black px-5 py-2 rounded-xl border uppercase shadow-md tracking-widest block mb-2" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span></div></div>
                            <div class="mb-12"><p class="text-3xl font-black text-indigo-600 uppercase tracking-tighter leading-none">{{ order.daily_menu.name }}</p><p v-if="order.preferences" class="mt-4 p-3 bg-gray-50 rounded-xl text-[10px] font-bold text-indigo-500 uppercase italic border-l-4 border-indigo-500">Obs: {{ order.preferences }}</p></div>
                            <div class="flex items-center text-[12px] font-black uppercase tracking-[0.2em]" :class="order.status === 'submitted_by_manager' ? 'text-emerald-600' : 'text-amber-500'"><div class="h-4 w-4 rounded-full mr-5" :class="order.status === 'submitted_by_manager' ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></div>{{ order.status === 'submitted_by_manager' ? 'Enviado a Cocina' : 'Esperando Firma' }}</div>
                        </div>
                    </div>

                    <div v-if="operationMode === 'complete' && hasMenus">
                        <div v-for="(menus, mType) in groupedAvailableMenus" :key="mType" class="rounded-[4.5rem] p-20 text-white shadow-2xl relative overflow-hidden flex flex-col min-h-[400px] mb-12" :class="getProviderTheme(activeSession?.provider_id || 0)">
                            <div class="relative z-10 mb-16 flex justify-between items-end"><div><h4 class="text-6xl font-black uppercase tracking-tighter leading-none">{{ mType }}</h4><p class="text-[12px] font-bold opacity-70 uppercase tracking-[0.6em] mt-8 leading-none">Platillos para hoy:</p></div></div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10 relative z-10 flex-1">
                                <div v-for="menu in menus" :key="menu.id" @click="openPlaceOrderModal(menu)" 
                                     class="p-12 backdrop-blur-3xl border rounded-[4rem] cursor-pointer group transition-all hover:scale-[1.05] hover:bg-white/30 bg-black/10 border-white/20 shadow-2xl">
                                    <h5 class="text-[18px] font-black uppercase tracking-tight leading-tight transition-transform group-hover:translate-x-2">{{ menu.name }}</h5>
                                    <p class="text-xs opacity-80 italic line-clamp-2 mt-4 leading-relaxed font-medium">{{ menu.description || 'Consulta ingredientes.' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODALES -->
        <ActivateMenuModal :show="showActivateMenuModal" :provider="selectedProviderForActivation" :areas="areas" :allSessions="allSessionsToday" :initialMode="activateModalMode" :initialSession="sessionToEdit" @close="showActivateMenuModal = false" />
        <DeactivateMenuConfirmationModal :show="showDeactivateMenuModal" :provider="sessionToDeactivate" :todayOrdersByArea="[]" @close="showDeactivateMenuModal = false" @confirm="confirmDeactivation" />
        <DeleteSessionModal :show="showDeleteSessionModal" :session="sessionToDelete" @close="showDeleteSessionModal = false" />
        <SubmitOrdersConfirmationModal :show="showSubmitConfirmation" :mealType="pendingSubmissionMealType" :count="selectedOrderIds[pendingSubmissionMealType]?.length || 0" @close="showSubmitConfirmation = false" @confirm="confirmSubmitAreaOrders" />
        <PlaceOrderModal :show="showPlaceOrderModal" :menu="selectedMenuForOrder" :existingOrder="editingOrder" :availableOptions="menusForSelection" @close="showPlaceOrderModal = false" />

        <!-- MODAL PREMIUM: CONFIRMAR ELIMINACIÓN DE PEDIDO -->
        <Modal :show="showDeleteOrderConfirmation" @close="showDeleteOrderConfirmation = false" max-width="md">
            <!-- ... (omitted content for clarity, same as before) ... -->
        </Modal>

        <!-- MODAL ALTA RÁPIDA DE PERSONAL -->
        <Modal :show="showQuickMemberModal" @close="showQuickMemberModal = false" max-width="md">
            <div class="p-10">
                <div class="flex items-center gap-6 mb-10">
                    <div class="h-16 w-16 bg-indigo-600 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl">
                        <UserPlusIcon class="h-8 w-8" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter leading-none">Nuevo Comensal</h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Registrar en plantilla del área</p>
                    </div>
                </div>

                <form @submit.prevent="submitQuickMember" class="space-y-6">
                    <div>
                        <InputLabel value="Nombre(s)" class="text-[10px] font-black uppercase text-gray-400 mb-2 ml-2" />
                        <TextInput type="text" class="w-full !rounded-2xl" v-model="quickMemberForm.first_name" required placeholder="Ej. Juan" />
                        <InputError :message="quickMemberForm.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel value="Apellido Paterno" class="text-[10px] font-black uppercase text-gray-400 mb-2 ml-2" />
                        <TextInput type="text" class="w-full !rounded-2xl" v-model="quickMemberForm.last_name" required placeholder="Ej. Pérez" />
                        <InputError :message="quickMemberForm.errors.last_name" />
                    </div>
                    <div>
                        <InputLabel value="Apellido Materno" class="text-[10px] font-black uppercase text-gray-400 mb-2 ml-2" />
                        <TextInput type="text" class="w-full !rounded-2xl" v-model="quickMemberForm.second_last_name" placeholder="Ej. García" />
                        <InputError :message="quickMemberForm.errors.second_last_name" />
                    </div>

                    <div class="flex flex-col gap-4 pt-6">
                        <PrimaryButton class="w-full justify-center py-5 text-sm font-black uppercase tracking-widest shadow-2xl active:scale-95" :class="{ 'opacity-25': quickMemberForm.processing }" :disabled="quickMemberForm.processing">
                            <template v-if="quickMemberForm.processing">Registrando...</template>
                            <template v-else>Registrar y Habilitar</template>
                        </PrimaryButton>
                        <SecondaryButton type="button" @click="showQuickMemberModal = false" class="w-full justify-center !rounded-2xl !py-4 text-[10px] font-black uppercase tracking-widest border-none text-gray-400 hover:text-gray-600">
                            Cancelar
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.1); border-radius: 20px; }
@keyframes glow-green { 0%, 100% { border-color: rgb(74, 222, 128); box-shadow: 0 0 20px rgba(74, 222, 128, 0.3); } 50% { border-color: rgb(34, 197, 94); box-shadow: 0 0 40px rgba(34, 197, 94, 0.5); } }
.animate-glow-green { animation: glow-green 2s infinite; }
</style>
