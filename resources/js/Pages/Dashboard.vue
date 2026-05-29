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
    UserPlusIcon, ArrowPathIcon, ShieldCheckIcon, MoonIcon, SunIcon, ChevronDownIcon, PowerIcon, UsersIcon,
    MagnifyingGlassIcon, SwatchIcon
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
const expandedHistorySessions = ref({});
const authStatus = ref({});
// --- BENTO V2.0 STATE ---
const bentoTurno = ref('Comida');
const bentoProviderId = ref(null);
const bentoSelectedAreas = ref([]);
const areaSearchTerm = ref('');

// --- AUTO-SYNC SELECTED AREAS WITH ACTIVE SESSION ---
// Only reset selection when Turno or Provider changes
watch([bentoTurno, bentoProviderId], () => {
    const session = props.openSessions?.find(s => 
        s.meal_type === bentoTurno.value && 
        s.provider_id === bentoProviderId.value
    );
    if (session) {
        const ids = Array.isArray(session.selected_area_ids) 
            ? session.selected_area_ids 
            : JSON.parse(session.selected_area_ids || '[]');
        bentoSelectedAreas.value = ids.map(id => parseInt(id));
    } else {
        bentoSelectedAreas.value = [];
    }
}, { immediate: true });

// Sync from props only if a session exists (to keep current active areas updated without wiping drafts)
watch(() => props.openSessions, (newSessions) => {
    const session = newSessions?.find(s => 
        s.meal_type === bentoTurno.value && 
        s.provider_id === bentoProviderId.value
    );
    if (session) {
        const ids = Array.isArray(session.selected_area_ids) 
            ? session.selected_area_ids 
            : JSON.parse(session.selected_area_ids || '[]');
        bentoSelectedAreas.value = ids.map(id => parseInt(id));
    }
}, { deep: true });

const filteredBentoAreas = computed(() => {
    if (!areaSearchTerm.value) return props.areas;
    return props.areas.filter(a => a.name.toLowerCase().includes(areaSearchTerm.value.toLowerCase()));
});

const selectAllAreas = () => {
    bentoSelectedAreas.value = props.areas.map(a => a.id);
};

const deselectAllAreas = () => {
    bentoSelectedAreas.value = [];
};

onMounted(() => {
    updateTimers(); 
    const timerIntervalId = setInterval(updateTimers, 1000);
    
    const refreshIntervalId = setInterval(() => { 
        router.reload({ preserveScroll: true, only: ['providers', 'dishSummaryToday', 'openSessions', 'myOrdersToday', 'availableMenus', 'teamOrders'] }); 
    }, 5000);

    onUnmounted(() => {
        clearInterval(timerIntervalId);
        clearInterval(refreshIntervalId);
    });

    if (props.providers?.length > 0) {
        bentoProviderId.value = props.providers[0].id;
    }

    // Default tab for managers and diners
    if (user.role === 'area_manager' || user.role === 'diner') {
        activeTab.value = 'my-area';
    }
});

const toggleBentoArea = (areaId) => {
    const idx = bentoSelectedAreas.value.indexOf(areaId);
    if (idx > -1) bentoSelectedAreas.value.splice(idx, 1);
    else bentoSelectedAreas.value.push(areaId);
};

const submitBentoActivation = () => {
    if (!bentoProviderId.value) return alert('Por favor, selecciona un proveedor.');
    if (bentoSelectedAreas.value.length === 0) return alert('Debes seleccionar al menos un área de trabajo para habilitar el servicio.');
    
    // Use local date YYYY-MM-DD to avoid timezone shifts at night
    const localDate = new Date().toLocaleDateString('en-CA'); 

    router.post(route('dashboard.providers.activate', bentoProviderId.value), {
        date: localDate,
        status: 'open',
        selected_area_ids: bentoSelectedAreas.value,
        meal_type: bentoTurno.value,
        conflict_resolution: 'merge'
    }, {
        preserveScroll: true,
    });
};

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

