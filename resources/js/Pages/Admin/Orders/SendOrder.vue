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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link :href="route('dashboard')" class="mr-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                            Enviar Pedido
                        </h2>
                        <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest">{{ provider.name }} • {{ mealType }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button @click="sendWhatsApp" class="flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-green-100 dark:shadow-none">
                        <ChatBubbleLeftRightIcon class="h-5 w-5 mr-2" /> WhatsApp
                    </button>
                    <PrimaryButton @click="showExportModal = true" class="!rounded-2xl !py-3 !px-6 !text-xs bg-indigo-600 hover:bg-indigo-700 uppercase font-black">
                        <PrinterIcon class="h-5 w-5 mr-2" /> Exportar
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="bg-white dark:bg-gray-800 rounded-[3rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-10 border-b dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Lista de Pedidos Confirmados</h3>
                            <p class="text-xs text-gray-400 font-bold uppercase mt-1">{{ formattedDate }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-4xl font-black text-indigo-600 dark:text-indigo-400 leading-none">{{ orders.length }}</p>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">Total Platillos</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/30">
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest border-b dark:border-gray-700">Área Solicitante</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest border-b dark:border-gray-700">Platillo</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest border-b dark:border-gray-700">Notas / Preferencias</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <tr v-for="(order, idx) in orders" :key="idx" class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors">
                                    <td class="p-6">
                                        <div class="flex items-center">
                                            <BuildingOfficeIcon class="h-4 w-4 text-indigo-500 mr-3" />
                                            <span class="font-black text-sm text-gray-700 dark:text-gray-300 uppercase tracking-tight">{{ order.area }}</span>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <span class="font-black text-sm text-gray-800 dark:text-gray-200">{{ order.platillo }}</span>
                                    </td>
                                    <td class="p-6">
                                        <span v-if="order.preferences" class="text-xs text-gray-500 dark:text-gray-400 italic">"{{ order.preferences }}"</span>
                                        <span v-else class="text-xs text-gray-300 dark:text-gray-600">- Sin notas -</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-center pb-12">
                    <Link :href="route('dashboard')" class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 hover:text-indigo-600 transition-colors">
                        Cancelar y volver al panel
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
