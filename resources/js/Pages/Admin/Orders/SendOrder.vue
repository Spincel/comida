<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ExportChoiceModal from '@/Pages/Admin/Partials/ExportChoiceModal.vue';
import { 
    ChevronLeftIcon, 
    PrinterIcon, 
    ChatBubbleLeftRightIcon,
    BuildingOfficeIcon,
    ClipboardDocumentListIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    provider: Object,
    date: String,
    mealType: String,
    orders: Array, 
});

const showExportModal = ref(false);

const formattedDate = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(props.date).toLocaleDateString('es-ES', options);
});

const sendWhatsApp = () => {
    const phone = props.provider.contact_phone ? props.provider.contact_phone.replace(/\D/g, '') : '';
    if (!phone) return alert('El proveedor no tiene un número de contacto registrado.');

    let message = `*PEDIDO DE ${props.mealType.toUpperCase()} - ${props.date}*
`;
    message += `*Proveedor:* ${props.provider.name}

`;
    
    // Group by dish for a cleaner message
    const grouped = {};
    props.orders.forEach(o => {
        const dish = o.platillo;
        if (!grouped[dish]) grouped[dish] = [];
        grouped[dish].push(o);
    });

    for (const dish in grouped) {
        message += `*${dish}* (${grouped[dish].length} unidades):
`;
        grouped[dish].forEach(o => {
            message += `- ${o.area}${o.preferences ? ' ('+o.preferences+')' : ''}
`;
        });
        message += `
`;
    }

    const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};

const handleExport = (format) => {
    const url = route('admin.orders.summary.pdf', { 
        provider: props.provider.id, 
        date: props.date,
        meal_type: props.mealType,
        view_mode: 'dishes',
        format: format
    });
    window.open(url, '_blank');
};
</script>

<template>
    <Head :title="`Enviar Pedido: ${provider.name}`" />

    <AuthenticatedLayout bento-tag="Pedidos">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- HEADER -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('dashboard')" class="p-3 rounded-2xl bg-slate-50 dark:bg-gray-800 text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 border border-slate-200 dark:border-gray-700 transition-all cursor-pointer">
                        <ChevronLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-gray-100 uppercase tracking-tight">
                            Enviar Pedido al Proveedor
                        </h2>
                        <p class="text-xs font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">{{ provider.name }} • {{ mealType }}</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="sendWhatsApp" class="flex items-center px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm cursor-pointer hover:scale-105 active:scale-95">
                        <ChatBubbleLeftRightIcon class="h-5 w-5 mr-2" /> WhatsApp
                    </button>
                    <button @click="showExportModal = true" class="flex items-center px-6 py-3.5 rounded-2xl bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white text-[10px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect">
                        <PrinterIcon class="h-5 w-5 mr-2 text-oro-300" /> Exportar
                    </button>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden">
                <div class="p-8 border-b border-slate-100 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Lista de Pedidos Confirmados</h3>
                        <p class="text-xs text-slate-400 font-bold uppercase mt-1">📅 {{ formattedDate }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black text-tinto-800 dark:text-oro-300 leading-none">{{ orders.length }}</p>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Total Platillos</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-gray-800/80 border-b border-slate-100 dark:border-gray-800">
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Área Solicitante</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Platillo Solicitado</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Notas / Preferencias</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            <tr v-for="(order, idx) in orders" :key="idx" class="hover:bg-tinto-50/30 dark:hover:bg-tinto-950/20 transition-colors">
                                <td class="p-6">
                                    <div class="flex items-center">
                                        <BuildingOfficeIcon class="h-4 w-4 text-tinto-700 dark:text-oro-400 mr-3" />
                                        <span class="font-black text-xs text-slate-700 dark:text-gray-300 uppercase">{{ order.area }}</span>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <span class="font-black text-xs text-slate-800 dark:text-gray-100 uppercase">🍽️ {{ order.platillo }}</span>
                                </td>
                                <td class="p-6">
                                    <span v-if="order.preferences" class="text-xs text-slate-600 dark:text-gray-400 italic">"{{ order.preferences }}"</span>
                                    <span v-else class="text-xs text-slate-300 dark:text-gray-600">- Sin notas -</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-center pb-6">
                <Link :href="route('dashboard')" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 transition-colors">
                    Volver al Monitor Principal
                </Link>
            </div>
        </div>

        <ExportChoiceModal 
            :show="showExportModal"
            @close="showExportModal = false"
            @select="handleExport"
        />
    </AuthenticatedLayout>
</template>
