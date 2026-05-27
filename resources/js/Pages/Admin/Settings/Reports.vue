<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import { 
    ChatBubbleLeftRightIcon, CheckCircleIcon, ArrowPathIcon, DocumentChartBarIcon, EyeIcon, 
    UserIcon, CheckBadgeIcon, PrinterIcon, SwatchIcon, ListBulletIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ settings: { type: Array, default: () => [] } });

const getSetting = (key) => {
    if (!props.settings || !Array.isArray(props.settings)) return {};
    const s = props.settings.find(s => s.key === key);
    let val = {}; try { val = s ? JSON.parse(s.value) : {}; } catch (e) {}
    if (key === 'report_configuration') return { main_title: 'Reporte de Control', report_color: '#4f46e5', font_size: '10px', default_sort: 'area', show_avatar: true, show_area: true, show_platillo: true, show_preferences: true, show_activity: true, show_signature: true, ...val };
    if (key === 'whatsapp_configuration') return { header_title: 'RESUMEN DE PEDIDOS', include_names: true, group_by_dish: true, footer_text: 'Generado desde Sistema', ...val };
    return val;
};

const reportConfig = ref(getSetting('report_configuration')), whatsappConfig = ref(getSetting('whatsapp_configuration'));
const savingKey = ref(null), lastSavedKey = ref(null);

const saveSetting = (key, value) => {
    savingKey.value = key; lastSavedKey.value = null;
    router.post(route('admin.settings.interface.update'), { [key]: JSON.stringify(value) }, {
        preserveScroll: true, onSuccess: () => { savingKey.value = null; lastSavedKey.value = key; setTimeout(() => { if (lastSavedKey.value === key) lastSavedKey.value = null; }, 3000); }
    });
};

const debouncedSave = (key) => { if (window[`timeout_${key}`]) clearTimeout(window[`timeout_${key}`]); window[`timeout_${key}`] = setTimeout(() => { saveSetting(key, key === 'report_configuration' ? reportConfig.value : whatsappConfig.value); }, 1000); };
const toggleReportField = (f) => { reportConfig.value[f] = !reportConfig.value[f]; saveSetting('report_configuration', reportConfig.value); };
const updateWhatsappField = (f) => { whatsappConfig.value[f] = !whatsappConfig.value[f]; saveSetting('whatsapp_configuration', whatsappConfig.value); };

const sampleOrders = [
    { name: 'Juan Pérez', area: 'Sistemas', dish: 'Chilaquiles', obs: 'S/C', activity: 'Mantenimiento', avatar: 'https://ui-avatars.com/api/?name=JP' },
    { name: 'María G.', area: 'RH', dish: 'Enchiladas', obs: '', activity: 'Nómina', avatar: 'https://ui-avatars.com/api/?name=MG' }
];
</script>