const toggleHistorySession = (sessionId) => {
    if (expandedHistorySessions.value[sessionId]) {
        delete expandedHistorySessions.value[sessionId];
    } else {
        expandedHistorySessions.value[sessionId] = true;
    }
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

const pendingSubmissionCount = computed(() => {
    let count = 0;
    props.teamOrders?.forEach(m => {
        count += m.orders?.filter(o => o.status === 'submitted_by_user').length || 0;
    });
    return count;
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
            daily_menu_id: null 
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

// --- Modals & Actions ---
const showActivateMenuModal = ref(false), selectedProviderForActivation = ref(null), activateModalMode = ref('new'), sessionToEdit = ref(null);
const openActivateMenuModal = (provider) => { selectedProviderForActivation.value = provider; activateModalMode.value = 'new'; sessionToEdit.value = null; showActivateMenuModal.value = true; };
const openEditSessionModal = (status, provider) => { selectedProviderForActivation.value = provider; activateModalMode.value = 'edit'; sessionToEdit.value = status; showActivateMenuModal.value = true; };
const showDeactivateMenuModal = ref(false), sessionToDeactivate = ref(null);
const openDeactivateMenuModal = (session, provider) => { sessionToDeactivate.value = { ...session, provider_name: provider?.name || session.provider?.name }; showDeactivateMenuModal.value = true; };
const confirmDeactivation = () => { 
    router.patch(route('dashboard.providers.deactivate', sessionToDeactivate.value.provider_id), { 
        date: sessionToDeactivate.value.date, 
        meal_type: sessionToDeactivate.value.meal_type 
    }, { 
        preserveScroll: true, 
        onSuccess: () => { showDeactivateMenuModal.value = false; } 
    }); 
};
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

const isSessionOpenForMe = (mealType) => props.openSessions?.some(s => s.meal_type === mealType && (s.is_open_for_my_area || s.selected_area_ids?.includes(parseInt(user.area_id))));

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
const confirmSubmitAreaOrders = () => { 
    if (!pendingSubmissionMealType.value) return;
    const ids = [];
    props.teamOrders.forEach(m => {
        const o = m.orders?.find(ord => ord.meal_type === pendingSubmissionMealType.value && ord.status === 'submitted_by_user');
        if (o) ids.push(o.id);
    });
    if (ids.length === 0) { showSubmitConfirmation.value = false; return; }
    router.post(route('orders.areaSubmit'), { order_ids: ids, meal_type: pendingSubmissionMealType.value }, { 
        preserveScroll: true, onSuccess: () => { setTimeout(() => { showSubmitConfirmation.value = false; selectedOrderIds.value[pendingSubmissionMealType.value] = []; router.reload({ only: ['teamOrders', 'openSessions', 'dishSummaryToday'] }); }, 1000); } 
    }); 
};

const prepareAndSubmitBatch = () => {
    if (!activeAuthSession.value) return;
    const mType = activeAuthSession.value.meal_type;
    const ids = [];
    props.teamOrders.forEach(m => {
        const o = m.orders?.find(ord => ord.meal_type === mType && ord.status === 'submitted_by_user');
        if (o) ids.push(o.id);
    });
    if (ids.length === 0) return alert('No hay nuevos pedidos pendientes de confirmación final.');
    pendingSubmissionMealType.value = mType;
    showSubmitConfirmation.value = true;
};

// --- Formats & Styles ---
const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200', 'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200', 'Cena': 'bg-purple-100 text-purple-700 border-purple-200', 'Extra': 'bg-teal-100 text-teal-700 border-teal-200' };
const getProviderTheme = (id) => [ 'bg-indigo-600', 'bg-emerald-600', 'bg-rose-600', 'bg-amber-600', 'bg-purple-600', 'bg-cyan-600' ][id % 6];
</script>

<template>
    <Head title="Dashboard Principal" />

    <AuthenticatedLayout>
        
        <!-- MAIN BENTO CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- LEFT COLUMN: OPERATIONAL CONTROL (ADQ/ADMIN) -->
            <div v-if="(user.role === 'admin' || user.role === 'acquisitions_manager') && activeTab === 'global'" class="lg:col-span-8 space-y-8">
                
                <!-- CARD CONTROL OPERATIVO -->
                <div class="bg-white dark:bg-gray-900 rounded-[3.5rem] p-10 shadow-xl border border-slate-100 dark:border-gray-800 relative overflow-hidden">
                    <div v-if="openSessions.length > 0" class="absolute top-8 right-8 z-10 flex flex-col gap-2 items-end">
                        <button v-for="session in openSessions" :key="'fin-' + session.id"
                                @click="openDeactivateMenuModal(session, session.provider)" 
                                class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg shadow-rose-600/20 flex items-center gap-3 transition-all hover:scale-105 active:scale-95 group">
                            <PowerIcon class="h-3.5 w-3.5 group-hover:animate-pulse" stroke-width="3" />
                            <span>Finalizar {{ session.meal_type }}: <span class="opacity-60">{{ session.provider?.name }}</span></span>
                        </button>
                    </div>

                    <div class="flex items-center gap-4 mb-2">
                        <ClockIcon class="h-6 w-6 text-orange-500" />
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tighter">Control Operativo: Sesiones SICOA</h2>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-10">Apertura y monitorización en tiempo real.</p>

                    <!-- PASOS DE CONFIGURACIÓN -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10">
                        <div>
                            <p class="text-[11px] font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 ml-2">1. Turno del Servicio:</p>
                            <div class="flex gap-3">
                                <button v-for="t in ['Desayuno', 'Comida', 'Cena']" :key="t" @click="bentoTurno = t"
                                        class="flex-1 py-4 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all border-2"
                                        :class="bentoTurno === t ? 'bg-indigo-600 text-white border-indigo-600 shadow-xl scale-105' : 'bg-white dark:bg-gray-800 text-slate-400 border-slate-100 dark:border-gray-700 hover:border-slate-200 dark:hover:border-gray-600'">
                                    {{ t }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 ml-2">2. Proveedor / Catering:</p>
                            <select v-model="bentoProviderId" class="w-full bg-slate-50 dark:bg-gray-800 border-slate-100 dark:border-gray-700 rounded-2xl py-4 px-6 text-[11px] font-black uppercase text-slate-600 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-inner">
                                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- PASO 3: ÁREAS -->
                    <div class="mb-12">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                            <div class="flex items-center gap-3">
                                <p class="text-[11px] font-black text-slate-800 dark:text-white uppercase tracking-widest ml-2">3. Habilitar Áreas:</p>
                                <div class="flex bg-slate-100 dark:bg-gray-800 p-1 rounded-xl border dark:border-gray-700">
                                    <button @click="selectAllAreas" class="px-3 py-1 text-[8px] font-black uppercase text-indigo-600 dark:text-indigo-400 hover:bg-white dark:hover:bg-gray-700 rounded-lg transition-all">Todas</button>
                                    <button @click="deselectAllAreas" class="px-3 py-1 text-[8px] font-black uppercase text-slate-400 hover:bg-white dark:hover:bg-gray-700 rounded-lg transition-all">Ninguna</button>
                                </div>
                            </div>
                            
                            <!-- MINI BUSCADOR -->
                            <div class="relative w-full md:w-64">
                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                <input type="text" v-model="areaSearchTerm" placeholder="Buscar área..." 
                                       class="w-full pl-9 pr-4 py-2.5 bg-slate-50 dark:bg-gray-800 border-slate-100 dark:border-gray-700 rounded-xl text-[10px] font-bold uppercase focus:ring-indigo-500 shadow-inner" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 max-h-[320px] overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="areaS in filteredBentoAreas" :key="areaS.id" @click="toggleBentoArea(areaS.id)"
                                 class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex justify-between items-center group shadow-sm"
                                 :class="bentoSelectedAreas.includes(areaS.id) ? 'bg-indigo-50 dark:bg-indigo-950/20 border-indigo-500' : 'bg-slate-50 dark:bg-gray-800 border-transparent dark:hover:border-gray-700 hover:border-slate-200'">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-2 rounded-full" :class="bentoSelectedAreas.includes(areaS.id) ? 'bg-indigo-600 animate-pulse' : 'bg-slate-300 dark:bg-gray-700'"></div>
                                    <span class="text-[10px] font-black uppercase text-slate-700 dark:text-gray-300 truncate max-w-[120px]">{{ areaS.name }}</span>
                                </div>
                                <span class="bg-white dark:bg-gray-900 border dark:border-gray-700 text-slate-400 text-[8px] font-black px-2 py-1 rounded-lg shadow-sm group-hover:text-indigo-600 transition-colors">
                                    {{ areaS.user_count || 0 }} p
                                </span>
                            </div>
                            
                            <!-- EMPTY SEARCH STATE -->
                            <div v-if="filteredBentoAreas.length === 0" class="col-span-full py-10 text-center bg-slate-50 dark:bg-gray-800/50 rounded-2xl border-2 border-dashed">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">No se encontraron áreas con ese nombre</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: STATS (ADMIN/ADQ) -->
            <div v-if="(user.role === 'admin' || user.role === 'acquisitions_manager') && activeTab === 'global'" class="lg:col-span-4 space-y-8">
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-10 shadow-xl border border-slate-100 dark:border-gray-800">
                    <div class="flex items-center gap-4 mb-2">
                        <ShieldCheckIcon class="h-6 w-6 text-orange-500" />
                        <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter">Auditoría & Control</h3>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 leading-relaxed uppercase tracking-widest mb-8">Estado de cumplimiento de raciones.</p>

                    <div class="space-y-4">
                        <div class="bg-slate-50 dark:bg-gray-800 p-6 rounded-3xl border border-slate-100 dark:border-gray-700">
                            <div class="flex justify-between items-center mb-6">
                                <p class="text-[11px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Estado Operativo</p>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black uppercase" :class="openSessions.length > 0 ? 'text-emerald-500' : 'text-orange-500'">{{ openSessions.length > 0 ? 'EN LÍNEA' : 'PASIVO' }}</span>
                                    <div class="h-2 w-2 rounded-full" :class="openSessions.length > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-orange-500'"></div>
                                </div>
                            </div>
                            
                            <!-- MINI LISTA DE SESIONES ACTIVAS (SUTIL) -->
                            <div v-if="openSessions.length > 0" class="space-y-3">
                                <div v-for="session in openSessions" :key="'mini-' + session.id" 
                                     class="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-slate-100 dark:border-gray-800 shadow-sm group">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[10px] font-black text-slate-800 dark:text-white uppercase truncate">{{ session.provider?.name }}</p>
                                            <span class="text-[7px] font-black uppercase text-indigo-500">{{ session.meal_type }}</span>
                                        </div>
                                        <p class="text-xs font-black tabular-nums text-indigo-600 shrink-0">{{ activeTimers[session.id] || '00:00:00' }}</p>
                                    </div>
                                    <div class="flex justify-between items-center mt-3">
                                        <div class="h-1 flex-1 bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden mr-4">
                                            <div class="h-full bg-indigo-500" style="width: 100%"></div>
                                        </div>
                                        <Link :href="route('admin.orders.summary', { provider: session.provider_id, date: session.date, meal_type: session.meal_type })" 
                                              class="text-[8px] font-black uppercase text-slate-400 hover:text-indigo-600 transition-colors whitespace-nowrap">
                                            Monitor →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-[9px] text-center text-slate-400 uppercase font-bold py-4">Sin turnos activos</p>
                        </div>

                        <div class="bg-slate-50 dark:bg-gray-800 p-6 rounded-3xl border border-slate-100 dark:border-gray-700 flex justify-between items-center">
                            <p class="text-[11px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Comensales:</p>
                            <p class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                                {{ activeTotalOrders }} <span class="text-[10px] text-slate-400 uppercase">Matriculados</span>
                            </p>
                        </div>
                    </div>

                    <!-- BOTÓN INICIAR SESIÓN (MOVIDO A AUDITORÍA) -->
                    <div class="mt-8">
                        <button @click="submitBentoActivation" 
                                class="w-full py-6 rounded-[2rem] bg-gradient-to-r from-indigo-600 via-indigo-500 to-indigo-600 bg-[length:200%_auto] animate-gradient text-white text-[11px] font-black uppercase tracking-[0.3em] shadow-2xl shadow-indigo-500/40 hover:scale-[1.01] active:scale-95 transition-all flex items-center justify-center gap-4">
                            <ArrowPathIcon class="h-5 w-5" />
                            Iniciar Buffet & Turno
                        </button>
                    </div>
                </div>
                
                <!-- QUICK ACTIONS -->
                <div class="bg-indigo-600 rounded-[3rem] p-10 text-white shadow-2xl shadow-indigo-900/20">
                    <h4 class="text-lg font-black uppercase tracking-tighter mb-8 ml-2">Herramientas</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <Link :href="route('admin.history')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                            <ClipboardDocumentListIcon class="h-6 w-6" />
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]">📖 Historial</span>
                        </Link>
                        <Link :href="route('admin.reports')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                            <TableCellsIcon class="h-6 w-6" />
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]">📊 Reportes</span>
                        </Link>
                        <div class="border-t border-white/10 my-2"></div>
                        <Link :href="route('providers.index')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                            <BuildingStorefrontIcon class="h-6 w-6" />
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]">🚚 Proveedores</span>
                        </Link>
                        <Link :href="route('areas.index')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                            <BuildingOfficeIcon class="h-6 w-6" />
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]">🏢 Áreas</span>
                        </Link>
                        <Link :href="route('users.index')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                            <UsersIcon class="h-6 w-6" />
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]">👥 Usuarios</span>
                        </Link>

                        <!-- ADMIN ONLY CONFIGURATION SECTION -->
                        <template v-if="user.role === 'admin'">
                            <div class="border-t border-white/10 my-4"></div>
                            <h5 class="text-[10px] font-black uppercase opacity-60 tracking-[0.3em] mb-4 ml-4">Configuración del Sistema</h5>
                            
                            <Link :href="route('admin.settings.interface')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                                <SwatchIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">🎨 Interfaz y Logo</span>
                            </Link>
                            <Link :href="route('admin.settings.reports')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                                <DocumentChartBarIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">📄 Conf. Reportes</span>
                            </Link>
                            <Link :href="route('admin.settings.roles')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                                <ShieldCheckIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">🔐 Roles y Permisos</span>
                            </Link>
                            <Link :href="route('admin.utilities.data')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                                <WrenchScrewdriverIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">🛠️ Mantenimiento</span>
                            </Link>
                            <Link :href="route('admin.sessions.logs')" class="flex items-center gap-4 bg-white/10 hover:bg-white/20 p-5 rounded-2xl border border-white/20 transition-all">
                                <ListBulletIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">📜 Bitácora de Logs</span>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN GERENTE DE ÁREA / DINER (RESTORED) -->
            <div v-if="(user.role === 'area_manager' || (user.role === 'admin' && activeTab === 'my-area') || user.role === 'diner')" 
                 class="lg:col-span-12 grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                
                <div v-if="user.role !== 'diner'" class="md:col-span-3 space-y-6">
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 shadow-xl border border-slate-100 dark:border-gray-800 sticky top-24">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 mb-8 px-4">Panel Local</h4>
                        <div class="space-y-3">
                            <button v-for="mode in [
                                {id:'auth',l: operationMode === 'simple' ? 'Asignar' : 'Habilitar', s: operationMode === 'simple' ? 'Platillos' : 'Personal', i:UserIcon, c:'indigo'},
                                {id:'menu',l:'Mi Menú', s:'Personal', i:BuildingStorefrontIcon, c:'orange', hideInSimple: true},
                                {id:'plantilla',l:'Plantilla', s:'Mi Equipo', i:UsersIcon, c:'emerald'},
                                {id:'justification',l:'Justificar', s:'Historial', i:PencilSquareIcon, c:'rose'}
                            ]" :key="mode.id" 
                            v-show="!(operationMode === 'simple' && mode.hideInSimple)"
                            @click="sidebarMode = mode.id" 
                            class="w-full p-4 rounded-2xl border-2 transition-all flex items-center gap-4 text-left group relative overflow-hidden active:scale-95" 
                            :class="[ sidebarMode === mode.id ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 shadow-md scale-[1.02]' : 'border-transparent hover:bg-slate-50 dark:hover:bg-gray-800' ]">
                                <div class="h-12 w-12 rounded-xl flex items-center justify-center transition-all shadow-sm" :class="sidebarMode === mode.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-gray-800 text-slate-400'">
                                    <component :is="mode.i" class="h-6 w-6" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-[13px] font-black uppercase tracking-tight" :class="sidebarMode === mode.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500'">{{ mode.l }}</p>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">{{ mode.s }}</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="user.role === 'diner' ? 'md:col-span-12' : 'md:col-span-9'" class="space-y-8">
                    
                    <!-- GERENTE: ASIGNAR PLATILLOS -->
                    <div v-if="sidebarMode === 'auth' && activeAuthSession" class="bg-white dark:bg-gray-900 rounded-[3.5rem] border border-slate-100 dark:border-gray-800 shadow-xl overflow-hidden">
                        <div class="px-10 py-8 text-white flex justify-between items-center shadow-lg" :class="getProviderTheme(activeAuthSession.provider_id)">
                            <div class="flex items-center gap-8">
                                <div class="h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20 shadow-inner"><UserIcon class="h-8 w-8 text-white" /></div>
                                <div>
                                    <h5 class="text-3xl font-black uppercase tracking-tighter leading-none mb-2">
                                        {{ operationMode === 'simple' ? 'Asignar Platillos' : 'Habilitar Personal' }}
                                    </h5>
                                    <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">{{ activeAuthSession.meal_type }} • {{ activeAuthSession.provider?.name }}</p>
                                </div>
                            </div>
                            <div v-if="operationMode === 'complete'" class="flex gap-3">
                                <button @click="selectAllForAuth(activeAuthSession.id)" class="px-6 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl text-[10px] font-black uppercase shadow-lg transition-all">Todos</button>
                                <button @click="deselectAllForAuth(activeAuthSession.id)" class="px-6 py-2.5 bg-black/10 hover:bg-black/20 rounded-xl text-[10px] font-black uppercase shadow-lg transition-all">Ninguno</button>
                            </div>
                        </div>
                        
                        <div class="p-10">
                            <p class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 mb-8 text-center">{{ operationMode === 'simple' ? 'Toca a un integrante para elegir su platillo:' : 'Toca el nombre para autorizar automáticamente:' }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <div v-for="m in teamOrders" :key="'auth-' + activeAuthSession.id + '-' + m.id" @click="openSimpleModeSelector(m)" 
                                     class="flex flex-col p-4 rounded-[2rem] border-2 transition-all cursor-pointer relative group shadow-sm hover:scale-[1.02]" 
                                     :class="[ operationMode === 'simple' ? (m.orders.some(o => o.meal_type === activeAuthSession.meal_type) ? 'bg-indigo-50 dark:bg-indigo-950/20 border-indigo-500 shadow-indigo-100 dark:shadow-none' : 'bg-slate-50 dark:bg-gray-800 border-transparent opacity-80') : (authorizedUserIds[activeAuthSession.id]?.includes(m.id) ? 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-500 text-emerald-700 dark:text-emerald-400 shadow-emerald-500/10' : 'bg-slate-50 dark:bg-gray-800 border-transparent text-slate-400 dark:text-gray-500 opacity-60') ]">
                                    <div class="flex items-center mb-3">
                                        <img :src="m.avatar_url" class="h-8 w-8 rounded-full mr-3 border-2 border-white dark:border-gray-700 shadow-md object-cover transition-transform group-hover:scale-110" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[9px] font-black uppercase truncate tracking-tight text-slate-800 dark:text-gray-300 leading-tight">{{ m.name }}</p>
                                        </div>
                                    </div>
                                    <div v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type)" class="mt-1 space-y-1 border-t pt-3 border-indigo-100 dark:border-indigo-900">
                                        <p class="text-[8px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter leading-none">{{ m.orders.find(o => o.meal_type === activeAuthSession.meal_type).platillo }}</p>
                                        <p v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type).preferences" class="text-[7px] font-bold text-rose-500 italic leading-none truncate">* {{ m.orders.find(o => o.meal_type === activeAuthSession.meal_type).preferences }}</p>
                                    </div>
                                    <div v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type)" class="absolute -top-1 -right-1 flex gap-1">
                                        <button v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type).status !== 'submitted_by_manager'" @click.stop="deleteOrder(m.orders.find(o => o.meal_type === activeAuthSession.meal_type).id)" 
                                                class="bg-white dark:bg-gray-800 text-rose-500 rounded-full p-1 shadow-lg border border-rose-100 dark:border-gray-900 hover:bg-rose-50 transition-all">
                                            <TrashIcon class="h-3 w-3" />
                                        </button>
                                        <div v-if="m.orders.find(o => o.meal_type === activeAuthSession.meal_type).status === 'submitted_by_manager'" 
                                             class="bg-emerald-500 text-white rounded-full p-1 shadow-md border border-white dark:border-gray-700"><CheckBadgeIcon class="h-3.5 w-3.5" /></div>
                                        <div v-else class="bg-indigo-600 text-white rounded-full p-1 shadow-md border border-white dark:border-gray-700"><CheckBadgeIcon class="h-3.5 w-3.5" /></div>
                                    </div>
                                </div>

                                <div @click="showQuickMemberModal = true" 
                                     class="flex flex-col items-center justify-center p-4 rounded-[2rem] border-2 border-dashed border-slate-200 dark:border-gray-700 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-all cursor-pointer group shadow-sm">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-gray-800 group-hover:bg-indigo-600 flex items-center justify-center text-slate-400 group-hover:text-white transition-all mb-2 shadow-inner">
                                        <PlusIcon class="h-5 w-5" />
                                    </div>
                                    <p class="text-[7px] font-black uppercase text-slate-400 group-hover:text-indigo-600 tracking-widest text-center">Nuevo<br/>Comensal</p>
                                </div>
                            </div>

                            <div v-if="teamOrders.some(m => m.orders.some(o => o.meal_type === activeAuthSession.meal_type))" class="mt-12 p-8 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-[2.5rem] border-2 border-dashed border-indigo-100 dark:border-indigo-900/30 flex flex-col items-center">
                                <button @click="pendingSubmissionMealType = activeAuthSession.meal_type; prepareAndSubmitBatch();" 
                                        :disabled="teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user'))"
                                        class="px-16 py-5 rounded-[2rem] font-black uppercase text-[10px] tracking-[0.4em] shadow-xl transition-all"
                                        :class="teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user'))
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 border-emerald-100'
                                            : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-500/20'">
                                    {{ teamOrders.every(m => !m.orders.some(o => o.meal_type === activeAuthSession.meal_type && o.status === 'submitted_by_user')) ? 'Pedido Confirmado' : 'Enviar Pedido del Equipo' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- VISTA PLANTILLA (EQUIPO) -->
                    <div v-else-if="sidebarMode === 'plantilla'" class="bg-white dark:bg-gray-900 rounded-[3.5rem] border border-slate-100 dark:border-gray-800 shadow-xl overflow-hidden">
                        <div class="px-10 py-8 bg-emerald-600 text-white flex justify-between items-center shadow-lg">
                            <div class="flex items-center gap-8">
                                <div class="h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20 shadow-inner"><UsersIcon class="h-8 w-8 text-white" /></div>
                                <div>
                                    <h5 class="text-3xl font-black uppercase tracking-tighter leading-none mb-2">Plantilla del Área</h5>
                                    <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">{{ area?.name }}</p>
                                </div>
                            </div>
                            <Link :href="route('team.index')" class="px-6 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl text-[10px] font-black uppercase shadow-lg transition-all flex items-center gap-2">
                                <PencilSquareIcon class="h-4 w-4" />
                                Gestionar Plantilla
                            </Link>
                        </div>
                        
                        <div class="p-10">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <div v-for="m in teamOrders" :key="'plantilla-' + m.id" 
                                     class="flex items-center p-4 rounded-[2rem] border-2 border-slate-50 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/50 transition-all hover:scale-[1.02] shadow-sm">
                                    <img :src="m.avatar_url" class="h-12 w-12 rounded-2xl mr-4 border-2 border-white dark:border-gray-700 shadow-md object-cover" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-black uppercase truncate tracking-tight text-slate-800 dark:text-gray-200 leading-tight">{{ m.name }}</p>
                                        <p class="text-[8px] font-bold text-emerald-500 uppercase tracking-widest mt-1">Activo</p>
                                    </div>
                                </div>

                                <div @click="showQuickMemberModal = true" 
                                     class="flex items-center justify-center p-4 rounded-[2rem] border-2 border-dashed border-slate-200 dark:border-gray-700 hover:border-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/10 transition-all cursor-pointer group shadow-sm">
                                    <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-gray-800 group-hover:bg-emerald-600 flex items-center justify-center text-slate-400 group-hover:text-white transition-all mr-4 shadow-inner">
                                        <PlusIcon class="h-5 w-5" />
                                    </div>
                                    <p class="text-[9px] font-black uppercase text-slate-400 group-hover:text-emerald-600 tracking-widest">Alta Rápida</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- VISTA JUSTIFICACIÓN (HISTORIAL RECIENTE) -->
                    <div v-else-if="sidebarMode === 'justification'" class="bg-white dark:bg-gray-900 rounded-[3.5rem] border border-slate-100 dark:border-gray-800 shadow-xl overflow-hidden">
                        <div class="px-10 py-8 bg-rose-600 text-white flex justify-between items-center shadow-lg">
                            <div class="flex items-center gap-8">
                                <div class="h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20 shadow-inner"><PencilSquareIcon class="h-8 w-8 text-white" /></div>
                                <div>
                                    <h5 class="text-3xl font-black uppercase tracking-tighter leading-none mb-2">Justificar Historial</h5>
                                    <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">Registros Recientes del Área</p>
                                </div>
                            </div>
                            <Link :href="route('justification.index')" class="px-6 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl text-[10px] font-black uppercase shadow-lg transition-all flex items-center gap-2">
                                <CalendarDaysIcon class="h-4 w-4" />
                                Ver Todo
                            </Link>
                        </div>
                        
                        <div class="p-10 space-y-6">
                            <div v-if="groupedHistory.length > 0" class="space-y-4">
                                <div v-for="session in groupedHistory" :key="'history-' + session.id + session.date + session.meal_type" 
                                     class="bg-slate-50/50 dark:bg-gray-800/50 rounded-[2.5rem] border-2 border-slate-50 dark:border-gray-800 overflow-hidden transition-all hover:border-rose-100 dark:hover:border-rose-950">
                                    
                                    <!-- Cabecera de la Sesión -->
                                    <div class="p-6 flex flex-col md:flex-row md:items-center gap-6 cursor-pointer" @click="toggleHistorySession(session.id + session.date + session.meal_type)">
                                        <div class="h-12 w-12 rounded-2xl bg-white dark:bg-gray-700 flex flex-col items-center justify-center shadow-sm border border-slate-100 dark:border-gray-600">
                                            <span class="text-[8px] font-black text-rose-500 leading-none">{{ session.date.split('-')[2] }}</span>
                                            <span class="text-[6px] font-bold text-slate-400 uppercase leading-none mt-1">{{ new Date(session.date + 'T12:00:00').toLocaleString('es-ES', { month: 'short' }).replace('.', '') }}</span>
                                        </div>
                                        <div class="flex-1">
                                            <h6 class="text-xs font-black uppercase text-slate-800 dark:text-gray-200 tracking-tight">{{ session.provider_name }}</h6>
                                            <div class="flex items-center gap-3 mt-1">
                                                <span class="text-[8px] font-bold px-2 py-0.5 bg-indigo-100 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-lg uppercase tracking-widest">{{ session.meal_type }}</span>
                                                <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">• {{ session.total_orders }} Pedidos</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Botón de Evidencia -->
                                        <button @click.stop="triggerEvidenceUpload(session.id)" 
                                                class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 transition-all shadow-sm group/btn active:scale-95"
                                                :class="session.evidence_url ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-white border-slate-100 text-slate-400 hover:border-rose-200 hover:text-rose-600'">
                                            <PhotoIcon class="h-4 w-4" />
                                            <span class="text-[8px] font-black uppercase tracking-widest">{{ session.evidence_url ? 'Evidencia Lista' : 'Subir Evidencia' }}</span>
                                            <CheckBadgeIcon v-if="session.evidence_url" class="h-3 w-3" />
                                        </button>

                                        <div class="flex items-center gap-4">
                                            <div class="text-right hidden sm:block">
                                                <div class="h-1.5 w-16 bg-slate-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 transition-all" :style="{ width: (session.justified_count / session.total_orders * 100) + '%' }"></div>
                                                </div>
                                            </div>
                                            <ChevronDownIcon class="h-5 w-5 text-slate-300 transition-transform duration-300" :class="{ 'rotate-180': expandedHistorySessions[session.id + session.date + session.meal_type] }" />
                                        </div>
                                    </div>

                                    <!-- Detalle de Pedidos para Justificar -->
                                    <div v-if="expandedHistorySessions[session.id + session.date + session.meal_type]" class="px-6 pb-6 pt-2 space-y-3 border-t-2 border-slate-100 dark:border-gray-800/50 bg-white/50 dark:bg-gray-900/50">
                                        <div v-for="order in session.orders" :key="order.id" class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-gray-800/50 border border-slate-100 dark:border-gray-800 group/order">
                                            <div class="flex items-center gap-3 w-full sm:w-1/3">
                                                <img :src="order.avatar_url" class="h-8 w-8 rounded-full border-2 border-white dark:border-gray-700 shadow-sm" />
                                                <div class="min-w-0">
                                                    <p class="text-[9px] font-black uppercase text-slate-700 dark:text-gray-300 truncate">{{ order.user_name }}</p>
                                                    <p class="text-[8px] font-bold text-indigo-500 uppercase truncate tracking-tighter">{{ order.platillo }}</p>
                                                </div>
                                            </div>
                                            <div class="flex-1 w-full relative">
                                                <textarea v-model="order.activity_performed" 
                                                          @blur="saveActivity(order.id, order.activity_performed)" 
                                                          placeholder="Justificación / Actividad realizada..." 
                                                          rows="1" 
                                                          class="w-full bg-white dark:bg-gray-900 border-none rounded-xl p-3 text-[9px] font-bold text-slate-600 dark:text-gray-300 shadow-inner focus:ring-1 focus:ring-rose-500 resize-none"></textarea>
                                                <div v-if="processingJustifications[order.id]" class="absolute right-3 top-1/2 -translate-y-1/2">
                                                    <ArrowPathIcon class="h-4 w-4 text-rose-500 animate-spin" />
                                                </div>
                                                <div v-else-if="order.activity_performed" class="absolute right-3 top-1/2 -translate-y-1/2">
                                                    <CheckBadgeIcon class="h-4 w-4 text-emerald-500" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-20 bg-slate-50/50 dark:bg-gray-800/30 rounded-[3rem] border-2 border-dashed border-slate-200 dark:border-gray-800">
                                <ClockIcon class="h-12 w-12 text-slate-200 mx-auto mb-4" />
                                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">No hay historial reciente para justificar</p>
                            </div>
                        </div>
                    </div>

                    <!-- VISTA POR DEFECTO / COMENSAL / SIN SESIÓN ACTIVA (Cuando sidebarMode es auth) -->
                    <div v-else-if="sidebarMode === 'auth' || user.role === 'diner'" class="bg-white dark:bg-gray-900 rounded-[3.5rem] p-16 shadow-xl border border-indigo-100 dark:border-gray-800 text-center">
                        <div class="h-24 w-24 bg-indigo-50 dark:bg-indigo-950/30 rounded-[2.5rem] flex items-center justify-center mx-auto mb-8 shadow-inner"><UserGroupIcon class="h-12 w-12 text-indigo-500" /></div>
                        <h5 class="text-4xl font-black text-indigo-900 dark:text-white uppercase tracking-tighter leading-none mb-6">Gestión de Comedor</h5>
                        <p class="text-sm font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest leading-relaxed max-w-2xl mx-auto">Tu Gerente de Área es responsable de gestionar los platillos hoy.</p>
                    </div>

                    <!-- FALLBACK DE SEGURIDAD -->
                    <div v-else class="text-center p-20 bg-white dark:bg-gray-900 rounded-[3rem] border border-slate-100 dark:border-gray-800">
                        <p class="text-slate-400 uppercase font-black tracking-widest text-[10px]">Selecciona una opción del panel lateral para continuar</p>
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

        <!-- MODAL ALTA RÁPIDA -->
        <Modal :show="showQuickMemberModal" @close="showQuickMemberModal = false" max-width="md">
            <div class="p-10 dark:bg-gray-900">
                <div class="flex items-center gap-6 mb-10">
                    <div class="h-16 w-16 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-xl"><UserPlusIcon class="h-8 w-8" /></div>
                    <div><h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Nuevo Comensal</h3><p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Registrar en plantilla</p></div>
                </div>
                <form @submit.prevent="submitQuickMember" class="space-y-6">
                    <div><InputLabel value="Nombre" class="text-[10px] uppercase font-black ml-2" /><TextInput type="text" class="w-full !rounded-2xl dark:bg-gray-800" v-model="quickMemberForm.first_name" required /></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><InputLabel value="A. Paterno" class="text-[10px] uppercase font-black ml-2" /><TextInput type="text" class="w-full !rounded-2xl dark:bg-gray-800" v-model="quickMemberForm.last_name" required /></div>
                        <div><InputLabel value="A. Materno" class="text-[10px] uppercase font-black ml-2" /><TextInput type="text" class="w-full !rounded-2xl dark:bg-gray-800" v-model="quickMemberForm.second_last_name" /></div>
                    </div>
                    <PrimaryButton class="w-full justify-center py-5 text-sm font-black uppercase tracking-widest" :disabled="quickMemberForm.processing">Registrar Ahora</PrimaryButton>
                </form>
            </div>
        </Modal>

        <!-- INPUT OCULTO PARA EVIDENCIA -->
        <input type="file" ref="evidenceFileInput" class="hidden" accept="image/*" @change="handleEvidenceFileChange" />
    </AuthenticatedLayout>
</template>

<style>
@keyframes gradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
.animate-gradient { animation: gradient 3s ease infinite; }
</style>
