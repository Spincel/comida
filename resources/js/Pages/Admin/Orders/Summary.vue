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
    ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    provider: Object,
    date: String,
    mealType: String,
    ordersSummary: Array, 
});

// --- WhatsApp Sharing Logic ---
const sendToWhatsApp = (areaSummary = null) => {
    try {
        const providerName = props.provider?.name || 'Proveedor';
        const mealType = props.mealType || 'Pedido';
        
        let message = `*RESUMEN DE PEDIDOS - ${mealType.toUpperCase()}*\n`;
        message += `*Proveedor:* ${providerName}\n`;
        message += `*Fecha:* ${props.date}\n`;
        message += `----------------------------\n\n`;

        if (areaSummary) {
            // Single Area Report
            const areaName = areaSummary.area_name || 'Área';
            message += `*ÁREA:* ${areaName.toUpperCase()}\n`;
            
            if (areaSummary.platillos && areaSummary.platillos.length > 0) {
                areaSummary.platillos.forEach(p => {
                    const pName = p.platillo_name || 'Platillo';
                    message += `• ${p.total_count}x ${pName}\n`;
                    if (p.observations?.length > 0) {
                        p.observations.forEach(obs => {
                            if (obs) message += `  - _${obs}_\n`;
                        });
                    }
                });
            } else {
                message += `(Sin platillos registrados)\n`;
            }
            message += `\n*Total Área:* ${areaSummary.total_area_orders || 0} pedidos`;
        } else {
            // Global Report
                    const globalPlatillos = {};
                    if (props.ordersSummary && props.ordersSummary.length > 0) {
                        props.ordersSummary.forEach(area => {
                            if (area.platillos && Array.isArray(area.platillos)) {
                                area.platillos.forEach(p => {
                                    const pName = p.platillo_name || 'Platillo';
                                    if (!globalPlatillos[pName]) globalPlatillos[pName] = 0;
                                    globalPlatillos[pName] += p.total_count;
                                });
                            }
                        });
                    }
            
                    message += `*RESUMEN GLOBAL POR PLATILLO:*\n`;
                    const entries = Object.entries(globalPlatillos);
                    if (entries.length > 0) {
                        entries.forEach(([name, count]) => {
                            message += `• ${count}x ${name}\n`;
                        });
                    } else {
                        message += `(No hay pedidos registrados)\n`;
                    }
                    
                    message += `\n----------------------------\n`;
                    message += `*DETALLE POR ÁREA:*\n`;
                    
                    if (props.ordersSummary && props.ordersSummary.length > 0) {
                        props.ordersSummary.forEach(area => {
                            const aName = area.area_name || 'Área';
                            message += `\n*${aName}:* ${area.total_area_orders || 0}\n`;
                            if (area.platillos && Array.isArray(area.platillos)) {
                                area.platillos.forEach(p => {
                                    const pName = p.platillo_name || 'Platillo';
                                    message += `  - ${p.total_count}x ${pName}\n`;
                                });
                            }
                        });
                    }
            
            message += `\n*TOTAL GENERAL:* ${totalGrandOrders.value} pedidos`;
        }

        const encodedMessage = encodeURIComponent(message);
        
        // Clean phone number (remove non-digits)
        let phone = props.provider.contact_phone ? props.provider.contact_phone.replace(/\D/g, '') : '';
        
        // If it's a 10 digit number, assume Mexico (+52)
        if (phone.length === 10) {
            phone = '52' + phone;
        }

        if (!phone) {
            alert('El proveedor no tiene un número de teléfono válido registrado.');
            return;
        }

        const whatsappUrl = `https://api.whatsapp.com/send?phone=${phone}&text=${encodedMessage}`;
        window.open(whatsappUrl, '_blank');
    } catch (error) {
        console.error('Error al generar mensaje de WhatsApp:', error);
        alert('Ocurrió un error al generar el mensaje. Por favor revisa la consola.');
    }
};

const formattedDate = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(props.date).toLocaleDateString('es-ES', options);
});

// --- View & Sort State ---
const viewMode = ref('detailed'); // 'detailed' (names) or 'dishes' (only counts)
const sortMode = ref('area'); // 'area', 'platillo', 'name'
const showExportModal = ref(false);
const areaToExport = ref(null);

const openGlobalExport = () => {
    areaToExport.value = null;
    showExportModal.value = true;
};

const openAreaExport = (areaId) => {
    areaToExport.value = areaId;
    showExportModal.value = true;
};

const handleExport = (format) => {
    const params = { 
        provider: props.provider.id, 
        date: props.date,
        meal_type: props.mealType,
        sort: sortMode.value,
        view_mode: viewMode.value,
        format: format
    };

    if (areaToExport.value) {
        params.area_id = areaToExport.value;
    }

    const url = route('admin.orders.summary.pdf', params);
    window.open(url, '_blank');
};

