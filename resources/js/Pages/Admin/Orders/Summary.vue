<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import { 
    ChevronLeftIcon, 
    PrinterIcon, 
    UsersIcon, 
    ListBulletIcon,
    BarsArrowDownIcon,
    DocumentArrowDownIcon,
    ClipboardDocumentListIcon,
    UserGroupIcon,
    ViewColumnsIcon,
    UserIcon,
    ChatBubbleBottomCenterTextIcon,
    ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    provider: Object,
    date: String,
    mealType: String,
    ordersSummary: Array, 
    reportConfig: { type: Object, default: () => ({}) },
    whatsappConfig: { type: Object, default: () => ({}) }
});

// --- WhatsApp Sharing Logic ---
const sendToWhatsApp = (areaSummary = null) => {
    try {
        const mealType = props.mealType || 'Pedido';
        const fullDate = formattedDate.value; // tuesday, 17 of march...
        
        let message = `*SOLICITUD DE PEDIDO ${fullDate.toUpperCase()} PARA ${mealType.toUpperCase()}*\n`;
        message += `----------------------------\n\n`;

        const getShortName = (name) => {
            if (!name) return '';
            const parts = name.split(' ');
            if (parts.length <= 2) return name;
            return `${parts[0]} ${parts[parts.length - 1]}`; // First name and last last name
        };

        if (areaSummary) {
            // Single Area Report
            message += `📍 *AREA: ${areaSummary.area_name.toUpperCase()}*\n`;
            if (areaSummary.individual_orders && areaSummary.individual_orders.length > 0) {
                areaSummary.individual_orders.forEach((o, index) => {
                    const pName = o.platillo_name || 'Platillo';
                    const prefs = o.preferences ? ` (${o.preferences})` : ' (sin obs.)';
                    const diner = getShortName(o.user_name);
                    message += `${index + 1}. ${diner} - ${pName}${prefs}\n`;
                });
                message += `\n*Total platillos área:* ${areaSummary.total_area_orders}\n`;
            } else {
                message += `(Sin registros)\n`;
            }
        } else {
            // Global Report
            if (props.ordersSummary && props.ordersSummary.length > 0) {
                props.ordersSummary.forEach(area => {
                    message += `📍 *AREA: ${area.area_name.toUpperCase()}*\n`;
                    if (area.individual_orders && area.individual_orders.length > 0) {
                        area.individual_orders.forEach((o, index) => {
                            const pName = o.platillo_name || 'Platillo';
                            const prefs = o.preferences ? ` (${o.preferences})` : ' (sin obs.)';
                            const diner = getShortName(o.user_name);
                            message += `${index + 1}. ${diner} - ${pName}${prefs}\n`;
                        });
                        message += `\n*Total platillos área:* ${area.total_area_orders}\n\n`;
                    } else {
                        message += `(Sin registros)\n\n`;
                    }
                });

                // Global Summary Section
                message += `----------------------------\n`;
                message += `*RESUMEN DE PLATILLOS:*\n`;
                
                const globalPlatillos = {};
                props.ordersSummary.forEach(area => {
                    if (area.platillos) {
                        area.platillos.forEach(p => {
                            const pName = p.platillo_name;
                            globalPlatillos[pName] = (globalPlatillos[pName] || 0) + p.total_count;
                        });
                    }
                });

                Object.entries(globalPlatillos).forEach(([name, count]) => {
                    message += `- ${count} x ${name}\n`;
                });
                
                message += `\n*TOTAL COMPLETO:* ${totalGrandOrders.value}\n`;
            } else {
                message += `(No hay pedidos registrados para hoy)\n`;
            }
        }

        const encoded = encodeURIComponent(message);
        const phone = props.provider?.contact_phone || '';
        const url = phone 
            ? `https://api.whatsapp.com/send?phone=52${phone}&text=${encoded}`
            : `https://api.whatsapp.com/send?text=${encoded}`;
            
        window.open(url, '_blank');
    } catch (e) {
        console.error("Error sending WhatsApp:", e);
        alert("Ocurrió un error al preparar el mensaje de WhatsApp.");
    }
};

// --- Export Logic ---
const showExportModal = ref(false);
const exportTargetArea = ref(null); // null for global, areaSummary object for single area

const openExportModal = (area = null) => {
    exportTargetArea.value = area;
    showExportModal.value = true;
};

const handleExport = (format) => {
    const params = {
        meal_type: props.mealType,
        format: format,
        view_mode: viewMode.value,
        sort: sortMode.value
    };

    if (exportTargetArea.value) {
        params.area_id = exportTargetArea.value.area_id;
    }

    const url = route('admin.orders.summary.pdf', { 
        provider: props.provider.id, 
        date: props.date,
        ...params
    });
    window.open(url, '_blank');
};

// --- View Logic ---
const viewMode = ref('detailed'); // 'compact' or 'detailed'
const sortMode = ref('area'); // 'area', 'platillo' or 'name'

const totalGrandOrders = computed(() => {
    if (!props.ordersSummary) return 0;
    return props.ordersSummary.reduce((total, area) => total + (area.total_area_orders || 0), 0);
});

const mealTypeTagColors = {
    'Desayuno': 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
    'Comida': 'bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800',
    'Cena': 'bg-purple-50 text-purple-700 border-purple-100 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800',
    'Extra': 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/30 dark:text-teal-400 dark:border-teal-800',
};

const formattedDate = computed(() => {
    return new Date(props.date + 'T12:00:00').toLocaleDateString('es-ES', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric' 
    });
});
</script>

