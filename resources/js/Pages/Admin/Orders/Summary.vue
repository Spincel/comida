<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import { 
    ChevronLeftIcon, PrinterIcon, UsersIcon, ListBulletIcon, UserGroupIcon, 
    ViewColumnsIcon, ChatBubbleLeftRightIcon, ClipboardDocumentListIcon, BuildingStorefrontIcon,
    InformationCircleIcon, CheckBadgeIcon, PowerIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ 
    provider: Object, 
    date: String, 
    mealType: String, 
    ordersSummary: Array,
    sessionId: Number // Necesitamos pasar el ID de la sesión desde el controlador
});

// Auto-refresh logic (Polling)
let refreshInterval = null;
onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ preserveScroll: true });
    }, 15000); // 15 seconds
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

const closeCurrentSession = () => {
    if (confirm('¿Estás seguro de finalizar esta sesión de comida? Se cerrará el pedido para todas las áreas.')) {
        router.patch(route('dashboard.sessions.deactivate', props.provider.id), {
            date: props.date,
            meal_type: props.mealType
        }, {
            onSuccess: () => {
                router.visit(route('dashboard'));
            }
        });
    }
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
const mealTypeTagColors = { 'Desayuno': 'bg-amber-100 text-amber-700', 'Comida': 'bg-indigo-100 text-indigo-700', 'Cena': 'bg-purple-100 text-purple-700', 'Extra': 'bg-teal-100 text-teal-700' };
</script>

<template>
    <Head :title="'Monitor ' + mealType" />

    <AuthenticatedLayout bento-tag="Monitor">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-12 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2"><span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase border" :class="mealTypeTagColors[mealType]">{{ mealType }}</span><p class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ provider.name }}</p></div>
                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button @click="closeCurrentSession" class="bg-rose-600 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-rose-500/20 flex items-center gap-2 animate-blink-danger">
                                        <PowerIcon class="h-4 w-4" /> Finalizar Comida
                                    </button>
                                    <div class="flex bg-slate-100 dark:bg-gray-800 p-1.5 rounded-2xl border dark:border-gray-700">
                <button @click="viewMode = 'compact'" :class="viewMode === 'compact' ? 'bg-white dark:bg-gray-700 text-indigo-600 shadow-sm' : 'text-slate-400'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Compacto</button><button @click="viewMode = 'detailed'" :class="viewMode === 'detailed' ? 'bg-white dark:bg-gray-700 text-indigo-600 shadow-sm' : 'text-slate-400'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Detallado</button></div>
                    <button @click="openExportModal()" class="bg-slate-900 dark:bg-indigo-600 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg flex items-center gap-2"><PrinterIcon class="h-4 w-4" /> Exportar</button>
                    <button @click="sendToWhatsApp()" class="bg-emerald-600 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg flex items-center gap-2"><ChatBubbleLeftRightIcon class="h-4 w-4" /> WhatsApp</button>
                </div>
            </div>

            <div class="lg:col-span-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 flex items-center gap-6"><div class="h-14 w-14 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center shadow-inner"><ClipboardDocumentListIcon class="h-8 w-8" /></div><div><p class="text-3xl font-black text-slate-800 dark:text-white">{{ totalGrandOrders }}</p><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Raciones Totales</p></div></div>
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 flex items-center gap-6"><div class="h-14 w-14 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-2xl flex items-center justify-center shadow-inner"><UserGroupIcon class="h-8 w-8" /></div><div><p class="text-3xl font-black text-slate-800 dark:text-white">{{ ordersSummary.length }}</p><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Áreas Atendidas</p></div></div>
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 flex items-center gap-6"><div class="h-14 w-14 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-2xl flex items-center justify-center shadow-inner"><BuildingStorefrontIcon class="h-8 w-8" /></div><div><p class="text-sm font-black text-slate-800 dark:text-white uppercase truncate">{{ provider.name }}</p><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Proveedor</p></div></div>
            </div>

            <div class="lg:col-span-12 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div v-for="area in ordersSummary" :key="area.area_id" class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-sm border border-slate-100 dark:border-gray-800 overflow-hidden flex flex-col">
                    <div class="p-8 bg-slate-50/50 dark:bg-gray-800/50 flex justify-between items-center border-b dark:border-gray-800"><div class="flex items-center gap-4"><div class="h-10 w-10 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center border shadow-sm"><UserGroupIcon class="h-6 w-6 text-indigo-500" /></div><div><h4 class="font-black text-base text-slate-800 dark:text-white uppercase tracking-tight">{{ area.area_name }}</h4><p class="text-[9px] font-bold text-slate-400 uppercase">{{ area.total_area_orders }} pedidos</p></div></div><div class="flex gap-2"><button @click="openExportModal(area)" class="p-2.5 text-indigo-400 hover:text-indigo-600 transition-all"><PrinterIcon class="h-5 w-5" /></button><button @click="sendToWhatsApp(area)" class="p-2.5 text-emerald-400 hover:text-emerald-600 transition-all"><ChatBubbleLeftRightIcon class="h-5 w-5" /></button></div></div>
                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div v-for="p in area.platillos" :key="p.platillo_name" class="p-4 rounded-2xl bg-slate-50/50 dark:bg-gray-800/30 border dark:border-gray-800 flex justify-between items-center"><div class="min-w-0 mr-3"><p class="text-[10px] font-black text-slate-700 dark:text-gray-300 uppercase truncate">{{ p.platillo_name }}</p><p v-if="p.observations?.length" class="text-[8px] text-rose-500 font-bold uppercase">{{ p.observations.length }} Notas</p></div><span class="h-8 w-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black text-xs">{{ p.total_count }}</span></div></div>
                        <div v-if="viewMode === 'detailed'" class="mt-6 pt-6 border-t dark:border-gray-800"><div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-60 overflow-y-auto pr-2 custom-scrollbar"><div v-for="o in area.individual_orders" :key="o.user_name" class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-slate-50 dark:border-gray-700"><img :src="o.avatar_url" class="h-8 w-8 rounded-full border object-cover shadow-sm" /><div class="min-w-0 flex-1"><p class="text-[9px] font-black uppercase text-slate-800 dark:text-white truncate">{{ o.user_name }}</p><p class="text-[8px] font-bold text-indigo-500 uppercase truncate">{{ o.platillo_name }}</p></div><CheckBadgeIcon v-if="o.activity_performed" class="h-4 w-4 text-emerald-500" /></div></div></div>
                    </div>
                </div>
            </div>
        </div>
        <ExportChoiceModal :show="showExportModal" @close="showExportModal = false" @select="handleExport" />
    </AuthenticatedLayout>
</template>
