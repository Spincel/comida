<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { 
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    DocumentChartBarIcon,
    EyeIcon,
    UserIcon,
    CheckBadgeIcon,
    PrinterIcon,
    SwatchIcon,
    ListBulletIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: { type: Array, default: () => [] },
});

// Parse JSON settings with deep defaults
const getSetting = (key) => {
    if (!props.settings || !Array.isArray(props.settings)) return {};
    const s = props.settings.find(s => s.key === key);
    let val = {};
    try {
        val = s ? JSON.parse(s.value) : {};
    } catch (e) {
        console.error("Error parsing setting " + key, e);
    }

    if (key === 'report_configuration') {
        return {
            main_title: 'Reporte de Control de Alimentos',
            report_color: '#4f46e5',
            font_size: '10px',
            default_sort: 'area',
            show_avatar: true,
            show_area: true,
            show_platillo: true,
            show_preferences: true,
            show_activity: true,
            show_signature: true,
            ...val
        };
    }
    if (key === 'whatsapp_configuration') {
        return {
            header_title: 'RESUMEN DE PEDIDOS',
            include_names: true,
            group_by_dish: true,
            footer_text: 'Generado desde Sistema Comedor',
            ...val
        };
    }
    return val;
};

const reportConfig = ref(getSetting('report_configuration'));
const whatsappConfig = ref(getSetting('whatsapp_configuration'));

const savingKey = ref(null);
const lastSavedKey = ref(null);

const saveSetting = (key, value) => {
    savingKey.value = key;
    lastSavedKey.value = null;

    router.post(route('admin.settings.interface.update'), {
        [key]: JSON.stringify(value)
    }, {
        preserveScroll: true,
        onSuccess: () => {
            savingKey.value = null;
            lastSavedKey.value = key;
            setTimeout(() => {
                if (lastSavedKey.value === key) lastSavedKey.value = null;
            }, 3000);
        },
        onError: () => {
            savingKey.value = null;
        }
    });
};

const debouncedSave = (key) => {
    if (window[`timeout_${key}`]) clearTimeout(window[`timeout_${key}`]);
    window[`timeout_${key}`] = setTimeout(() => {
        saveSetting(key, key === 'report_configuration' ? reportConfig.value : whatsappConfig.value);
    }, 1000);
};

const toggleReportField = (field) => {
    reportConfig.value[field] = !reportConfig.value[field];
    saveSetting('report_configuration', reportConfig.value);
};

const updateWhatsappField = (field) => {
    whatsappConfig.value[field] = !whatsappConfig.value[field];
    saveSetting('whatsapp_configuration', whatsappConfig.value);
};

// PREVIEW SAMPLES
const sampleOrders = [
    { name: 'Juan Pérez', area: 'Sistemas', dish: 'Chilaquiles Rojos', obs: 'Sin cebolla', activity: 'Mantenimiento Servidores', avatar: 'https://ui-avatars.com/api/?name=Juan+Perez&background=4f46e5&color=fff' },
    { name: 'María García', area: 'Contabilidad', dish: 'Enchiladas Verdes', obs: '', activity: 'Cierre de Mes', avatar: 'https://ui-avatars.com/api/?name=Maria+Garcia&background=10b981&color=fff' }
];
</script>

