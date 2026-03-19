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
    ExclamationTriangleIcon,
    ChatBubbleLeftRightIcon,
    BuildingOfficeIcon,
    PhotoIcon,
    ArrowDownTrayIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    userRole: String,
    sessions: { type: Array, default: () => [] },
    orders: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ start_date: '', end_date: '' }) }
});

const page = usePage();
const user = page.props.auth.user;

// --- Filters Logic ---
const filterForm = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilters = () => {
    router.get(route('justification.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.value.start_date = '';
    filterForm.value.end_date = '';
    applyFilters();
};

const startDateInput = ref(null);
const endDateInput = ref(null);

const triggerPicker = (inputRef) => {
    if (inputRef && inputRef.showPicker) {
        inputRef.showPicker();
    }
};

// --- WhatsApp Share Logic ---
const shareByWhatsApp = (session) => {
    let message = `*RESUMEN DE PEDIDOS - ${session.provider_name}*\n`;
    message += `📅 *Fecha:* ${session.date}\n`;
    message += `🍴 *Servicio:* ${session.meal_type}\n`;
    message += `🏢 *Área:* ${user.area?.name || 'Mi Área'}\n\n`;
    
    const groupedDishes = {};
    session.orders?.forEach(o => {
        if (!groupedDishes[o.platillo]) groupedDishes[o.platillo] = [];
        groupedDishes[o.platillo].push(o.user_name);
    });

    Object.entries(groupedDishes).forEach(([dish, names]) => {
        message += `✅ *${names.length}x ${dish}*\n`;
        names.forEach(n => message += `  • ${n}\n`);
        message += `\n`;
    });

    message += `_Generado desde Sistema Comedor_`;
    const encoded = encodeURIComponent(message);
    window.open(`https://api.whatsapp.com/send?text=${encoded}`, '_blank');
};

// --- Unified Color Helpers ---
const mealTypeColors = {
    'Desayuno': 'bg-amber-500', 'Comida': 'bg-indigo-600', 'Cena': 'bg-purple-700', 'Extra': 'bg-teal-600',
};

const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200',
    'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200',
    'Cena': 'bg-purple-100 text-purple-700 border-purple-200',
    'Extra': 'bg-teal-100 text-teal-700 border-teal-200',
};

const providerColors = [
    { text: 'text-indigo-600', bg: 'bg-indigo-50' },
    { text: 'text-emerald-600', bg: 'bg-emerald-50' },
    { text: 'text-rose-600', bg: 'bg-rose-50' },
    { text: 'text-amber-600', bg: 'bg-amber-50' },
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
        onSuccess: () => { savingId.value = null; },
        onError: () => savingId.value = null
    });
};

// --- Manager Logic ---
const selectedSession = ref(null);
const searchQuery = ref('');
const showExportModal = ref(false);
const sessionToExport = ref(null);

const openExportModal = (session) => { sessionToExport.value = session; showExportModal.value = true; };

const handleExport = (format) => {
    const session = sessionToExport.value;
    if (!session) return;
    const url = route('admin.orders.summary.pdf', { 
        provider: session.provider_id, date: session.date, meal_type: session.meal_type,
        area_id: user.area_id, view_mode: 'names', sort: 'name', format: format
    });
    window.open(url, '_blank');
};

