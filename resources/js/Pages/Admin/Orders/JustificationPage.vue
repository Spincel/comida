<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import { 
    ChevronLeftIcon, 
    ClipboardDocumentListIcon,
    PencilSquareIcon,
    CheckCircleIcon,
    ClockIcon,
    UserIcon,
    CalendarDaysIcon,
    CheckBadgeIcon,
    ArrowPathIcon,
    PrinterIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    userRole: String,
    sessions: { type: Array, default: () => [] },
    orders: { type: Array, default: () => [] },
});

const page = usePage();
const user = page.props.auth.user;

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
    { text: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-900/30' },
    { text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-900/30' },
    { text: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-50 dark:bg-rose-900/30' },
    { text: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-900/30' },
];

const getProviderColor = (id) => providerColors[id % providerColors.length];

// --- Common Logic ---
const savingId = ref(null);

const autoSaveJustification = (orderId, value) => {
    savingId.value = orderId;
    router.put(route('orders.updateJustification', orderId), {
        activity_performed: value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            savingId.value = null;
        },
        onError: () => savingId.value = null
    });
};

// --- Manager Logic ---
const selectedSession = ref(null);
const filterPendingOnly = ref(false);
const searchQuery = ref('');
const showExportModal = ref(false);
const sessionToExport = ref(null);

const openExportModal = (session) => {
    sessionToExport.value = session;
    showExportModal.value = true;
};

const handleExport = (format) => {
    const session = sessionToExport.value;
    if (!session) return;

    const url = route('admin.orders.summary.pdf', { 
        provider: session.provider_id, 
        date: session.date,
        meal_type: session.meal_type,
        area_id: user.area_id,
        view_mode: 'names',
        sort: 'name',
        format: format
    });
    window.open(url, '_blank');
};

const currentSessionStats = computed(() => {
    if (!selectedSession.value) return null;
    return props.sessions.find(s => s.id === selectedSession.value.id) || selectedSession.value;
});

const openSessionJustification = (session) => {
    selectedSession.value = session;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const filteredOrders = computed(() => {
    if (!selectedSession.value) return [];
    let list = [...selectedSession.value.orders];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(o => o.user_name.toLowerCase().includes(q) || o.platillo.toLowerCase().includes(q));
    }
    if (filterPendingOnly.value) {
        list = list.filter(o => !o.activity_performed);
    }
    return list.sort((a, b) => {
        const aJustified = a.activity_performed ? 1 : 0;
        const bJustified = b.activity_performed ? 1 : 0;
        if (aJustified !== bJustified) return aJustified - bJustified;
        return a.user_name.localeCompare(b.user_name);
    });
});
</script>

<template>
    <Head title="Justificación de Actividades" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link :href="route('dashboard')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                    </Link>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Justificación de Actividades
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- VISTA GERENTE DE ÁREA -->
                <div v-if="userRole === 'area_manager'" class="space-y-8">
                    <!-- Lista de Sesiones -->
                    <div v-if="!selectedSession" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="hSession in sessions" :key="hSession.id" 
                             class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border-2 shadow-xl transition-all hover:scale-[1.02] flex flex-col relative"
                             :class="[
                                hSession.justified_count === hSession.total_orders 
                                    ? 'border-green-400 dark:border-green-500 shadow-green-50' 
                                    : (hSession.justified_count > 0 
                                        ? 'border-amber-400 dark:border-amber-500 shadow-amber-50' 
                                        : 'border-red-400 dark:border-red-500 shadow-red-50')
                             ]">
                            
                            <button @click.stop="openExportModal(hSession)" 
                                    class="absolute top-6 right-6 p-2 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all border border-gray-100 dark:border-gray-600 shadow-sm"
                                    title="Exportar Reporte">
                                <PrinterIcon class="h-5 w-5" />
                            </button>

                            <div class="flex justify-between items-start mb-6">
                                <div class="h-14 w-14 rounded-3xl flex items-center justify-center shadow-sm"
                                     :class="getProviderColor(hSession.provider_id).bg + ' ' + getProviderColor(hSession.provider_id).text">
                                    <ClipboardDocumentListIcon class="h-8 w-8" />
                                </div>
                                <div class="text-right mr-10">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ hSession.date }}</p>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-tighter"
                                          :class="mealTypeTagColors[hSession.meal_type] || 'text-indigo-500 border-indigo-100'">
                                        {{ hSession.meal_type }}
                                    </span>
                                </div>
                            </div>

                            <h5 class="font-black text-xl text-gray-800 dark:text-white mb-2 uppercase tracking-tight leading-tight flex-1"
                                :class="getProviderColor(hSession.provider_id).text">
                                {{ hSession.provider_name }}
                            </h5>
                            
                            <div class="mb-8">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Justificados:</span>
                                    <span class="text-[10px] font-black" 
                                          :class="hSession.justified_count === hSession.total_orders ? 'text-green-500' : (hSession.justified_count > 0 ? 'text-amber-500' : 'text-red-500')">
                                        {{ hSession.justified_count }} / {{ hSession.total_orders }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                    <div class="h-full transition-all duration-500" 
                                         :class="hSession.justified_count === hSession.total_orders ? 'bg-green-500' : (hSession.justified_count > 0 ? 'bg-amber-500' : 'bg-red-500')"
                                         :style="{ width: (hSession.justified_count / hSession.total_orders * 100) + '%' }"></div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col gap-3">
                                <button @click="openSessionJustification(hSession)" 
                                        class="w-full py-4 bg-gray-900 dark:bg-gray-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg">
                                    {{ hSession.justified_count === hSession.total_orders ? 'Revisar Lista' : 'Llenar Actividades' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Editor de Justificaciones -->
                    <div v-else class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-700 space-y-6">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <div class="flex items-center">
                                    <button @click="selectedSession = null" class="mr-4 p-2 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-400 hover:text-indigo-600 transition-all">
                                        <ChevronLeftIcon class="h-6 w-6" />
                                    </button>
                                    <div>
                                        <h3 class="font-black text-xl text-gray-800 dark:text-white uppercase leading-none">{{ selectedSession.provider_name }}</h3>
                                        <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mt-1">{{ selectedSession.meal_type }} • {{ selectedSession.date }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-2xl border border-green-100 dark:border-green-800 flex items-center space-x-2">
                                        <CheckBadgeIcon class="h-5 w-5 text-green-500" />
                                        <span class="text-[10px] font-black text-green-700 dark:text-green-400 uppercase tracking-widest">Auto-guardado</span>
                                    </div>
                                    <button @click="openExportModal(selectedSession)"
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-lg shadow-indigo-100 dark:shadow-none transition-all">
                                        <PrinterIcon class="h-4 w-4 mr-2" /> Exportar Reporte
                                    </button>
                                </div>
                            </div>

                            <div class="pt-6 border-t dark:border-gray-700">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center">
                                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest mr-2">Progreso de la sesión:</span>
                                        <div v-if="currentSessionStats.justified_count === currentSessionStats.total_orders" 
                                             class="flex items-center text-green-500 text-[9px] font-black uppercase animate-bounce">
                                            <CheckBadgeIcon class="h-3 w-3 mr-1" /> ¡Completado!
                                        </div>
                                    </div>
                                    <span class="text-xs font-black" :class="currentSessionStats.justified_count === currentSessionStats.total_orders ? 'text-green-500' : 'text-indigo-500'">
                                        {{ currentSessionStats.justified_count }} / {{ currentSessionStats.total_orders }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-3 rounded-full overflow-hidden border dark:border-gray-600">
                                    <div class="h-full transition-all duration-700 ease-out shadow-inner" 
                                         :class="currentSessionStats.justified_count === currentSessionStats.total_orders ? 'bg-green-500 shadow-green-200' : 'bg-indigo-500'"
                                         :style="{ width: (currentSessionStats.justified_count / currentSessionStats.total_orders * 100) + '%' }"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t dark:border-gray-700">
                                <div class="relative">
                                    <MagnifyingGlassIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input v-model="searchQuery" type="text" placeholder="Buscar por nombre o platillo..." 
                                           class="w-full pl-12 pr-4 py-3 rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500" />
                                </div>
                                <button @click="filterPendingOnly = !filterPendingOnly" 
                                        class="flex items-center justify-center gap-2 px-6 py-3 rounded-2xl border-2 transition-all font-black text-[10px] uppercase tracking-widest"
                                        :class="filterPendingOnly 
                                            ? 'border-orange-500 bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                                            : 'border-gray-100 text-gray-400 dark:border-gray-700 hover:border-indigo-200'">
                                    <FunnelIcon class="h-4 w-4" />
                                    {{ filterPendingOnly ? 'Mostrando solo faltantes' : 'Mostrar solo pendientes' }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div v-for="order in filteredOrders" :key="order.id" 
                                 class="group p-6 bg-white dark:bg-gray-800 rounded-3xl border-2 transition-all hover:shadow-lg"
                                 :class="order.activity_performed ? 'border-green-50 dark:border-green-900/10' : 'border-orange-100 dark:border-orange-900/30 animate-pulse-subtle'">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                    <div class="flex items-center space-x-4 min-w-[250px]">
                                        <div class="h-12 w-12 rounded-2xl flex items-center justify-center font-black text-xs uppercase border-2 shadow-sm"
                                             :class="order.activity_performed ? 'bg-green-50 text-green-500 border-green-100' : 'bg-orange-50 text-orange-500 border-orange-100'">
                                            {{ order.user_name.substring(0,2) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-base text-gray-800 dark:text-gray-200">{{ order.user_name }}</p>
                                            <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-tighter">{{ order.platillo }}</p>
                                        </div>
                                    </div>
                                    <div class="flex-1 relative">
                                        <textarea
                                            v-model="order.activity_performed"
                                            @blur="autoSaveJustification(order.id, order.activity_performed)"
                                            rows="2"
                                            class="block w-full rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition-all"
                                            placeholder="Escribe la actividad realizada aquí..."
                                        ></textarea>
                                        <div v-if="savingId === order.id" class="absolute right-3 bottom-3">
                                            <ArrowPathIcon class="h-4 w-4 text-indigo-500 animate-spin" />
                                        </div>
                                        <div v-else-if="order.activity_performed" class="absolute right-3 bottom-3">
                                            <CheckCircleIcon class="h-4 w-4 text-green-500" />
                                        </div>
                                        <div v-else class="absolute right-3 bottom-3 flex items-center text-[8px] font-black text-orange-500 uppercase tracking-widest">
                                            <ExclamationTriangleIcon class="h-3 w-3 mr-1" /> Pendiente
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA COMENSAL -->
                <div v-if="userRole === 'diner'" class="space-y-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div v-for="order in orders" :key="order.id" 
                             class="bg-white dark:bg-gray-800 rounded-[2rem] p-8 border-2 transition-all shadow-xl"
                             :class="order.activity_performed ? 'border-indigo-100 dark:border-indigo-900/30' : 'border-orange-100 dark:border-orange-900/30 animate-pulse-subtle'">
                            <div class="flex flex-col md:flex-row justify-between gap-8">
                                <div class="flex items-start space-x-6">
                                    <div class="h-16 w-16 rounded-3xl flex items-center justify-center text-white shrink-0 shadow-xl shadow-indigo-100 dark:shadow-none"
                                         :class="mealTypeColors[order.meal_type] || 'bg-indigo-600'">
                                        <CalendarDaysIcon class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                                  :class="mealTypeTagColors[order.meal_type] || 'bg-indigo-50 text-indigo-500'">
                                                {{ order.meal_type }}
                                            </span>
                                            <span class="text-[10px] font-bold text-gray-400">{{ order.date }}</span>
                                        </div>
                                        <h4 class="font-black text-xl text-gray-800 dark:text-white leading-tight uppercase tracking-tight">{{ order.platillo }}</h4>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">{{ order.provider_name }}</p>
                                    </div>
                                </div>

                                <div class="flex-1 relative">
                                    <InputLabel value="Mi Actividad / Justificación (Auto-guardado):" class="text-[10px] font-black uppercase text-indigo-500 mb-2" />
                                    <textarea 
                                        v-model="order.activity_performed"
                                        @blur="autoSaveJustification(order.id, order.activity_performed)"
                                        rows="3"
                                        class="block w-full rounded-[1.5rem] border-gray-200 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-indigo-500 shadow-inner"
                                        placeholder="Describe brevemente la actividad que realizaste..."
                                    ></textarea>
                                    <div v-if="savingId === order.id" class="absolute right-4 bottom-4">
                                        <ArrowPathIcon class="h-5 w-5 text-indigo-500 animate-spin" />
                                    </div>
                                    <div v-else-if="order.activity_performed" class="absolute right-4 bottom-4 text-green-500 flex items-center gap-1">
                                        <CheckBadgeIcon class="h-5 w-5" />
                                        <span class="text-[8px] font-black uppercase">Guardado</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <ExportChoiceModal 
            :show="showExportModal"
            @close="showExportModal = false"
            @select="handleExport"
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

@keyframes pulse-subtle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.95; transform: scale(0.995); }
}
.animate-pulse-subtle {
    animation: pulse-subtle 3s infinite ease-in-out;
}
</style>