<template>
    <Head title="Configuración de Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center px-4">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Personalización Maestro
                    </h2>
                    <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mt-1">Edita títulos, colores y estructura de reportes</p>
                </div>
                <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-2xl border border-green-100 dark:border-green-800">
                    <CheckCircleIcon class="h-5 w-5 text-green-500" />
                    <span class="text-[10px] font-black text-green-700 dark:text-green-400 uppercase tracking-widest">Auto-guardado activo</span>
                </div>
            </div>
        </template>

        <div v-if="reportConfig && whatsappConfig" class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-10">
                
                <!-- PANEL DE CONFIGURACIÓN (LADO IZQUIERDO) -->
                <div class="lg:col-span-5 space-y-8">
                    <!-- Configuración Visual del Documento -->
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-8 shadow-2xl border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-4 mb-8 border-b dark:border-gray-700 pb-6">
                            <div class="h-12 w-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg"><SwatchIcon class="h-7 w-7" /></div>
                            <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Estilo del Reporte</h3>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <InputLabel value="Título Principal del Documento" class="text-[9px] font-black uppercase text-gray-400 mb-2 ml-1" />
                                <input type="text" v-model="reportConfig.main_title" @input="debouncedSave('report_configuration')" 
                                       class="w-full rounded-2xl border-gray-100 dark:bg-gray-900 text-sm font-bold uppercase focus:ring-indigo-500" placeholder="Ej. Reporte de Control de Alimentos" />
                            </div>

                            <div>
                                <InputLabel value="Color Temático (Cabeceras)" class="text-[9px] font-black uppercase text-gray-400 mb-2 ml-1" />
                                <div class="flex items-center gap-4">
                                    <input type="color" v-model="reportConfig.report_color" @input="debouncedSave('report_configuration')" 
                                           class="h-12 w-20 rounded-xl border-0 cursor-pointer bg-transparent" />
                                    <input type="text" v-model="reportConfig.report_color" @input="debouncedSave('report_configuration')" 
                                           class="flex-1 rounded-xl border-gray-100 dark:bg-gray-900 text-xs font-mono uppercase" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Tamaño de Fuente" class="text-[9px] font-black uppercase text-gray-400 mb-2 ml-1" />
                                    <select v-model="reportConfig.font_size" @change="saveSetting('report_configuration', reportConfig)" class="w-full rounded-xl border-gray-200 dark:bg-gray-900 text-xs font-bold uppercase">
                                        <option value="8px">8px (Muy Pequeña)</option>
                                        <option value="9px">9px (Pequeña)</option>
                                        <option value="10px">10px (Estándar)</option>
                                        <option value="11px">11px (Grande)</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Orden de Datos" class="text-[9px] font-black uppercase text-gray-400 mb-2 ml-1" />
                                    <select v-model="reportConfig.default_sort" @change="saveSetting('report_configuration', reportConfig)" class="w-full rounded-xl border-gray-200 dark:bg-gray-900 text-xs font-bold uppercase">
                                        <option value="area">Por Dependencia</option>
                                        <option value="platillo">Por Platillo</option>
                                        <option value="name">Alfabético General</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visibilidad de Columnas -->
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-8 shadow-2xl border border-gray-100 dark:border-gray-700">
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400 mb-6 px-2 flex items-center gap-2">
                            <ListBulletIcon class="h-4 w-4" /> Columnas a incluir:
                        </p>
                        <div class="grid grid-cols-1 gap-2">
                            <div v-for="field in [
                                {id: 'show_avatar', label: 'Foto del Comensal'},
                                {id: 'show_area', label: 'Nombre de Dependencia'},
                                {id: 'show_platillo', label: 'Nombre del Platillo'},
                                {id: 'show_preferences', label: 'Notas / Preferencias'},
                                {id: 'show_activity', label: 'Actividad / Justificación'},
                                {id: 'show_signature', label: 'Recuadro de Firma'}
                            ]" :key="field.id" 
                            class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer shadow-sm"
                            :class="reportConfig[field.id] ? 'border-indigo-500 bg-indigo-50/20' : 'border-gray-50 dark:border-gray-700'"
                            @click="toggleReportField(field.id)">
                                <span class="text-[10px] font-black uppercase tracking-tight" :class="reportConfig[field.id] ? 'text-indigo-700' : 'text-gray-400'">{{ field.label }}</span>
                                <div class="h-5 w-5 rounded-full border-2 flex items-center justify-center transition-all" :class="reportConfig[field.id] ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-gray-200'">
                                    <CheckBadgeIcon v-if="reportConfig[field.id]" class="h-3 w-3" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración WhatsApp -->
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-8 shadow-2xl border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-4 mb-8 border-b dark:border-gray-700 pb-6">
                            <div class="h-12 w-12 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-lg"><ChatBubbleLeftRightIcon class="h-7 w-7" /></div>
                            <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight leading-none">WhatsApp</h3>
                        </div>
                        <div class="space-y-6">
                            <div>
                                <InputLabel value="Encabezado del Mensaje" class="text-[9px] font-black uppercase text-gray-400 mb-2 ml-1" />
                                <input type="text" v-model="whatsappConfig.header_title" @input="debouncedSave('whatsapp_configuration')" 
                                       class="w-full rounded-2xl border-gray-100 dark:bg-gray-900 text-sm font-bold uppercase focus:ring-emerald-500" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="field in [{id: 'include_names', label: 'Ver Nombres'}, {id: 'group_by_dish', label: 'Agrupar Platos'}]" :key="field.id" 
                                     @click="updateWhatsappField(field.id)" class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col items-center text-center gap-2"
                                     :class="whatsappConfig[field.id] ? 'border-emerald-500 bg-emerald-50/30' : 'border-gray-100 dark:border-gray-700'">
                                    <span class="text-[9px] font-black uppercase" :class="whatsappConfig[field.id] ? 'text-emerald-700' : 'text-gray-400'">{{ field.label }}</span>
                                    <CheckCircleIcon v-if="whatsappConfig[field.id]" class="h-5 w-5 text-emerald-600" />
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Pie de Página del Chat" class="text-[9px] font-black uppercase text-gray-400 mb-2 ml-1" />
                                <textarea v-model="whatsappConfig.footer_text" @input="debouncedSave('whatsapp_configuration')" rows="2" class="w-full rounded-2xl border-gray-100 dark:bg-gray-900 text-xs shadow-inner uppercase font-bold" placeholder="Ej. Saludos..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA PREVIA (LADO DERECHO) -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="sticky top-24 space-y-8">
                        <div class="flex items-center justify-between px-6">
                            <h4 class="text-sm font-black uppercase tracking-[0.4em] text-gray-400 flex items-center gap-2"><EyeIcon class="h-5 w-5" /> Vista Previa Dinámica</h4>
                        </div>

                        <!-- Preview PDF -->
                        <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden transform hover:scale-[1.01] transition-transform">
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 border-b flex items-center justify-between">
                                <span class="text-[9px] font-black uppercase text-gray-400 tracking-widest">Pre-visualización Documento PDF</span>
                                <PrinterIcon class="h-4 w-4 text-gray-300" />
                            </div>
                            <div class="p-10 bg-white" :style="{ fontSize: reportConfig.font_size }">
                                <div class="text-center mb-8 border-b-2 pb-6" :style="{ borderColor: reportConfig.report_color }">
                                    <p class="text-[10px] uppercase text-gray-400 font-black tracking-[0.3em] mb-2">{{ reportConfig.main_title }}</p>
                                    <h1 class="uppercase font-black m-0 leading-none text-4xl" :style="{ color: reportConfig.report_color }">COMIDA</h1>
                                    <p class="text-[12px] font-bold mt-3 text-gray-600 uppercase">Proveedor de Muestra S.A.</p>
                                    <p class="text-[9px] text-gray-400 mt-1 uppercase font-black">04 de Marzo de 2026</p>
                                </div>
                                
                                <div class="bg-gray-50 p-3 border-l-4 mb-6 uppercase font-black text-[10px]" :style="{ borderLeftColor: reportConfig.report_color, color: reportConfig.report_color }">
                                    DEPENDENCIAS: SISTEMAS (2 pedidos)
                                </div>

                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="text-white" :style="{ backgroundColor: reportConfig.report_color }">
                                            <th class="p-3 text-left uppercase text-[8px] font-black">Comensal</th>
                                            <th v-if="reportConfig.show_area" class="p-3 text-left uppercase text-[8px] font-black">Dependencia</th>
                                            <th v-if="reportConfig.show_platillo" class="p-3 text-left uppercase text-[8px] font-black">Platillo</th>
                                            <th v-if="reportConfig.show_preferences" class="p-3 text-left uppercase text-[8px] font-black">Obs.</th>
                                            <th v-if="reportConfig.show_activity" class="p-3 text-left uppercase text-[8px] font-black">Actividad</th>
                                            <th v-if="reportConfig.show_signature" class="p-3 text-center uppercase text-[8px] font-black">Firma</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-800">
                                        <tr v-for="o in sampleOrders" :key="o.name" class="border-b border-gray-100">
                                            <td class="p-3 flex items-center gap-3">
                                                <img v-if="reportConfig.show_avatar" :src="o.avatar" class="h-6 w-6 rounded-full border border-gray-200 shadow-sm">
                                                <strong class="font-black uppercase text-[10px]">{{ o.name }}</strong>
                                            </td>
                                            <td v-if="reportConfig.show_area" class="p-3 font-bold uppercase text-[9px] text-gray-500">{{ o.area }}</td>
                                            <td v-if="reportConfig.show_platillo" class="p-3 font-bold uppercase text-[9px]">{{ o.dish }}</td>
                                            <td v-if="reportConfig.show_preferences" class="p-3 italic text-gray-400 text-[9px]">{{ o.obs || '-' }}</td>
                                            <td v-if="reportConfig.show_activity" class="p-3 text-[9px] font-medium">{{ o.activity }}</td>
                                            <td v-if="reportConfig.show_signature" class="p-3"><div class="border-b border-gray-300 h-6 w-20 mx-auto"></div></td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div class="mt-12 text-center">
                                    <div class="inline-block border-t border-gray-400 px-10 pt-2">
                                        <p class="text-[8px] font-black uppercase text-gray-400 tracking-widest leading-none">Sello de Recibido y Firma de Conformidad</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview WhatsApp -->
                        <div class="bg-[#e5ddd5] dark:bg-[#0b141a] rounded-[3.5rem] shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden relative max-w-sm mx-auto transform hover:rotate-1 transition-transform">
                            <div class="bg-[#075e54] dark:bg-[#202c33] p-5 flex items-center gap-4 text-white">
                                <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border border-white/10 shadow-inner"><UserIcon class="h-6 w-6" /></div>
                                <div><p class="text-sm font-bold leading-none">Servicio Comedor</p><p class="text-[9px] opacity-70 mt-1.5 font-bold uppercase tracking-widest">En línea</p></div>
                            </div>
                            <div class="p-8 space-y-4">
                                <div class="bg-white dark:bg-[#1f2c33] p-5 rounded-[1.5rem] rounded-tl-none shadow-xl relative max-w-[95%] border dark:border-0 border-black/5">
                                    <div class="text-[12px] leading-relaxed whitespace-pre-wrap dark:text-[#e9edef] font-medium">