const openSessionJustification = (session) => {
    selectedSession.value = session;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEvidence = (url) => {
    if (url) window.open(url, '_blank');
};
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
                        Historial de Sesiones
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-[85%] mx-auto space-y-10">
                
                <!-- Barra de Filtros Premium -->
                <div v-if="!selectedSession" class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-8">
                    <div class="flex-1 min-w-[240px]">
                        <InputLabel value="Desde la fecha:" class="text-[10px] font-black uppercase text-gray-400 mb-3 ml-2 tracking-widest leading-none" />
                        <div class="relative group cursor-pointer" @click="triggerPicker(startDateInput)">
                            <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition-colors pointer-events-none" />
                            <input type="date" ref="startDateInput" v-model="filterForm.start_date" @change="applyFilters"
                                   class="w-full pl-12 pr-4 py-3.5 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-black text-gray-700 dark:text-white focus:border-indigo-500 focus:ring-0 transition-all uppercase tracking-widest cursor-pointer [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>
                    </div>
                    <div class="flex-1 min-w-[240px]">
                        <InputLabel value="Hasta la fecha:" class="text-[10px] font-black uppercase text-gray-400 mb-3 ml-2 tracking-widest leading-none" />
                        <div class="relative group cursor-pointer" @click="triggerPicker(endDateInput)">
                            <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition-colors pointer-events-none" />
                            <input type="date" ref="endDateInput" v-model="filterForm.end_date" @change="applyFilters"
                                   class="w-full pl-12 pr-4 py-3.5 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-black text-gray-700 dark:text-white focus:border-indigo-500 focus:ring-0 transition-all uppercase tracking-widest cursor-pointer [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button @click="applyFilters" class="px-10 py-4 bg-indigo-600 text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-900/20 active:scale-95">Filtrar Historial</button>
                        <button @click="clearFilters" class="px-8 py-4 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">Limpiar</button>
                    </div>
                </div>

                <!-- VISTA GERENTE DE ÁREA / ADMIN -->
                <div v-if="userRole !== 'diner'" class="space-y-8">
                    <!-- Lista de Sesiones -->
                    <div v-if="!selectedSession" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="hSession in sessions" :key="hSession.id" 
                             class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border-2 shadow-xl transition-all hover:scale-[1.02] flex flex-col relative"
                             :class="[
                                hSession.justified_count === hSession.total_orders && hSession.total_orders > 0
                                    ? 'border-green-400' : (hSession.justified_count > 0 ? 'border-amber-400' : 'border-rose-100')
                             ]">
                            
                            <div class="flex justify-between items-start mb-8">
                                <div class="flex items-center gap-5">
                                    <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center shadow-md shrink-0" :class="getProviderColor(hSession.provider_id).bg + ' ' + getProviderColor(hSession.provider_id).text"><ClipboardDocumentListIcon class="h-9 w-9" /></div>
                                    <div class="min-w-0">
                                        <h5 class="font-black text-lg text-gray-800 dark:text-white uppercase tracking-tighter leading-none mb-2 truncate">{{ hSession.provider_name }}</h5>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded border uppercase tracking-widest inline-block" :class="mealTypeTagColors[hSession.meal_type]">{{ hSession.meal_type }}</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ hSession.date }}</p>
                                </div>
                            </div>
                            
                            <!-- Included Areas (Admin) or Diners (Manager) Section -->
                            <div class="mb-8 p-5 bg-gray-50 dark:bg-gray-900/50 rounded-[2rem] border border-gray-100 dark:border-gray-700">
                                <p v-if="userRole === 'area_manager'" class="text-[8px] font-black uppercase text-gray-400 tracking-[0.2em] mb-4 flex items-center gap-2">
                                    <UserGroupIcon class="h-3 w-3" /> Personal Comensal:
                                </p>
                                <p v-else class="text-[8px] font-black uppercase text-gray-400 tracking-[0.2em] mb-4 flex items-center gap-2">
                                    <BuildingOfficeIcon class="h-3 w-3" /> Dependencias autorizadas:
                                </p>

                                <div class="flex flex-wrap gap-2 max-h-28 overflow-y-auto pr-1 custom-scrollbar">
                                    <!-- For Manager: Show Diner Names -->
                                    <template v-if="userRole === 'area_manager'">
                                        <span v-for="dinerName in hSession.team_diner_names" :key="dinerName" 
                                              class="text-[8px] font-bold px-3 py-1.5 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl text-gray-600 dark:text-gray-400 uppercase truncate max-w-[140px] shadow-sm">
                                            {{ dinerName }}
                                        </span>
                                    </template>
                                    <!-- For Admin/Adq: Show Area Names -->
                                    <template v-else>
                                        <span v-for="areaName in hSession.included_areas" :key="areaName" 
                                              class="text-[8px] font-bold px-3 py-1.5 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl text-gray-600 dark:text-gray-400 uppercase truncate max-w-[140px] shadow-sm">
                                            {{ areaName }}
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <div class="mb-8 flex-1">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Progreso:</span>
                                    <span class="text-[10px] font-black" :class="hSession.justified_count === hSession.total_orders ? 'text-green-500' : 'text-rose-500'">{{ hSession.justified_count }} / {{ hSession.total_orders }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden mb-4 border">
                                    <div class="h-full transition-all duration-500" :class="hSession.justified_count === hSession.total_orders ? 'bg-green-500' : (hSession.justified_count > 0 ? 'bg-amber-500' : 'bg-red-500')" :style="{ width: (hSession.total_orders > 0 ? (hSession.justified_count / hSession.total_orders * 100) : 0) + '%' }"></div>
                                </div>
                            </div>
                            
                            <button @click="openSessionJustification(hSession)" class="w-full py-4 bg-gray-900 dark:bg-gray-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg">Ver Detalle / Justificar</button>
                        </div>
                    </div>

                    <!-- Editor de Justificaciones (DRILL-DOWN) -->
                    <div v-else class="space-y-12">
                        <!-- HEADER DETALLE -->
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-[3.5rem] shadow-2xl border-2 border-indigo-50 dark:border-gray-700">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <div class="flex items-center">
                                    <button @click="selectedSession = null" class="mr-6 p-3 rounded-2xl bg-gray-50 dark:bg-gray-900 text-gray-400 hover:text-indigo-600 transition-all shadow-sm"><ChevronLeftIcon class="h-6 w-6" /></button>
                                    <div><h3 class="font-black text-2xl text-gray-800 dark:text-white uppercase tracking-tighter leading-none">{{ selectedSession.provider_name }}</h3><p class="text-[10px] font-bold text-indigo-500 uppercase tracking-[0.2em] mt-2">{{ selectedSession.meal_type }} • {{ selectedSession.date }}</p></div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="bg-green-50 dark:bg-green-900/20 px-6 py-3 rounded-[1.5rem] border-2 border-green-100 flex items-center space-x-3 shadow-sm"><CheckBadgeIcon class="h-5 w-5 text-green-500" /><span class="text-[10px] font-black text-green-700 uppercase tracking-widest">Respaldo Automático</span></div>
                                    <button @click="openExportModal(selectedSession)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest flex items-center shadow-lg"><PrinterIcon class="h-4 w-4 mr-2" /> Reporte</button>
                                </div>
                            </div>
                            <!-- Barra de Progreso Interna -->
                            <div class="pt-8 mt-8 border-t dark:border-gray-700">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">{{ userRole === 'area_manager' ? 'Progreso Mi Área' : 'Progreso Global' }}</span>
                                    <span class="text-xs font-black text-indigo-600">{{ selectedSession.justified_count }} / {{ selectedSession.total_orders }}</span>
                                </div>
                                <div class="w-full h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden border">
                                    <div class="h-full bg-indigo-600 transition-all duration-700 shadow-inner" :style="{ width: (selectedSession.total_orders > 0 ? (selectedSession.justified_count / selectedSession.total_orders * 100) : 0) + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <!-- AREAS LOOP -->
                        <div v-for="areaDetail in selectedSession.areas_detail" :key="areaDetail.area_id" class="space-y-6">
                            <div class="bg-gray-900 text-white px-10 py-6 rounded-[2.5rem] flex justify-between items-center shadow-xl">
                                <div class="flex items-center gap-6">
                                    <div class="h-12 w-12 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-xl border border-white/10 shadow-inner"><BuildingOfficeIcon class="h-6 w-6" /></div>
                                    <div><h4 class="text-xl font-black uppercase tracking-tighter">{{ areaDetail.area_name }}</h4><p class="text-[10px] font-bold opacity-60 uppercase tracking-widest">Estatus: {{ areaDetail.justified_count }} / {{ areaDetail.total_orders }} justificados</p></div>
                                </div>
                                <!-- Area Evidence -->
                                <div v-if="areaDetail.evidence_url" class="flex items-center gap-3">
                                    <div class="group relative h-14 w-20 rounded-xl overflow-hidden border-2 border-white/20 shadow-lg cursor-pointer transition-all hover:ring-2 hover:ring-white" 
                                         @click="openEvidence(areaDetail.evidence_url)"
                                         title="Ver imagen completa">
                                        <img :src="areaDetail.evidence_url" class="w-full h-full object-cover" />
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                            <EyeIcon class="h-5 w-5 text-white" />
                                        </div>
                                    </div>
                                    <a :href="areaDetail.evidence_url" target="_blank" download
                                       class="p-2.5 bg-white/10 hover:bg-emerald-500 rounded-xl border border-white/10 transition-all text-white shadow-lg"
                                       title="Descargar Evidencia">
                                        <ArrowDownTrayIcon class="h-5 w-5" />
                                    </a>
                                </div>
                                <div v-else class="text-[9px] font-black uppercase bg-white/10 px-4 py-2 rounded-xl border border-white/10 italic opacity-50">Sin Evidencia de Foto</div>
                            </div>

                            <!-- Individual Orders -->
                            <div class="grid grid-cols-1 gap-4 ml-6 border-l-4 border-gray-100 dark:border-gray-800 pl-6">
                                <div v-for="order in areaDetail.orders" :key="order.id" 
                                     class="p-6 bg-white dark:bg-gray-800 rounded-[2.5rem] border-2 transition-all hover:shadow-lg"
                                     :class="order.activity_performed ? 'border-green-50' : 'border-rose-50 shadow-xl shadow-rose-500/5'">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
                                        <div class="flex items-center space-x-6 min-w-[350px] max-w-[350px] shrink-0">
                                            <div class="h-16 w-16 rounded-[1.5rem] flex items-center justify-center font-black text-lg uppercase border-4 shadow-xl shrink-0 overflow-hidden" :class="order.activity_performed ? 'bg-green-50 text-green-500 border-green-100' : 'bg-orange-50 text-orange-500 border-orange-100'">
                                                <img v-if="order.avatar_url && !order.avatar_url.includes('ui-avatars.com')" :src="order.avatar_url" class="h-full w-full object-cover" />
                                                <span v-else>{{ order.user_name.substring(0,2) }}</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-black text-base text-gray-800 dark:text-white uppercase tracking-tight leading-none mb-2 truncate" :title="order.user_name">{{ order.user_name }}</p>
                                                <div class="flex items-center gap-2"><div class="h-1.5 w-1.5 rounded-full bg-indigo-500"></div><p class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest truncate">{{ order.platillo }}</p></div>
                                            </div>
                                        </div>
                                        <div class="flex-1 relative">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2"><PencilSquareIcon class="h-3 w-3" /> Actividad Realizada:</p>
                                            <textarea v-model="order.activity_performed" @blur="autoSaveJustification(order.id, order.activity_performed)" rows="2" class="block w-full rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition-all" placeholder="Describe la actividad..."></textarea>
                                            <div v-if="savingId === order.id" class="absolute right-3 bottom-3"><ArrowPathIcon class="h-4 w-4 text-indigo-500 animate-spin" /></div>
                                            <div v-else-if="order.activity_performed" class="absolute right-3 bottom-3"><CheckCircleIcon class="h-4 w-4 text-green-500" /></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA COMENSAL (FULL WIDTH) -->
                <div v-if="userRole === 'diner'" class="grid grid-cols-1 gap-6">
                    <div v-for="order in orders" :key="order.id" 
                         class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-10 border-2 transition-all shadow-xl"
                         :class="order.activity_performed ? 'border-green-100' : 'border-orange-100 shadow-orange-500/5'">
                        <div class="flex flex-col md:flex-row justify-between gap-10">
                            <div class="flex items-start space-x-8">
                                <div class="h-20 w-20 rounded-[2rem] flex items-center justify-center text-white shrink-0 shadow-2xl" :class="mealTypeColors[order.meal_type] || 'bg-indigo-600'"><CalendarDaysIcon class="h-10 w-10" /></div>
                                <div>
                                    <div class="flex items-center gap-3 mb-3"><span class="text-[10px] font-black px-3 py-1 rounded-xl border uppercase tracking-widest" :class="mealTypeTagColors[order.meal_type]">{{ order.meal_type }}</span><span class="text-[10px] font-bold text-gray-400">{{ order.date }}</span></div>
                                    <h4 class="font-black text-3xl text-gray-800 dark:text-white leading-tight uppercase tracking-tighter">{{ order.platillo }}</h4>
                                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-2">{{ order.provider_name }}</p>
                                </div>
                            </div>
                            <div class="flex-1 relative">
                                <InputLabel value="Mi Actividad Realizada (Auto-guardado):" class="text-[10px] font-black uppercase text-gray-400 mb-3" />
                                <textarea v-model="order.activity_performed" @blur="autoSaveJustification(order.id, order.activity_performed)" rows="3" class="block w-full rounded-[2rem] border-gray-100 dark:border-gray-700 dark:bg-gray-950 shadow-inner focus:ring-indigo-500 text-sm" placeholder="Escribe tu actividad..."></textarea>
                                <div v-if="savingId === order.id" class="absolute right-6 bottom-6"><ArrowPathIcon class="h-6 w-6 text-indigo-500 animate-spin" /></div>
                                <div v-else-if="order.activity_performed" class="absolute right-6 bottom-6 text-green-500 flex items-center gap-2"><CheckBadgeIcon class="h-6 w-6" /><span class="text-[10px] font-black uppercase">Guardado</span></div>
                            </div>
                        </div>
                    </div>
                    <div v-if="orders.length === 0" class="p-40 text-center bg-white rounded-[4rem] border-2 border-dashed border-gray-100"><ClipboardDocumentCheckIcon class="h-24 w-24 text-gray-100 mx-auto mb-8" /><p class="text-gray-400 font-black uppercase tracking-widest text-sm">No tienes pedidos registrados por justificar</p></div>
                </div>

            </div>
        </div>

        <ExportChoiceModal :show="showExportModal" @close="showExportModal = false" @select="handleExport" />
    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
</style>