<template>
    <Head title="Ajustes Reportes V2.0" />

    <AuthenticatedLayout bento-tag="Formatos">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-12 flex items-center gap-3 mb-4"><DocumentChartBarIcon class="h-6 w-6 text-indigo-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Estructura de Salida</h3></div>

            <div class="lg:col-span-4 space-y-8">
                <!-- PDF STYLE -->
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[3rem] shadow-sm border border-slate-100 dark:border-gray-800">
                    <div class="flex items-center gap-4 mb-8 border-b dark:border-gray-800 pb-6"><SwatchIcon class="h-6 w-6 text-indigo-600" /><h4 class="font-black text-slate-800 dark:text-white uppercase tracking-widest">Estilo PDF</h4></div>
                    <div class="space-y-6">
                        <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Título Documento</label><input type="text" v-model="reportConfig.main_title" @input="debouncedSave('report_configuration')" class="w-full rounded-2xl border-slate-100 dark:bg-gray-800 text-xs font-bold uppercase" /></div>
                        <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Color Identidad</label><div class="flex gap-4"><input type="color" v-model="reportConfig.report_color" @input="debouncedSave('report_configuration')" class="h-10 w-16 border-0 bg-transparent cursor-pointer" /><input type="text" v-model="reportConfig.report_color" class="flex-1 rounded-xl border-slate-100 dark:bg-gray-800 text-[10px] font-mono uppercase" /></div></div>
                    </div>
                </div>

                <!-- COLUMNS -->
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[3rem] shadow-sm border border-slate-100 dark:border-gray-800">
                    <p class="text-[9px] font-black uppercase text-slate-400 mb-6 flex items-center gap-2"><ListBulletIcon class="h-4 w-4" /> Datos a incluir:</p>
                    <div class="space-y-2">
                        <div v-for="f in [{id:'show_avatar', l:'Foto'}, {id:'show_area', l:'Área'}, {id:'show_platillo', l:'Platillo'}, {id:'show_activity', l:'Justificación'}, {id:'show_signature', l:'Firma'}]" :key="f.id" 
                             @click="toggleReportField(f.id)" class="flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer" :class="reportConfig[f.id] ? 'bg-indigo-50 border-indigo-200' : 'border-slate-50 dark:border-gray-800'">
                            <span class="text-[10px] font-black uppercase" :class="reportConfig[f.id] ? 'text-indigo-700' : 'text-slate-400'">{{ f.l }}</span>
                            <CheckBadgeIcon v-if="reportConfig[f.id]" class="h-4 w-4 text-indigo-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- PREVIEW -->
            <div class="lg:col-span-8 space-y-8">
                <div class="bg-white p-12 rounded-[3.5rem] shadow-xl border border-slate-200" :style="{ fontSize: reportConfig.font_size, color: '#333' }">
                    <div class="text-center mb-10 border-b-2 pb-6" :style="{ borderColor: reportConfig.report_color }">
                        <p class="text-[10px] uppercase font-black tracking-widest mb-2 opacity-40">{{ reportConfig.main_title }}</p>
                        <h1 class="text-4xl font-black uppercase m-0" :style="{ color: reportConfig.report_color }">TIPO ALIMENTO</h1>
                        <p class="text-xs font-bold mt-4">VISTA PREVIA DE MEMBRETE</p>
                    </div>
                    <table class="w-full border-collapse">
                        <thead><tr class="text-white" :style="{ backgroundColor: reportConfig.report_color }"><th class="p-3 text-left uppercase text-[9px] font-black">Usuario</th><th v-if="reportConfig.show_area" class="p-3 text-left uppercase text-[9px] font-black">Área</th><th v-if="reportConfig.show_platillo" class="p-3 text-left uppercase text-[9px] font-black">Menú</th><th v-if="reportConfig.show_signature" class="p-3 text-center uppercase text-[9px] font-black">Firma</th></tr></thead>
                        <tbody><tr v-for="o in sampleOrders" :key="o.name" class="border-b border-slate-100"><td class="p-3 flex items-center gap-3"><img v-if="reportConfig.show_avatar" :src="o.avatar" class="h-6 w-6 rounded-full shadow-sm"><strong class="font-black uppercase text-[10px]">{{ o.name }}</strong></td><td v-if="reportConfig.show_area" class="p-3 font-bold uppercase text-[9px] opacity-60">{{ o.area }}</td><td v-if="reportConfig.show_platillo" class="p-3 font-bold uppercase text-[9px]">{{ o.dish }}</td><td v-if="reportConfig.show_signature" class="p-3"><div class="border-b border-slate-200 h-6 w-20 mx-auto"></div></td></tr></tbody>
                    </table>
                </div>

                <div class="bg-[#e5ddd5] dark:bg-[#0b141a] p-8 rounded-[3rem] shadow-xl max-w-sm mx-auto border-8 border-slate-900">
                    <div class="bg-white dark:bg-[#1f2c33] p-4 rounded-xl shadow-md"><p class="text-[12px] font-bold text-[#075e54] dark:text-[#25d366] mb-1">*{{ whatsappConfig.header_title }}*</p><p class="text-[11px] leading-tight dark:text-slate-300 font-medium">📍 *SISTEMAS* (2)<br>• 2x Platillo Especial<br><span v-if="whatsappConfig.include_names" class="opacity-50 italic">  - Juan Pérez</span></p></div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