<span class="font-bold text-[#075e54] dark:text-[#25d366] text-[13px] block mb-1">*{{ whatsappConfig.header_title || 'RESUMEN DE PEDIDOS' }} - COMIDA*</span>
*Proveedor:* Antojitos Mexicanos
*Fecha:* 04 de Marzo 2026
----------------------------

📍 *SISTEMAS* (2)
• 2x Chilaquiles Rojos
<span v-if="whatsappConfig.include_names" class="opacity-60 italic text-[11px] block mt-1 ml-4 border-l-2 pl-2">  - Juan Pérez
  - Pedro López</span>

📍 *CONTABILIDAD* (1)
• 1x Enchiladas
<span v-if="whatsappConfig.include_names" class="opacity-60 italic text-[11px] block mt-1 ml-4 border-l-2 pl-2">  - María García</span>

<span class="uppercase font-black text-[10px] block mt-4 border-t pt-2 border-dashed">_{{ whatsappConfig.footer_text || 'Generado desde Sistema Comedor' }}_</span>
                                    </div>
                                    <span class="text-[9px] text-gray-400 absolute right-3 bottom-2 font-mono">12:00 PM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@keyframes glow-indigo { 0%, 100% { border-color: rgba(99, 102, 241, 0.3); } 50% { border-color: rgba(99, 102, 241, 1); box-shadow: 0 0 20px rgba(99, 102, 241, 0.4); } }
.animate-glow-indigo { animation: glow-indigo 1.5s infinite; }
@keyframes glow-amber { 0%, 100% { border-color: rgba(245, 158, 11, 0.3); } 50% { border-color: rgba(245, 158, 11, 1); box-shadow: 0 0 20px rgba(245, 158, 11, 0.4); } }
.animate-glow-amber { animation: glow-amber 1.5s infinite; }
</style>
