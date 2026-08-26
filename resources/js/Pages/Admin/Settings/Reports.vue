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
    if (key === 'report_configuration') return { main_title: 'Reporte de Control', report_color: '#78182A', font_size: '10px', default_sort: 'area', show_avatar: true, show_area: true, show_platillo: true, show_preferences: true, show_activity: true, show_signature: true, ...val };
    if (key === 'whatsapp_configuration') return { header_title: 'RESUMEN DE PEDIDOS SICOA', include_names: true, group_by_dish: true, footer_text: 'H. Congreso del Estado de Nayarit', ...val };
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
    { name: 'Juan Pérez', area: 'Secretaría General', dish: 'Chilaquiles con Arrachera', obs: 'S/C', activity: 'Sesión Ordinaria', avatar: 'https://ui-avatars.com/api/?name=JP' },
    { name: 'María G.', area: 'Recursos Humanos', dish: 'Omelet de Espinacas', obs: '', activity: 'Auditoría', avatar: 'https://ui-avatars.com/api/?name=MG' }
];
</script>

<template>
    <Head title="Ajustes de Reportes SICOA" />

    <AuthenticatedLayout bento-tag="Formatos">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-12 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center gap-4">
                <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                    📄
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Formatos y Estructura de Salida</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Personalización de reportes PDF e integración WhatsApp</p>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-8">
                <!-- PDF STYLE -->
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <div class="flex items-center gap-3 mb-6 border-b border-slate-100 dark:border-gray-800 pb-4">
                        <SwatchIcon class="h-5 w-5 text-tinto-700 dark:text-oro-400" />
                        <h4 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-xs">Estilo PDF Oficial</h4>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Título Documento</label>
                            <input type="text" v-model="reportConfig.main_title" @input="debouncedSave('report_configuration')" class="w-full rounded-2xl border-slate-200 dark:border-gray-700 dark:bg-gray-800 text-xs font-bold uppercase py-3 px-4 shadow-inner focus:ring-2 focus:ring-tinto-700" />
                        </div>
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Color Institucional</label>
                            <div class="flex gap-3">
                                <input type="color" v-model="reportConfig.report_color" @input="debouncedSave('report_configuration')" class="h-11 w-16 border-0 bg-transparent cursor-pointer rounded-xl" />
                                <input type="text" v-model="reportConfig.report_color" class="flex-1 rounded-2xl border-slate-200 dark:border-gray-700 dark:bg-gray-800 text-xs font-mono uppercase px-4 shadow-inner" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNS -->
                <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <p class="text-[9px] font-black uppercase text-slate-400 mb-4 flex items-center gap-2 tracking-widest">
                        <ListBulletIcon class="h-4 w-4 text-tinto-700 dark:text-oro-400" /> Columnas a incluir:
                    </p>
                    <div class="space-y-2">
                        <div v-for="f in [{id:'show_avatar', l:'Fotografía'}, {id:'show_area', l:'Área / Dependencia'}, {id:'show_platillo', l:'Menú Seleccionado'}, {id:'show_activity', l:'Justificación'}, {id:'show_signature', l:'Firma de Recibido'}]" :key="f.id" 
                             @click="toggleReportField(f.id)" class="flex items-center justify-between p-3 rounded-2xl border transition-all cursor-pointer" :class="reportConfig[f.id] ? 'bg-tinto-50 dark:bg-tinto-950/40 border-tinto-200 dark:border-tinto-800' : 'border-slate-100 dark:border-gray-800'">
                            <span class="text-[10px] font-black uppercase tracking-tight" :class="reportConfig[f.id] ? 'text-tinto-900 dark:text-oro-300' : 'text-slate-400'">{{ f.l }}</span>
                            <CheckBadgeIcon v-if="reportConfig[f.id]" class="h-5 w-5 text-tinto-700 dark:text-oro-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- PREVIEW -->
            <div class="lg:col-span-8 space-y-8">
                <div class="bg-white dark:bg-gray-900 p-10 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800" :style="{ fontSize: reportConfig.font_size }">
                    <div class="text-center mb-8 border-b-2 pb-6" :style="{ borderColor: reportConfig.report_color }">
                        <p class="text-[10px] uppercase font-black tracking-widest mb-1 opacity-50 dark:text-gray-400">{{ reportConfig.main_title }}</p>
                        <h1 class="text-3xl font-black uppercase m-0" :style="{ color: reportConfig.report_color }">DESAYUNO / COMIDA INSTITUCIONAL</h1>
                        <p class="text-[10px] font-bold mt-2 text-slate-400">H. CONGRESO DEL ESTADO DE NAYARIT — VISTA PREVIA</p>
                    </div>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="text-white" :style="{ backgroundColor: reportConfig.report_color }">
                                <th class="p-3 text-left uppercase text-[9px] font-black">Servidor Público</th>
                                <th v-if="reportConfig.show_area" class="p-3 text-left uppercase text-[9px] font-black">Área</th>
                                <th v-if="reportConfig.show_platillo" class="p-3 text-left uppercase text-[9px] font-black">Menú</th>
                                <th v-if="reportConfig.show_signature" class="p-3 text-center uppercase text-[9px] font-black">Firma</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            <tr v-for="o in sampleOrders" :key="o.name" class="hover:bg-slate-50 dark:hover:bg-gray-800/40">
                                <td class="p-3 flex items-center gap-3">
                                    <img v-if="reportConfig.show_avatar" :src="o.avatar" class="h-6 w-6 rounded-full shadow-xs">
                                    <strong class="font-black uppercase text-[10px] text-slate-800 dark:text-gray-200">{{ o.name }}</strong>
                                </td>
                                <td v-if="reportConfig.show_area" class="p-3 font-bold uppercase text-[9px] text-slate-400">{{ o.area }}</td>
                                <td v-if="reportConfig.show_platillo" class="p-3 font-bold uppercase text-[9px] text-slate-700 dark:text-gray-300">🍽️ {{ o.dish }}</td>
                                <td v-if="reportConfig.show_signature" class="p-3"><div class="border-b border-slate-300 dark:border-gray-600 h-5 w-20 mx-auto"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-[#e5ddd5] dark:bg-[#0b141a] p-6 rounded-[2.5rem] shadow-xl max-w-sm mx-auto border-4 border-slate-800">
                    <div class="bg-white dark:bg-[#1f2c33] p-4 rounded-2xl shadow-sm">
                        <p class="text-[12px] font-bold text-[#075e54] dark:text-[#25d366] mb-1">*{{ whatsappConfig.header_title }}*</p>
                        <p class="text-[11px] leading-tight dark:text-slate-300 font-medium">📍 *SECRETARÍA GENERAL* (2)<br>• 2x Platillo Especial<br><span v-if="whatsappConfig.include_names" class="opacity-50 italic">  - Juan Pérez</span></p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