const sortedSummary = computed(() => {
    let summary = [...props.ordersSummary];
    if (sortMode.value === 'area') {
        summary.sort((a, b) => a.area_name.localeCompare(b.area_name));
    }
    return summary;
});

const totalGrandOrders = computed(() => {
    return props.ordersSummary.reduce((sum, area) => sum + area.total_area_orders, 0);
});

// --- Unified Color Helpers ---
const mealTypeTagColors = {
    'Desayuno': 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
    'Comida': 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800',
    'Cena': 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800',
    'Extra': 'bg-teal-100 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-400 dark:border-teal-800',
};
</script>

<template>
    <Head :title="`Reporte: ${mealType} - ${provider.name}`" />

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
                    <!-- Acomodo de Impresión en el Header -->
                    <div class="flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-2xl border dark:border-gray-700">
                        <span class="px-3 text-[9px] font-black text-gray-400 uppercase tracking-widest hidden md:inline">Orden:</span>
                        <button 
                            v-for="mode in ['area', 'platillo', 'name']" :key="mode"
                            @click="sortMode = mode"
                            class="px-3 py-2 rounded-xl text-[9px] font-black uppercase transition-all whitespace-nowrap"
                            :class="sortMode === mode ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-400 hover:text-gray-600'"
                        >
                            {{ mode === 'area' ? 'Por Área' : (mode === 'platillo' ? 'Platillo' : 'Alfabético') }}
                        </button>
                    </div>

                    <div class="flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-2xl border dark:border-gray-700">
                        <button 
                            @click="viewMode = 'detailed'"
                            class="px-3 py-2 rounded-xl text-[9px] font-black uppercase transition-all flex items-center"
                            :class="viewMode === 'detailed' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-400 hover:text-gray-600'"
                        >
                            <UsersIcon class="h-3 w-3 mr-1" /> Detallado
                        </button>
                        <button 
                            @click="viewMode = 'dishes'"
                            class="px-3 py-2 rounded-xl text-[9px] font-black uppercase transition-all flex items-center"
                            :class="viewMode === 'dishes' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-400 hover:text-gray-600'"
                        >
                            <ListBulletIcon class="h-3 w-3 mr-1" /> Platillos
                        </button>
                        <button 
                            @click="viewMode = 'names'"
                            class="px-3 py-2 rounded-xl text-[9px] font-black uppercase transition-all flex items-center"
                            :class="viewMode === 'names' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-400 hover:text-gray-600'"
                        >
                            <UserGroupIcon class="h-3 w-3 mr-1" /> Nombres
                        </button>
                    </div>

                    <PrimaryButton @click="openGlobalExport" class="!rounded-2xl !py-3 !px-6 !text-[10px] bg-indigo-600 hover:bg-indigo-700 shadow-xl shadow-indigo-100 dark:shadow-none uppercase font-black tracking-[0.1em] flex-1 xl:flex-none justify-center">
                        <PrinterIcon class="h-4 w-4 mr-2" /> Exportar Todo
                    </PrimaryButton>

                    <button @click="sendToWhatsApp()" 
                            class="p-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl shadow-lg shadow-emerald-100 dark:shadow-none transition-all flex items-center justify-center gap-2 text-[10px] font-black uppercase px-6">
                        <ChatBubbleBottomCenterTextIcon class="h-4 w-4" /> WhatsApp Global
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- RESUMEN GENERAL -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-xl shadow-gray-100 dark:shadow-none border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total de Pedidos</p>
                            <p class="text-5xl font-black text-gray-900 dark:text-white leading-none mt-1">{{ totalGrandOrders }}</p>
                        </div>
                        <div class="h-14 w-14 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <ClipboardDocumentListIcon class="h-8 w-8" />
                        </div>
                    </div>

                    <!-- Configuración de Vista (Cambiado aquí) -->
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-xl shadow-gray-100 dark:shadow-none border border-gray-100 dark:border-gray-700 md:col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Configuración de Pantalla</p>
                            <ViewColumnsIcon class="h-5 w-5 text-gray-300" />
                        </div>
                        <div class="flex gap-4">
                            <button 
                                @click="viewMode = 'detailed'"
                                class="flex-1 flex flex-col items-center gap-3 p-4 rounded-3xl border-2 transition-all group"
                                :class="viewMode === 'detailed' ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400' : 'border-gray-100 text-gray-400 dark:border-gray-700 hover:border-indigo-200'"
                            >
                                <UsersIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-widest text-center">Vista Detallada (Nombres)</span>
                            </button>
                            
                            <button 
                                @click="viewMode = 'dishes'"
                                class="flex-1 flex flex-col items-center gap-3 p-4 rounded-3xl border-2 transition-all group"
                                :class="viewMode === 'dishes' ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400' : 'border-gray-100 text-gray-400 dark:border-gray-700 hover:border-indigo-200'"
                            >
                                <ListBulletIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-widest text-center">Solo Platillos (Totales)</span>
                            </button>

                            <button 
                                @click="viewMode = 'names'"
                                class="flex-1 flex flex-col items-center gap-3 p-4 rounded-3xl border-2 transition-all group"
                                :class="viewMode === 'names' ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400' : 'border-gray-100 text-gray-400 dark:border-gray-700 hover:border-indigo-200'"
                            >
                                <UserIcon class="h-6 w-6" />
                                <span class="text-[10px] font-black uppercase tracking-widest text-center">Solo Nombres (Firma)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CARDS POR ÁREA -->
                <div v-if="sortedSummary.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div v-for="areaSummary in sortedSummary" :key="areaSummary.area_id" 
                         class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col group transition-all hover:scale-[1.01]">
                        
                        <!-- Header de la Tarjeta -->
                        <div class="p-8 pb-4">
                            <div class="flex justify-between items-start mb-6">
                                <div class="flex items-center space-x-4">
                                    <div class="h-14 w-14 bg-indigo-600 rounded-3xl flex items-center justify-center text-white shadow-lg shadow-indigo-200 dark:shadow-none">
                                        <UserGroupIcon class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight leading-none">{{ areaSummary.area_name }}</h3>
                                        <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">Reporte de Área</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ areaSummary.total_area_orders }}</p>
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mt-1">Platillos</p>
                                </div>
                            </div>

                            <!-- Resumen de Platillos en el área -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8">
                                <div v-for="platillo in areaSummary.platillos" :key="platillo.platillo_name" 
                                     class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate mr-2">{{ platillo.platillo_name }}</span>
                                    <span class="font-black text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800 h-6 w-6 flex items-center justify-center rounded-lg shadow-sm border dark:border-gray-700">{{ platillo.total_count }}</span>
                                </div>
                            </div>

                            <!-- Detalle por Persona (Condicional) -->
                            <transition
                                enter-active-class="transition ease-out duration-300"
                                enter-from-class="opacity-0 translate-y-4"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-200"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-4"
                            >
                                <div v-if="viewMode === 'detailed'" class="space-y-3">
                                    <div class="flex items-center justify-between mb-4 border-b dark:border-gray-700 pb-2">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Comensales:</p>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div v-for="(order, idx) in areaSummary.individual_orders" :key="idx" 
                                             class="flex items-center p-3 bg-white dark:bg-gray-800 border-2 border-gray-50 dark:border-gray-700 rounded-2xl shadow-sm">
                                            <img :src="order.avatar_url" class="h-10 w-10 rounded-full border-2 border-white shadow-sm mr-3" alt="" />
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-black text-gray-800 dark:text-gray-200 truncate">{{ order.user_name }}</p>
                                                <p class="text-[9px] font-bold text-indigo-500 uppercase truncate">{{ order.platillo_name }}</p>
                                                <p v-if="order.preferences" class="text-[8px] text-gray-400 italic truncate mt-0.5">"{{ order.preferences }}"</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else-if="viewMode === 'names'" class="space-y-3 mt-6">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b dark:border-gray-700">
                                                    <th class="pb-2">Comensal</th>
                                                    <th class="pb-2">Actividad / Justificación</th>
                                                    <th class="pb-2 text-center">Firma</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y dark:divide-gray-700">
                                                <tr v-for="(order, idx) in areaSummary.individual_orders" :key="idx" class="text-sm">
                                                    <td class="py-3 font-bold text-gray-800 dark:text-gray-200">{{ order.user_name }}</td>
                                                    <td class="py-3 text-gray-500 italic text-xs">{{ order.activity_performed || '(Sin llenar)' }}</td>
                                                    <td class="py-3">
                                                        <div class="w-20 border-b border-gray-300 dark:border-gray-600 mx-auto h-4"></div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <!-- Footer de la Tarjeta con botón de impresión por área -->
                        <div class="mt-auto p-6 bg-gray-50 dark:bg-gray-900/30 border-t dark:border-gray-700 grid grid-cols-2 gap-3">
                            <button 
                                @click="openAreaExport(areaSummary.area_id)"
                                class="w-full py-3 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl text-[9px] font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all flex items-center justify-center shadow-sm"
                            >
                                <DocumentArrowDownIcon class="h-4 w-4 mr-2" /> Exportar
                            </button>

                            <button @click="sendToWhatsApp(areaSummary)" 
                                    class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center justify-center shadow-md shadow-emerald-100 dark:shadow-none">
                                <ChatBubbleBottomCenterTextIcon class="h-4 w-4 mr-2" /> WhatsApp
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center p-20 bg-white dark:bg-gray-800 rounded-[3rem] shadow-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <PrinterIcon class="h-16 w-16 text-gray-300 mx-auto mb-6" />
                    <p class="text-xl font-black text-gray-400 uppercase tracking-widest">No hay pedidos registrados</p>
                </div>

                <div class="flex justify-center pb-12">
                    <Link :href="route('dashboard')" class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 hover:text-indigo-600 transition-colors">
                        Volver al Panel Principal
                    </Link>
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
</style>