<template>
    <Head :title="'Resumen ' + mealType" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6">
                <div class="flex items-center">
                    <Link :href="route('dashboard')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                            Reporte de {{ mealType }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-widest"
                                  :class="mealTypeTagColors[mealType] || 'bg-indigo-50 text-indigo-500 border-indigo-100'">
                                {{ mealType }}
                            </span>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ provider.name }} • {{ formattedDate }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 w-full xl:w-auto">
                    <div class="flex bg-gray-100 dark:bg-gray-900 p-1 rounded-2xl border dark:border-gray-800">
                        <button @click="viewMode = 'compact'" 
                                class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2"
                                :class="viewMode === 'compact' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-sm' : 'text-gray-500'">
                            <ListBulletIcon class="h-4 w-4" /> Compacto
                        </button>
                        <button @click="viewMode = 'detailed'" 
                                class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2"
                                :class="viewMode === 'detailed' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-sm' : 'text-gray-500'">
                            <ViewColumnsIcon class="h-4 w-4" /> Detallado
                        </button>
                    </div>

                    <PrimaryButton @click="openExportModal()" class="!rounded-2xl !py-3 !px-8 flex items-center gap-2 shadow-lg shadow-indigo-100 dark:shadow-none">
                        <PrinterIcon class="h-4 w-4" /> Exportar Global
                    </PrimaryButton>

                    <button @click="sendToWhatsApp()" 
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-emerald-100 dark:shadow-none transition-all">
                        <ChatBubbleLeftRightIcon class="h-4 w-4" /> WhatsApp Global
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-[85%] mx-auto space-y-10">
                
                <!-- Stats Bar -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] border shadow-xl flex items-center gap-6">
                        <div class="h-16 w-16 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-3xl flex items-center justify-center">
                            <ClipboardDocumentListIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-4xl font-black text-gray-800 dark:text-white tracking-tighter">{{ totalGrandOrders }}</p>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Pedidos</p>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] border shadow-xl flex items-center gap-6">
                        <div class="h-16 w-16 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-3xl flex items-center justify-center">
                            <UserGroupIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-4xl font-black text-gray-800 dark:text-white tracking-tighter">{{ ordersSummary.length }}</p>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Dependencias</p>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] border shadow-xl flex items-center gap-6">
                        <div class="h-16 w-16 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-3xl flex items-center justify-center">
                            <BuildingStorefrontIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-lg font-black text-gray-800 dark:text-white uppercase truncate tracking-tight">{{ provider.name }}</p>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Proveedor Asignado</p>
                        </div>
                    </div>
                </div>

                <!-- Detalle por Área -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div v-for="area in ordersSummary" :key="area.area_id" 
                         class="bg-white dark:bg-gray-800 rounded-[3rem] border shadow-xl overflow-hidden flex flex-col">
                        
                        <div class="p-8 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/20">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-white dark:bg-gray-800 rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm border border-gray-100">
                                    <UserGroupIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="font-black text-lg text-gray-800 dark:text-white uppercase tracking-tight">{{ area.area_name }}</h3>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">{{ area.total_area_orders }} pedidos registrados</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="openExportModal(area)" 
                                        class="p-3 text-indigo-600 hover:bg-white rounded-2xl transition-all shadow-sm border border-gray-100" title="Exportar Área">
                                    <PrinterIcon class="h-5 w-5" />
                                </button>
                                <button @click="sendToWhatsApp(area)" 
                                        class="p-3 text-emerald-600 hover:bg-white rounded-2xl transition-all shadow-sm border border-gray-100" title="Enviar WhatsApp al Área">
                                    <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <div class="p-8 flex-1 space-y-6">
                            <!-- Resumen de Platillos en el Área -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="p in area.platillos" :key="p.platillo_name" 
                                     class="p-4 rounded-2xl bg-indigo-50/30 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800 flex justify-between items-center">
                                    <div class="flex-1 min-w-0 mr-3">
                                        <p class="text-[11px] font-black text-indigo-700 dark:text-indigo-400 uppercase truncate">{{ p.platillo_name }}</p>
                                        <div v-if="p.observations?.length > 0" class="mt-1 flex items-center gap-1">
                                            <InformationCircleIcon class="h-3 w-3 text-indigo-400" />
                                            <span class="text-[8px] text-indigo-400 font-bold uppercase">{{ p.observations.length }} obs.</span>
                                        </div>
                                    </div>
                                    <span class="h-8 w-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-lg">{{ p.total_count }}</span>
                                </div>
                            </div>

                            <!-- Listado de Nombres (Solo en Detallado) -->
                            <div v-if="viewMode === 'detailed'" class="mt-6 pt-6 border-t dark:border-gray-700">
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mb-4 ml-2">Personal y Pedido:</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="order in area.individual_orders" :key="order.user_name" 
                                         class="flex items-center p-3 rounded-xl border border-gray-50 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
                                        <div class="relative">
                                            <img :src="order.avatar_url" class="h-8 w-8 rounded-full border border-gray-100 shadow-sm object-cover mr-3" />
                                            <CheckBadgeIcon v-if="order.activity_performed" class="absolute -bottom-1 -right-1 h-3 w-3 text-green-500 bg-white rounded-full" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-black text-gray-800 dark:text-white uppercase truncate">{{ order.user_name }}</p>
                                            <p class="text-[9px] font-bold text-indigo-500 truncate uppercase">{{ order.platillo_name }}</p>
                                            <p v-if="order.preferences" class="text-[8px] font-medium text-rose-500 italic truncate uppercase mt-0.5">
                                                * {{ order.preferences }}
                                            </p>
                                        </div>
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
    background: rgba(99, 102, 241, 0.1);
    border-radius: 10px;
}
</style>
