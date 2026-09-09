<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    PhotoIcon, SwatchIcon, CloudArrowUpIcon, CheckCircleIcon, ArrowPathIcon,
    UsersIcon, UserGroupIcon, PaintBrushIcon, PlusIcon, TrashIcon,
    SparklesIcon, KeyIcon, EyeIcon, EyeSlashIcon, BoltIcon, ShieldCheckIcon,
    InformationCircleIcon, ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({ settings: Array });

const previews = ref({
    logo_main: props.settings.find(s => s.key === 'logo_main')?.value,
    logo_small: props.settings.find(s => s.key === 'logo_small')?.value,
    logo_report: props.settings.find(s => s.key === 'logo_report')?.value,
    favicon: props.settings.find(s => s.key === 'favicon')?.value,
});

const savingKey = ref(null), lastSavedKey = ref(null);

const saveSetting = (key, value, isFile = false) => {
    savingKey.value = key; lastSavedKey.value = null;
    const formData = new FormData();
    if (isFile) formData.append(key, value); else formData.append(key, value || '');
    router.post(route('admin.settings.interface.update'), formData, {
        preserveScroll: true, forceFormData: true,
        onSuccess: () => { savingKey.value = null; lastSavedKey.value = key; setTimeout(() => { if (lastSavedKey.value === key) lastSavedKey.value = null; }, 3000); }
    });
};

const handleFile = (key, e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (ev) => previews.value[key] = ev.target.result;
        reader.readAsDataURL(file);
        saveSetting(key, file, true);
    }
};

const values = ref(props.settings.reduce((acc, s) => { acc[s.key] = s.value; return acc; }, {}));
const debouncedSave = (key) => { if (window[`timeout_${key}`]) clearTimeout(window[`timeout_${key}`]); window[`timeout_${key}`] = setTimeout(() => { saveSetting(key, values.value[key]); }, 1000); };

const activeTab = ref('logos'); // 'logos', 'system', 'ai'
const showApiKey = ref(false);
const testingGemini = ref(false);
const testResult = ref(null);

const hasAiConfigured = computed(() => {
    return Boolean(values.value.gemini_api_key && values.value.gemini_api_key.trim().length > 0);
});

const testGeminiConnection = async () => {
    testingGemini.value = true;
    testResult.value = null;
    try {
        const response = await axios.post(route('admin.settings.gemini.test'), {
            api_key: values.value.gemini_api_key || '',
        });
        testResult.value = response.data;
    } catch (err) {
        testResult.value = {
            success: false,
            message: err.response?.data?.message || 'Error de comunicación al verificar la clave API.'
        };
    } finally {
        testingGemini.value = false;
    }
};

const clearApiKey = () => {
    if (confirm('¿Deseas deshabilitar la Inteligencia Artificial? La función de escaneo con IA se ocultará en Menús y Proveedores.')) {
        values.value.gemini_api_key = '';
        saveSetting('gemini_api_key', '');
        testResult.value = null;
    }
};
</script>

