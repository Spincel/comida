<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import DeactivateMenuConfirmationModal from '@/Pages/Admin/Partials/DeactivateMenuConfirmationModal.vue';
import { 
    ChevronLeftIcon, PrinterIcon, UsersIcon, ListBulletIcon, UserGroupIcon, 
    ViewColumnsIcon, ChatBubbleLeftRightIcon, ClipboardDocumentListIcon, BuildingStorefrontIcon,
    InformationCircleIcon, CheckBadgeIcon, PowerIcon, CheckCircleIcon, ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ 
    provider: Object, 
    date: String, 
    mealType: String, 
    ordersSummary: Array,
    sessionId: Number,
    sessionStatus: {
        type: String,
        default: 'open'
    },
    session: Object
});

const isSessionOpen = computed(() => {
    if (props.sessionStatus) return props.sessionStatus === 'open';
    if (props.session?.status) return props.session.status === 'open';
    return false;
});

// Auto-refresh logic (Polling) - only if session is active
let refreshInterval = null;
onMounted(() => {
    if (isSessionOpen.value) {
        refreshInterval = setInterval(() => {
            router.reload({ preserveScroll: true });
        }, 15000); // 15 seconds
    }
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

const showDeactivateModal = ref(false);

const closeCurrentSession = () => {
    showDeactivateModal.value = true;
};

const confirmDeactivation = () => {
    showDeactivateModal.value = false;
    router.patch(route('dashboard.providers.deactivate', props.provider.id), {
        date: props.date,
        meal_type: props.mealType
    }, {
        onSuccess: () => {
            router.visit(route('dashboard'));
        }
    });
};

const sendToWhatsApp = (area = null) => {
    let msg = `*SOLICITUD DE PEDIDO ${props.mealType.toUpperCase()} - ${props.date}*\n`;
    msg += `----------------------------\n\n`;

    const processArea = (a) => {
        let areaMsg = `📍 *${a.area_name.toUpperCase()}*\n`;
        
        // List individual diners with their dish and observations
        a.individual_orders.forEach((o, i) => {
            const obs = o.preferences ? ` _(Obs: ${o.preferences})_` : '';
            areaMsg += `${i + 1}. ${o.user_name} - *${o.platillo_name}*${obs}\n`;
        });

        // Add grouped summary for this area
        areaMsg += `\n*TOTAL PLATILLOS ÁREA:*`;
        a.platillos.forEach(p => {
            areaMsg += `\n• ${p.total_count}x ${p.platillo_name}`;
        });
        
        return areaMsg + `\n\n`;
    };

    if (area) {
        msg += processArea(area);
    } else {
        props.ordersSummary.forEach(a => {
            msg += processArea(a);
        });
        
        // Final Global Grand Total
        const total = props.ordersSummary.reduce((t, a) => t + (a.total_area_orders || 0), 0);
        msg += `----------------------------\n`;
        msg += `*TOTAL GLOBAL PEDIDOS: ${total}*`;
    }

    const phone = props.provider.contact_phone || '';
    window.open(`https://api.whatsapp.com/send?${phone ? 'phone=52' + phone + '&' : ''}text=${encodeURIComponent(msg)}`, '_blank');
};

const showExportModal = ref(false), exportTargetArea = ref(null);
const openExportModal = (area = null) => { exportTargetArea.value = area; showExportModal.value = true; };
const handleExport = (format) => { window.open(route('admin.orders.summary.pdf', { provider: props.provider.id, date: props.date, meal_type: props.mealType, format, area_id: exportTargetArea.value?.area_id }), '_blank'); };

const viewMode = ref('detailed');
const totalGrandOrders = computed(() => props.ordersSummary.reduce((t, a) => t + (a.total_area_orders || 0), 0));

const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800',
    'Comida': 'bg-tinto-100 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border-tinto-300 dark:border-tinto-800',
    'Cena': 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-800',
    'Extra': 'bg-teal-100 dark:bg-teal-950/80 text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-800'
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
</script>

<template>
    <Head :title="'Monitor ' + mealType" />

    <AuthenticatedLayout bento-tag="Monitor">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-12 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2.5">
                        <span class="px-3.5 py-1.5 rounded-xl text-[10px] font-black uppercase border shadow-sm flex items-center gap-1.5" :class="mealTypeTagColors[mealType]">
                            <span>{{ mealType === 'Desayuno' ? '🍳' : (mealType === 'Comida' ? '🍲' : '🌙') }}</span>
                            {{ mealType }}
                        </span>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest flex items-center gap-1.5">
                            <span>👨‍🍳</span> {{ provider.name }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Botón de finalizar solo si la sesión sigue abierta -->
                    <button v-if="isSessionOpen" @click="closeCurrentSession" class="bg-gradient-to-r from-rose-700 to-rose-600 hover:from-rose-800 hover:to-rose-700 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-rose-900/30 flex items-center gap-2 animate-blink-danger transition-all hover:scale-105 active:scale-95 cursor-pointer">
                        <PowerIcon class="h-4 w-4" /> Finalizar {{ mealType }}
                    </button>
                    <!-- Badge indicador si la sesión ya está cerrada -->
                    <div v-else class="flex items-center gap-2 px-5 py-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-nayarit-800 dark:text-emerald-400 text-[10px] font-black uppercase tracking-widest shadow-sm">
                        <CheckCircleIcon class="h-4 w-4 text-emerald-500" />
                        <span>Turno Finalizado</span>
                    </div>

                    <!-- Enlace para volver al Dashboard -->
                    <Link :href="route('dashboard')" class="bg-slate-100 hover:bg-slate-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-200 px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-sm flex items-center gap-2 transition-all hover:scale-105 active:scale-95 cursor-pointer">
                        <ArrowLeftIcon class="h-4 w-4" /> Volver al Panel
                    </Link>

                    <div class="flex bg-slate-100 dark:bg-gray-800 p-1.5 rounded-2xl border dark:border-gray-700">
                        <button @click="viewMode = 'compact'" 
                                :class="viewMode === 'compact' ? 'bg-white dark:bg-gray-700 text-tinto-800 dark:text-oro-300 shadow-md font-black' : 'text-slate-400'" 
                                class="px-4 py-2 rounded-xl text-[10px] uppercase tracking-widest transition-all cursor-pointer">
                            Compacto
                        </button>
                        <button @click="viewMode = 'detailed'" 
                                :class="viewMode === 'detailed' ? 'bg-white dark:bg-gray-700 text-tinto-800 dark:text-oro-300 shadow-md font-black' : 'text-slate-400'" 
                                class="px-4 py-2 rounded-xl text-[10px] uppercase tracking-widest transition-all cursor-pointer">
                            Detallado
                        </button>
                    </div>
                    <button @click="openExportModal()" class="bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 flex items-center gap-2 transition-all hover:scale-105 active:scale-95 cursor-pointer shine-effect">
                        <PrinterIcon class="h-4 w-4 text-oro-300" /> Exportar
                    </button>
                    <button @click="sendToWhatsApp()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-600/20 flex items-center gap-2 transition-all hover:scale-105 active:scale-95 cursor-pointer">
                        <ChatBubbleLeftRightIcon class="h-4 w-4" /> WhatsApp
                    </button>
                </div>
            </div>

            <!-- TARJETAS DE MÉTRICAS -->
            <div class="lg:col-span-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center gap-6">
                    <div class="h-14 w-14 bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 rounded-2xl flex items-center justify-center shadow-inner">
                        <ClipboardDocumentListIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-3xl font-black text-tinto-900 dark:text-oro-200">{{ totalGrandOrders }}</p>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Raciones Totales</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center gap-6">
                    <div class="h-14 w-14 bg-oro-50 dark:bg-oro-950/60 text-oro-700 dark:text-oro-300 border border-oro-200 dark:border-oro-800 rounded-2xl flex items-center justify-center shadow-inner">
                        <UserGroupIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-3xl font-black text-oro-900 dark:text-oro-200">{{ ordersSummary.length }}</p>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Áreas Atendidas</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center gap-6">
                    <div class="h-14 w-14 bg-emerald-50 dark:bg-emerald-950/60 text-nayarit-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-2xl flex items-center justify-center shadow-inner">
                        <BuildingStorefrontIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-800 dark:text-white uppercase truncate">{{ provider.name }}</p>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Proveedor Asignado</p>
                    </div>
                </div>
            </div>

            <!-- LISTA DE ÁREAS Y PEDIDOS -->
            <div class="lg:col-span-12 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div v-for="area in ordersSummary" :key="area.area_id" class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden flex flex-col">
                    <div class="p-8 bg-slate-50/70 dark:bg-gray-800/50 flex justify-between items-center border-b border-slate-100 dark:border-gray-800">
                        <div class="flex items-center gap-4">
                            <div class="h-11 w-11 bg-gradient-to-tr from-tinto-900 to-tinto-800 rounded-2xl flex items-center justify-center text-oro-300 border border-oro-500/30 shadow-sm">
                                <span class="text-base">🏢</span>
                            </div>
                            <div>
                                <h4 class="font-black text-base text-slate-800 dark:text-white uppercase tracking-tight">{{ area.area_name }}</h4>
                                <p class="text-[9px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">{{ area.total_area_orders }} pedidos autorizados</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openExportModal(area)" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 transition-all shadow-sm cursor-pointer" title="Exportar área">
                                <PrinterIcon class="h-4 w-4" />
                            </button>
                            <button @click="sendToWhatsApp(area)" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 text-slate-400 hover:text-emerald-600 transition-all shadow-sm cursor-pointer" title="WhatsApp área">
                                <ChatBubbleLeftRightIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- RESUMEN DE PLATILLOS AGRUPADOS -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-for="p in area.platillos" :key="p.platillo_name" class="p-4 rounded-2xl bg-slate-50/60 dark:bg-gray-800/40 border border-slate-200/60 dark:border-gray-700/60 flex justify-between items-center group hover:scale-[1.02] transition-all">
                                <div class="min-w-0 mr-3 flex items-center gap-2.5">
                                    <span class="text-xl group-hover:scale-125 transition-transform">{{ getDishEmoji(p.platillo_name, mealType) }}</span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black text-slate-800 dark:text-gray-200 uppercase truncate">{{ p.platillo_name }}</p>
                                        <p v-if="p.observations?.length" class="text-[8px] text-rose-500 font-bold uppercase mt-0.5">{{ p.observations.length }} Notas</p>
                                    </div>
                                </div>
                                <span class="h-8 min-w-[2rem] px-2 rounded-xl bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 border border-oro-500/30 flex items-center justify-center font-black text-xs shadow-sm">
                                    {{ p.total_count }}
                                </span>
                            </div>
                        </div>

                        <!-- DESGLOSE INDIVIDUAL DE COMENSALES (MODO DETALLADO) -->
                        <div v-if="viewMode === 'detailed'" class="mt-6 pt-6 border-t border-slate-100 dark:border-gray-800">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-4">Comensales registrados en este turno:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="o in area.individual_orders" :key="o.user_name" class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/60 dark:border-gray-700 shadow-sm hover:scale-[1.01] transition-all">
                                    <img :src="o.avatar_url" class="h-8 w-8 rounded-full border-2 border-slate-100 dark:border-gray-700 object-cover shadow-sm" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[9px] font-black uppercase text-slate-800 dark:text-white truncate">{{ o.user_name }}</p>
                                        <p class="text-[8px] font-bold text-tinto-700 dark:text-oro-400 uppercase truncate flex items-center gap-1">
                                            <span>{{ getDishEmoji(o.platillo_name, mealType) }}</span>
                                            <span>{{ o.platillo_name }}</span>
                                        </p>
                                    </div>
                                    <CheckBadgeIcon v-if="o.activity_performed" class="h-4 w-4 text-nayarit-700 dark:text-emerald-400 shrink-0" title="Actividad justificada" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ExportChoiceModal :show="showExportModal" @close="showExportModal = false" @select="handleExport" />
        <DeactivateMenuConfirmationModal 
            :show="showDeactivateModal" 
            :provider="provider" 
            :todayOrdersByArea="ordersSummary.map(a => ({ area_name: a.area_name, total_items: a.total_area_orders, total_orders: a.total_area_orders }))"
            @close="showDeactivateModal = false" 
            @confirm="confirmDeactivation" 
        />
    </AuthenticatedLayout>
</template>