<template>
    <Head title="Configuración de Interfaz SICOA" />

    <AuthenticatedLayout bento-tag="Configuración">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- HEADER BENTO -->
            <div class="lg:col-span-12 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        🎨
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Identidad & Parámetros</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Personalización Institucional SICOA</p>
                    </div>
                </div>
                <div class="flex flex-wrap bg-slate-100 dark:bg-gray-800 p-1.5 rounded-2xl border border-slate-200 dark:border-gray-700 gap-1">
                    <button @click="activeTab = 'logos'" :class="activeTab === 'logos' ? 'bg-gradient-to-r from-tinto-900 to-tinto-800 text-oro-300 shadow-tinto-sm border border-oro-400/40' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'" class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">Logotipos</button>
                    <button @click="activeTab = 'system'" :class="activeTab === 'system' ? 'bg-gradient-to-r from-tinto-900 to-tinto-800 text-oro-300 shadow-tinto-sm border border-oro-400/40' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'" class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">Sistema</button>
                    <button @click="activeTab = 'ai'" :class="activeTab === 'ai' ? 'bg-gradient-to-r from-tinto-900 to-tinto-800 text-oro-300 shadow-tinto-sm border border-oro-400/40' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'" class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer flex items-center gap-2">
                        <SparklesIcon class="h-3.5 w-3.5 text-oro-400" />
                        <span>Inteligencia Artificial</span>
                        <span v-if="hasAiConfigured" class="h-2 w-2 rounded-full bg-emerald-500 shadow-xs" title="IA Activa"></span>
                        <span v-else class="h-2 w-2 rounded-full bg-slate-400/50" title="IA Inactiva"></span>
                    </button>
                </div>
            </div>

            <!-- LOGOS TAB -->
            <div v-if="activeTab === 'logos'" class="lg:col-span-12 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div v-for="key in ['logo_main', 'logo_small', 'logo_report', 'favicon']" :key="key" class="space-y-4">
                        <p class="text-[9px] font-black uppercase text-slate-400 text-center tracking-widest">{{ key.replace('_', ' ') }}</p>
                        <div @click="$refs[key + 'Input'][0].click()" class="aspect-square rounded-[2.5rem] border-2 border-dashed border-slate-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:border-tinto-700 dark:hover:border-oro-400 transition-all overflow-hidden bg-slate-50 dark:bg-gray-800 relative group shadow-inner">
                            <img v-if="previews[key]" :src="previews[key].startsWith('data') ? previews[key] : '/storage/' + previews[key]" class="max-h-24 object-contain group-hover:scale-110 transition-transform" />
                            <CloudArrowUpIcon v-else class="h-10 w-10 text-slate-300 dark:text-gray-600" />
                            <div v-if="savingKey === key" class="absolute inset-0 bg-white/70 dark:bg-black/70 flex items-center justify-center">
                                <ArrowPathIcon class="h-6 w-6 animate-spin text-tinto-800 dark:text-oro-300" />
                            </div>
                            <input type="file" :ref="key + 'Input'" class="hidden" @change="e => handleFile(key, e)" accept="image/*" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- SYSTEM TAB -->
            <div v-if="activeTab === 'system'" class="lg:col-span-12 space-y-8">
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Nombre de la Aplicación</label>
                            <TextInput type="text" v-model="values.app_name" @input="debouncedSave('app_name')" class="w-full !rounded-2xl !py-4 !px-6 text-xs font-bold" />
                        </div>
                        <div class="md:col-span-2 space-y-4 pt-6 border-t border-slate-100 dark:border-gray-800">
                            <p class="text-[10px] font-black uppercase text-tinto-700 dark:text-oro-400 tracking-widest">Modalidad de Operación</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div @click="values.operation_mode = 'complete'; saveSetting('operation_mode', 'complete')" 
                                     class="p-6 rounded-[2rem] border-2 cursor-pointer transition-all flex items-center gap-4" 
                                     :class="values.operation_mode === 'complete' ? 'border-tinto-700 bg-tinto-50 dark:bg-tinto-950/40 text-tinto-900 dark:text-oro-300' : 'border-slate-100 dark:border-gray-800 text-slate-400'">
                                    <UsersIcon class="h-6 w-6" />
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-tight">Modo Completo</p>
                                        <p class="text-[9px] font-medium opacity-70">Control por comensal y pedidos detallados</p>
                                    </div>
                                </div>
                                <div @click="values.operation_mode = 'simple'; saveSetting('operation_mode', 'simple')" 
                                     class="p-6 rounded-[2rem] border-2 cursor-pointer transition-all flex items-center gap-4" 
                                     :class="values.operation_mode === 'simple' ? 'border-oro-600 bg-oro-50 dark:bg-oro-950/40 text-oro-900 dark:text-oro-300' : 'border-slate-100 dark:border-gray-800 text-slate-400'">
                                    <UserGroupIcon class="h-6 w-6" />
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-tight">Modo Simple</p>
                                        <p class="text-[9px] font-medium opacity-70">Conteo rápido de comensales por área</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- QUICK AI SUMMARY IN SYSTEM TAB -->
                        <div class="md:col-span-2 pt-6 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between bg-slate-50 dark:bg-gray-800/60 p-6 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <SparklesIcon class="h-6 w-6 text-oro-500" />
                                <div>
                                    <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-tight">Motor de Inteligencia Artificial (Gemini)</p>
                                    <p class="text-[10px] text-slate-400 font-semibold">
                                        Estado actual: 
                                        <span v-if="hasAiConfigured" class="text-emerald-600 dark:text-emerald-400 font-bold">Activo (Clave Configurada)</span>
                                        <span v-else class="text-rose-500 font-bold">Inactivo (Funciones Ocultas)</span>
                                    </p>
                                </div>
                            </div>
                            <button @click="activeTab = 'ai'" type="button" class="px-4 py-2.5 rounded-xl bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-200 text-[10px] font-black uppercase tracking-widest border border-slate-200 dark:border-gray-600 hover:border-oro-400 transition-all cursor-pointer flex items-center gap-1.5 shadow-xs">
                                <span>Administrar Clave</span>
                                <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI TAB -->
            <div v-if="activeTab === 'ai'" class="lg:col-span-12 space-y-8">
                <!-- MAIN AI CONFIG CARD -->
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-8 sm:p-10 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none space-y-8">
                    
                    <!-- STATUS BANNER -->
                    <div class="p-6 rounded-[2rem] border transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                         :class="hasAiConfigured ? 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-gray-800/60 border-slate-200 dark:border-gray-700'">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-xl shrink-0"
                                 :class="hasAiConfigured ? 'bg-emerald-500 text-white shadow-emerald-500/20 shadow-md' : 'bg-slate-200 dark:bg-gray-700 text-slate-400'">
                                <SparklesIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <h4 class="text-sm font-black uppercase tracking-tight text-slate-800 dark:text-white">
                                        {{ hasAiConfigured ? 'Inteligencia Artificial Activa' : 'Inteligencia Artificial Deshabilitada' }}
                                    </h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest"
                                          :class="hasAiConfigured ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-300'">
                                        {{ hasAiConfigured ? 'Visible & Operativo' : 'Funciones Ocultas' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium leading-relaxed">
                                    {{ hasAiConfigured 
                                        ? 'La clave API de Gemini está configurada. Los botones de "Escaneo IA de Menú" e "Importar con IA" están disponibles en Menús Diarios, Proveedores y Usuarios.' 
                                        : 'Sin clave API configurada. Las opciones de escaneo con IA se ocultan automáticamente en el menú y los proveedores para evitar errores.' 
                                    }}
                                </p>
                            </div>
                        </div>

                        <div v-if="hasAiConfigured" class="shrink-0">
                            <button @click="clearApiKey" 
                                    type="button" 
                                    class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900 transition-all cursor-pointer flex items-center gap-1.5">
                                <TrashIcon class="h-3.5 w-3.5" />
                                <span>Deshabilitar IA</span>
                            </button>
                        </div>
                    </div>

                    <!-- API KEY FORM FIELD -->
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <label class="text-[10px] font-black uppercase text-tinto-800 dark:text-oro-300 tracking-widest flex items-center gap-2">
                                <KeyIcon class="h-4 w-4 text-oro-500" />
                                Clave API de Google Gemini (GEMINI_API_KEY)
                            </label>
                            <span class="text-[10px] text-slate-400 font-bold">
                                Compatible con modelos Gemini 3.6 Flash, 3.7 Flash y 3.5 Flash
                            </span>
                        </div>

                        <div class="relative">
                            <TextInput 
                                :type="showApiKey ? 'text' : 'password'" 
                                v-model="values.gemini_api_key" 
                                placeholder="Ejemplo: AQ.Ab8RN6... o AIzaSy..."
                                class="w-full !rounded-2xl !py-4.5 !pl-6 !pr-28 text-xs font-mono font-semibold tracking-wider bg-slate-50 dark:bg-gray-800 border-slate-200 dark:border-gray-700 shadow-inner" 
                            />
                            
                            <div class="absolute inset-y-0 right-3 flex items-center gap-1.5">
                                <button @click="showApiKey = !showApiKey" 
                                        type="button" 
                                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-gray-700 transition-all cursor-pointer"
                                        :title="showApiKey ? 'Ocultar clave' : 'Mostrar clave'">
                                    <EyeSlashIcon v-if="showApiKey" class="h-4 w-4" />
                                    <EyeIcon v-else class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <button @click="saveSetting('gemini_api_key', values.gemini_api_key)" 
                                    type="button" 
                                    :disabled="savingKey === 'gemini_api_key'"
                                    class="px-6 py-3.5 rounded-2xl bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white text-[10px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer flex items-center gap-2 shine-effect disabled:opacity-50">
                                <ArrowPathIcon v-if="savingKey === 'gemini_api_key'" class="h-4 w-4 animate-spin text-oro-300" />
                                <CheckCircleIcon v-else-if="lastSavedKey === 'gemini_api_key'" class="h-4 w-4 text-emerald-400" />
                                <KeyIcon v-else class="h-4 w-4 text-oro-300" />
                                <span>{{ savingKey === 'gemini_api_key' ? 'Guardando...' : (lastSavedKey === 'gemini_api_key' ? '¡Clave Guardada!' : 'Guardar Clave') }}</span>
                            </button>

                            <button @click="testGeminiConnection" 
                                    type="button" 
                                    :disabled="testingGemini || !values.gemini_api_key"
                                    class="px-6 py-3.5 rounded-2xl bg-white dark:bg-gray-800 border border-oro-400/50 text-tinto-900 dark:text-oro-300 text-[10px] font-black uppercase tracking-widest hover:bg-oro-50/60 dark:hover:bg-oro-950/30 active:scale-95 transition-all cursor-pointer flex items-center gap-2 shadow-xs disabled:opacity-40 disabled:cursor-not-allowed">
                                <ArrowPathIcon v-if="testingGemini" class="h-4 w-4 animate-spin text-oro-500" />
                                <BoltIcon v-else class="h-4 w-4 text-oro-500" />
                                <span>{{ testingGemini ? 'Verificando con Google...' : '⚡ Probar Conexión' }}</span>
                            </button>
                        </div>

                        <!-- TEST RESULT BANNER -->
                        <div v-if="testResult" class="mt-4 p-5 rounded-2xl border transition-all animate-fadeIn"
                             :class="testResult.success ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 text-emerald-900 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/30 border-rose-300 text-rose-900 dark:text-rose-200'">
                            <div class="flex items-start gap-3">
                                <CheckCircleIcon v-if="testResult.success" class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                                <ExclamationCircleIcon v-else class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                                <div class="text-xs">
                                    <p class="font-black uppercase tracking-wider mb-0.5">
                                        {{ testResult.success ? 'Validación Exitosa' : 'Fallo en la Validación' }}
                                    </p>
                                    <p class="font-medium opacity-90 leading-relaxed">{{ testResult.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GUIDANCE & INFORMATION CARD -->
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-8 sm:p-10 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none">
                    <div class="flex items-center gap-3 mb-6">
                        <InformationCircleIcon class="h-6 w-6 text-oro-500" />
                        <h4 class="text-sm font-black uppercase text-slate-800 dark:text-white tracking-tight">
                            ¿Cómo funciona el escaneo con IA en SICOA?
                        </h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-6 rounded-[2rem] bg-slate-50 dark:bg-gray-800/50 border border-slate-100 dark:border-gray-700/60 space-y-2">
                            <span class="text-xs font-black text-oro-600 dark:text-oro-400 uppercase tracking-widest block">Paso 1: Fotografía</span>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                                El administrador o gerente sube una fotografía del menú impreso o PDF del banquetero.
                            </p>
                        </div>
                        <div class="p-6 rounded-[2rem] bg-slate-50 dark:bg-gray-800/50 border border-slate-100 dark:border-gray-700/60 space-y-2">
                            <span class="text-xs font-black text-oro-600 dark:text-oro-400 uppercase tracking-widest block">Paso 2: Extracción IA</span>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                                Los algoritmos de visión de Google Gemini leen la imagen, categorizan los platillos y detectan descripciones.
                            </p>
                        </div>
                        <div class="p-6 rounded-[2rem] bg-slate-50 dark:bg-gray-800/50 border border-slate-100 dark:border-gray-700/60 space-y-2">
                            <span class="text-xs font-black text-oro-600 dark:text-oro-400 uppercase tracking-widest block">Paso 3: Aprobación</span>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                                Se muestra una tabla interactiva para revisar, corregir o agregar precios antes de publicar el menú del día.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <p class="text-xs text-slate-400 font-medium">
                            ¿No tienes una clave API? Puedes generar una gratuita en la plataforma de Google.
                        </p>
                        <a href="https://aistudio.google.com/app/apikey" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 text-[10px] font-black uppercase tracking-widest hover:text-oro-600 dark:hover:text-oro-400 transition-colors cursor-pointer border border-slate-200 dark:border-gray-700">
                            <span>Ir a Google AI Studio</span>
                            <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
