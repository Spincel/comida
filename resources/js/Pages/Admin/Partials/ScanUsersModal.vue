<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    XMarkIcon, 
    DocumentArrowUpIcon, 
    PhotoIcon, 
    SparklesIcon, 
    CheckCircleIcon, 
    ExclamationCircleIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    TrashIcon,
    UsersIcon,
    BuildingOfficeIcon,
    CheckBadgeIcon,
    UserPlusIcon,
    KeyIcon,
    MagnifyingGlassIcon,
    GlobeAltIcon,
    LinkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    areas: Array,
});

const emit = defineEmits(['close', 'usersImported']);

// --- States: 'upload', 'scanning', 'review' ---
const step = ref('upload');
const importMode = ref('file'); // 'file' or 'url'
const webUrl = ref('https://congresonayarit.gob.mx/directorio-de-funcionarios/');
const scanProgress = ref(0);
const progressInterval = ref(null);
const currentScanMessage = ref('Iniciando lectura inteligente...');

const fileInput = ref(null);
const previewImage = ref(null);
const currentFile = ref(null);

const scannedUsers = ref([]); 
const scanError = ref(null);
const defaultPassword = ref('password123');
const searchFilter = ref('');
const isSubmittingImport = ref(false);

const scanMessagesFile = [
    'Leyendo imagen y estructura del documento...',
    'Identificando departamentos y áreas asignadas...',
    'Extrayendo personal, nombres y apellidos...',
    'Mapeando números de empleado y puestos...',
    'Estructurando plantilla de personal para importación...'
];

const scanMessagesUrl = [
    'Conectando con el portal web institucional...',
    'Extrayendo directorio oficial de funcionarios...',
    'Identificando titulares, direcciones y unidades...',
    'Limpiando grados académicos y normalizando nombres...',
    'Generando plantilla de personal para importación...'
];

watch(() => props.show, (isVisible) => {
    if (isVisible) {
        resetAll();
    }
});

const resetAll = () => {
    step.value = 'upload';
    importMode.value = 'file';
    scanProgress.value = 0;
    if (progressInterval.value) clearInterval(progressInterval.value);
    fileInput.value = null;
    previewImage.value = null;
    currentFile.value = null;
    scannedUsers.value = [];
    scanError.value = null;
    searchFilter.value = '';
    defaultPassword.value = 'password123';
    isSubmittingImport.value = false;
};

const selectFile = () => fileInput.value.click();

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        currentFile.value = file;
        scanError.value = null;
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (ev) => previewImage.value = ev.target.result;
            reader.readAsDataURL(file);
        } else {
            previewImage.value = null;
        }
    }
};

const removeFile = () => {
    currentFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
    previewImage.value = null;
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const startScan = async () => {
    if (!currentFile.value) return;

    step.value = 'scanning';
    scanProgress.value = 5;
    scanError.value = null;

    let msgIdx = 0;
    currentScanMessage.value = scanMessagesFile[0];

    // Progress animation
    progressInterval.value = setInterval(() => {
        if (scanProgress.value < 90) {
            scanProgress.value += Math.floor(Math.random() * 8) + 2;
            msgIdx = Math.min(Math.floor((scanProgress.value / 90) * scanMessagesFile.length), scanMessagesFile.length - 1);
            currentScanMessage.value = scanMessagesFile[msgIdx];
        }
    }, 600);

    const formData = new FormData();
    formData.append('document', currentFile.value);

    try {
        const response = await axios.post(route('users.scanDocument'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (progressInterval.value) clearInterval(progressInterval.value);
        scanProgress.value = 100;

        setTimeout(() => {
            const users = response.data.users || [];
            if (users.length === 0) {
                step.value = 'upload';
                scanError.value = 'No se detectaron personas o áreas en el documento proporcionado. Intenta con una imagen más nítida o un documento con texto legible.';
            } else {
                scannedUsers.value = users.map((u, idx) => ({
                    id: 'temp_' + idx,
                    first_name: u.first_name || '',
                    last_name: u.last_name || '',
                    second_last_name: u.second_last_name || '',
                    full_name: u.full_name || '',
                    area_name: u.area_name || 'General',
                    area_id: u.area_id || null,
                    employee_number: u.employee_number || '',
                    position: u.position || '',
                    role: u.role || 'area_manager',
                    email: u.email || '',
                    selected: true,
                }));
                step.value = 'review';
            }
        }, 500);

    } catch (error) {
        if (progressInterval.value) clearInterval(progressInterval.value);
        step.value = 'upload';
        scanError.value = error.response?.data?.error || error.response?.data?.message || 'Ocurrió un error al procesar el archivo con Inteligencia Artificial. Intenta nuevamente.';
        console.error('Scan users error:', error);
    }
};

const startUrlScan = async () => {
    if (!webUrl.value || !webUrl.value.startsWith('http')) {
        scanError.value = 'Por favor ingresa una URL válida (iniciando con http:// o https://).';
        return;
    }

    step.value = 'scanning';
    scanProgress.value = 5;
    scanError.value = null;

    let msgIdx = 0;
    currentScanMessage.value = scanMessagesUrl[0];

    // Progress animation
    progressInterval.value = setInterval(() => {
        if (scanProgress.value < 90) {
            scanProgress.value += Math.floor(Math.random() * 8) + 2;
            msgIdx = Math.min(Math.floor((scanProgress.value / 90) * scanMessagesUrl.length), scanMessagesUrl.length - 1);
            currentScanMessage.value = scanMessagesUrl[msgIdx];
        }
    }, 600);

    try {
        const response = await axios.post(route('users.scanUrl'), {
            url: webUrl.value
        });

        if (progressInterval.value) clearInterval(progressInterval.value);
        scanProgress.value = 100;

        setTimeout(() => {
            const users = response.data.users || [];
            if (users.length === 0) {
                step.value = 'upload';
                scanError.value = 'No se detectaron funcionarios en la página web proporcionada. Verifica el enlace ingresado.';
            } else {
                scannedUsers.value = users.map((u, idx) => ({
                    id: 'temp_url_' + idx,
                    first_name: u.first_name || '',
                    last_name: u.last_name || '',
                    second_last_name: u.second_last_name || '',
                    full_name: u.full_name || '',
                    area_name: u.area_name || 'General',
                    area_id: u.area_id || null,
                    employee_number: u.employee_number || '',
                    position: u.position || '',
                    role: u.role || 'area_manager',
                    email: u.email || '',
                    selected: true,
                }));
                step.value = 'review';
            }
        }, 500);

    } catch (error) {
        if (progressInterval.value) clearInterval(progressInterval.value);
        step.value = 'upload';
        scanError.value = error.response?.data?.error || error.response?.data?.message || 'Ocurrió un error al procesar el enlace web con Inteligencia Artificial.';
        console.error('Scan URL error:', error);
    }
};

// Computed stats and filtered results
const selectedCount = computed(() => scannedUsers.value.filter(u => u.selected).length);

const uniqueDetectedAreas = computed(() => {
    const areasMap = {};
    scannedUsers.value.forEach(u => {
        const aName = u.area_name || 'Sin Área';
        areasMap[aName] = (areasMap[aName] || 0) + 1;
    });
    return areasMap;
});

const allSelected = computed({
    get: () => scannedUsers.value.length > 0 && scannedUsers.value.every(u => u.selected),
    set: (val) => {
        scannedUsers.value.forEach(u => { u.selected = val; });
    }
});

const filteredScannedUsers = computed(() => {
    if (!searchFilter.value) return scannedUsers.value;
    const term = searchFilter.value.toLowerCase();
    return scannedUsers.value.filter(u => 
        (u.first_name + ' ' + u.last_name + ' ' + u.second_last_name).toLowerCase().includes(term) ||
        (u.area_name || '').toLowerCase().includes(term) ||
        (u.employee_number || '').toLowerCase().includes(term) ||
        (u.position || '').toLowerCase().includes(term) ||
        (u.email || '').toLowerCase().includes(term)
    );
});

const removeUserRow = (index) => {
    scannedUsers.value.splice(index, 1);
};

const handleConfirmImport = () => {
    const usersToImport = scannedUsers.value.filter(u => u.selected);
    if (usersToImport.length === 0) {
        alert('Debes seleccionar al menos un usuario para importar.');
        return;
    }

    isSubmittingImport.value = true;

    router.post(route('users.batchImport'), {
        users: usersToImport,
        default_password: defaultPassword.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmittingImport.value = false;
            emit('close');
            emit('usersImported');
        },
        onError: (err) => {
            isSubmittingImport.value = false;
            console.error('Batch import error:', err);
            alert('Error en la importación: ' + (Object.values(err)[0] || 'Revisa los datos ingresados.'));
        }
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="5xl">
        <div class="p-6 md:p-8 bg-white dark:bg-gray-900 rounded-[2.5rem] relative overflow-hidden transition-all">
            
            <!-- HEADER -->
            <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 dark:border-gray-800">
                <div class="flex items-center gap-4">
                    <div class="p-3.5 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-2xl text-white shadow-lg shadow-indigo-500/20">
                        <SparklesIcon class="h-6 w-6 animate-pulse" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight flex items-center gap-2">
                            Importar Plantilla con IA
                            <span class="bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[9px] font-black px-2.5 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-800 tracking-widest">
                                OCR & DIRECTORIOS WEB
                            </span>
                        </h2>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                            Extrae automáticamente funcionarios, personal y áreas desde archivos o enlaces web oficiales
                        </p>
                    </div>
                </div>

                <button @click="emit('close')" class="p-2.5 rounded-2xl bg-slate-50 dark:bg-gray-800 hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all cursor-pointer">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- PASO 1: SELECCIÓN DE ORIGEN (ARCHIVO O URL) -->
            <div v-if="step === 'upload'" class="space-y-6">
                
                <!-- ERROR ALERT -->
                <div v-if="scanError" class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl flex items-start gap-3 text-rose-700 dark:text-rose-300">
                    <ExclamationCircleIcon class="h-5 w-5 shrink-0 mt-0.5" />
                    <div class="text-xs font-bold">{{ scanError }}</div>
                </div>

                <!-- TABS DE MODO DE IMPORTACIÓN -->
                <div class="flex items-center gap-3 bg-slate-100 dark:bg-gray-800/60 p-1.5 rounded-2xl border border-slate-200 dark:border-gray-700 max-w-md mx-auto">
                    <button type="button" @click="importMode = 'file'"
                            class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 cursor-pointer"
                            :class="importMode === 'file' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-md scale-[1.02]' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                        <DocumentArrowUpIcon class="h-4 w-4" />
                        <span>Subir Archivo</span>
                    </button>
                    <button type="button" @click="importMode = 'url'"
                            class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 cursor-pointer"
                            :class="importMode === 'url' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-md scale-[1.02]' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                        <GlobeAltIcon class="h-4 w-4" />
                        <span>Enlace Web / URL</span>
                    </button>
                </div>

                <!-- MODO A: SUBIR ARCHIVO -->
                <div v-if="importMode === 'file'" class="space-y-6">
                    <div @click="selectFile" 
                         class="border-3 border-dashed rounded-[2.5rem] p-10 text-center cursor-pointer transition-all flex flex-col items-center justify-center gap-4 group"
                         :class="currentFile ? 'border-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/10' : 'border-slate-200 dark:border-gray-700 hover:border-indigo-400 hover:bg-slate-50/60 dark:hover:bg-gray-800/30'">
                        
                        <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*,.pdf,.csv,.xlsx,.xls,.doc,.docx,.txt" class="hidden" />

                        <!-- PREVIEW OR EMPTY -->
                        <template v-if="!currentFile">
                            <div class="p-5 rounded-3xl bg-slate-100 dark:bg-gray-800 group-hover:scale-110 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-950/40 transition-all text-slate-400 group-hover:text-indigo-600">
                                <DocumentArrowUpIcon class="h-12 w-12 stroke-1" />
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-700 dark:text-gray-200 uppercase tracking-wide">
                                    Haz clic aquí o arrastra tu archivo
                                </p>
                                <p class="text-xs text-slate-400 font-semibold mt-1">
                                    Formatos admitidos: Organigramas en imagen (JPG, PNG, WEBP), Documentos (PDF, Excel, Word, CSV, TXT)
                                </p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2">
                                    Máximo 15 MB por archivo
                                </p>
                            </div>
                        </template>

                        <!-- SELECTED FILE PREVIEW -->
                        <template v-else>
                            <div v-if="previewImage" class="relative max-h-48 rounded-2xl overflow-hidden shadow-md border-2 border-indigo-200 dark:border-indigo-900">
                                <img :src="previewImage" class="object-contain max-h-48 max-w-full" />
                            </div>
                            <div v-else class="p-6 rounded-3xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                                <DocumentArrowUpIcon class="h-14 w-14 mx-auto" />
                            </div>

                            <div class="text-center">
                                <p class="text-sm font-black text-slate-800 dark:text-white uppercase truncate max-w-md">
                                    {{ currentFile.name }}
                                </p>
                                <p class="text-xs text-slate-400 font-bold mt-1">
                                    {{ formatFileSize(currentFile.size) }} • {{ currentFile.type || 'Documento' }}
                                </p>
                                <div class="flex items-center justify-center gap-3 mt-3">
                                    <button type="button" @click.stop="selectFile" class="text-[10px] font-black uppercase text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Cambiar archivo
                                    </button>
                                    <span class="text-slate-300">•</span>
                                    <button type="button" @click.stop="removeFile" class="text-[10px] font-black uppercase text-rose-500 hover:underline">
                                        Quitar archivo
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- ACTIONS FILE -->
                    <div class="flex justify-end gap-4 pt-4 border-t border-slate-100 dark:border-gray-800">
                        <button @click="emit('close')" type="button" class="px-6 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button @click="startScan" 
                                :disabled="!currentFile"
                                type="button"
                                class="px-8 py-4 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-[length:200%_auto] text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-indigo-500/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <SparklesIcon class="h-5 w-5" />
                            <span>Analizar Documento con IA</span>
                        </button>
                    </div>
                </div>

                <!-- MODO B: IMPORTAR DESDE ENLACE WEB (URL) -->
                <div v-else-if="importMode === 'url'" class="space-y-6">
                    <div class="bg-slate-50 dark:bg-gray-800/50 p-8 rounded-[2.5rem] border border-slate-200 dark:border-gray-700 space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-indigo-100 dark:bg-indigo-900/60 rounded-2xl text-indigo-600 dark:text-indigo-400">
                                <GlobeAltIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-tight">
                                    Directorio Institucional en Línea
                                </h3>
                                <p class="text-xs text-slate-400 font-semibold mt-0.5">
                                    Pega el enlace de la página web del directorio oficial para importar titulares y áreas en tiempo real
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 block ml-2">
                                Enlace URL del Directorio:
                            </label>
                            <div class="relative">
                                <LinkIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                <input type="url" v-model="webUrl" placeholder="https://congresonayarit.gob.mx/directorio-de-funcionarios/"
                                       class="w-full pl-12 pr-6 py-4 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm" />
                            </div>
                        </div>

                        <!-- SUGERENCIAS RÁPIDAS -->
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-2">
                                Sugerencia Directa:
                            </p>
                            <button type="button" 
                                    @click="webUrl = 'https://congresonayarit.gob.mx/directorio-de-funcionarios/'"
                                    class="p-3 px-4 rounded-xl bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:border-indigo-400 transition-all flex items-center gap-2 shadow-sm cursor-pointer">
                                <span>🏛️ Directorio Oficial: Congreso del Estado de Nayarit</span>
                            </button>
                        </div>
                    </div>

                    <!-- ACTIONS URL -->
                    <div class="flex justify-end gap-4 pt-4 border-t border-slate-100 dark:border-gray-800">
                        <button @click="emit('close')" type="button" class="px-6 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button @click="startUrlScan" 
                                :disabled="!webUrl"
                                type="button"
                                class="px-8 py-4 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-[length:200%_auto] text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-indigo-500/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <SparklesIcon class="h-5 w-5" />
                            <span>Extraer Directorio Web con IA</span>
                        </button>
                    </div>
                </div>

                <!-- TIPS / INFO CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-gray-800/60 rounded-2xl border border-slate-100 dark:border-gray-800 flex items-start gap-3">
                        <BuildingOfficeIcon class="h-5 w-5 text-indigo-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-[11px] font-black text-slate-700 dark:text-gray-200 uppercase">Detección de Áreas</p>
                            <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">Identifica departamentos y los vincula automáticamente con el catálogo existente o crea nuevas áreas.</p>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-gray-800/60 rounded-2xl border border-slate-100 dark:border-gray-800 flex items-start gap-3">
                        <UsersIcon class="h-5 w-5 text-purple-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-[11px] font-black text-slate-700 dark:text-gray-200 uppercase">Nombres y Apellidos</p>
                            <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">Limpia títulos académicos y desglosa nombres de pila, apellido paterno y materno.</p>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-gray-800/60 rounded-2xl border border-slate-100 dark:border-gray-800 flex items-start gap-3">
                        <SparklesIcon class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-[11px] font-black text-slate-700 dark:text-gray-200 uppercase">Revisión Previa</p>
                            <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">Podrás editar, filtrar y revisar toda la lista antes de guardar cualquier cambio.</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- PASO 2: ESCANEANDO CON IA -->
            <div v-else-if="step === 'scanning'" class="py-16 text-center space-y-8">
                <div class="relative w-24 h-24 mx-auto">
                    <div class="absolute inset-0 rounded-full border-4 border-indigo-100 dark:border-indigo-950"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <SparklesIcon class="h-10 w-10 text-indigo-600 animate-pulse" />
                    </div>
                </div>

                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">
                        Procesando Directorio con IA
                    </h3>
                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mt-2 animate-pulse">
                        {{ currentScanMessage }}
                    </p>
                </div>

                <!-- PROGRESS BAR -->
                <div class="max-w-md mx-auto">
                    <div class="h-3 w-full bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden p-0.5 border border-slate-200 dark:border-gray-700">
                        <div class="h-full bg-gradient-to-r from-indigo-600 to-purple-600 rounded-full transition-all duration-500" :style="{ width: `${scanProgress}%` }"></div>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-3">
                        {{ scanProgress }}% completado
                    </p>
                </div>
            </div>

            <!-- PASO 3: REVISIÓN DE RESULTADOS Y CONFIRMACIÓN -->
            <div v-else-if="step === 'review'" class="space-y-6">
                
                <!-- SUMMARY STATS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-5 bg-indigo-50 dark:bg-indigo-950/30 rounded-3xl border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-400">Total Detectados</p>
                            <p class="text-2xl font-black text-indigo-700 dark:text-indigo-300 mt-1">{{ scannedUsers.length }}</p>
                        </div>
                        <UsersIcon class="h-8 w-8 text-indigo-500 opacity-60" />
                    </div>
                    
                    <div class="p-5 bg-purple-50 dark:bg-purple-950/30 rounded-3xl border border-purple-100 dark:border-purple-900/50 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-purple-400">Áreas Identificadas</p>
                            <p class="text-2xl font-black text-purple-700 dark:text-purple-300 mt-1">{{ Object.keys(uniqueDetectedAreas).length }}</p>
                        </div>
                        <BuildingOfficeIcon class="h-8 w-8 text-purple-500 opacity-60" />
                    </div>

                    <div class="p-5 bg-emerald-50 dark:bg-emerald-950/30 rounded-3xl border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500">Listos a Importar</p>
                            <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ selectedCount }} / {{ scannedUsers.length }}</p>
                        </div>
                        <CheckBadgeIcon class="h-8 w-8 text-emerald-500 opacity-60" />
                    </div>
                </div>

                <!-- CONTROLS & FILTER BAR -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50 dark:bg-gray-800/40 p-4 rounded-2xl border border-slate-100 dark:border-gray-800">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-black uppercase text-slate-700 dark:text-gray-200">
                            <Checkbox v-model:checked="allSelected" />
                            <span>Seleccionar Todos ({{ scannedUsers.length }})</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="relative flex-1 md:w-64">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input type="text" v-model="searchFilter" placeholder="Filtrar detectados..." 
                                   class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl text-xs font-semibold focus:ring-indigo-500" />
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <KeyIcon class="h-4 w-4 text-slate-400" />
                            <input type="text" v-model="defaultPassword" placeholder="Contraseña default" title="Contraseña por defecto para usuarios importados"
                                   class="w-36 py-2 px-3 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl text-xs font-bold text-slate-700 dark:text-gray-200 focus:ring-indigo-500" />
                        </div>
                    </div>
                </div>

                <!-- USERS TABLE LIST -->
                <div class="border border-slate-200 dark:border-gray-800 rounded-3xl overflow-hidden max-h-[380px] overflow-y-auto custom-scrollbar shadow-inner bg-white dark:bg-gray-900">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 dark:bg-gray-800 sticky top-0 z-10 border-b border-slate-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3.5 text-center w-12">
                                    <span class="sr-only">Seleccionar</span>
                                </th>
                                <th class="p-3.5 font-black uppercase text-slate-400 tracking-wider">Nombre y Apellidos</th>
                                <th class="p-3.5 font-black uppercase text-slate-400 tracking-wider">Área Asignada</th>
                                <th class="p-3.5 font-black uppercase text-slate-400 tracking-wider">Puesto / Cargo</th>
                                <th class="p-3.5 font-black uppercase text-slate-400 tracking-wider">Rol</th>
                                <th class="p-3.5 font-black uppercase text-slate-400 tracking-wider">Correo</th>
                                <th class="p-3.5 text-center w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            <tr v-for="(u, idx) in filteredScannedUsers" :key="u.id" 
                                class="hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition-all"
                                :class="{ 'opacity-50 bg-slate-50/50': !u.selected }">
                                
                                <td class="p-3.5 text-center">
                                    <Checkbox v-model:checked="u.selected" />
                                </td>

                                <td class="p-3.5">
                                    <div class="space-y-1">
                                        <div class="flex gap-2">
                                            <input type="text" v-model="u.first_name" placeholder="Nombre(s)" 
                                                   class="w-32 px-2.5 py-1 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-xs font-bold" />
                                            <input type="text" v-model="u.last_name" placeholder="Ap. Paterno" 
                                                   class="w-28 px-2.5 py-1 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-xs font-bold" />
                                            <input type="text" v-model="u.second_last_name" placeholder="Ap. Materno" 
                                                   class="w-28 px-2.5 py-1 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-xs font-bold" />
                                        </div>
                                    </div>
                                </td>

                                <td class="p-3.5">
                                    <div class="space-y-1">
                                        <select v-model="u.area_id" class="w-48 px-2.5 py-1 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-xs font-bold">
                                            <option :value="null">➕ Crear: "{{ u.area_name }}"</option>
                                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                                        </select>
                                        <p v-if="!u.area_id" class="text-[9px] text-amber-600 dark:text-amber-400 font-bold uppercase">
                                            Se creará nueva área
                                        </p>
                                    </div>
                                </td>

                                <td class="p-3.5">
                                    <input type="text" v-model="u.position" placeholder="Cargo oficial" 
                                           class="w-40 px-2.5 py-1 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-xs font-medium" />
                                </td>

                                <td class="p-3.5">
                                    <select v-model="u.role" class="w-32 px-2.5 py-1 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-xs font-bold">
                                        <option value="area_manager">Gerente de Área</option>
                                        <option value="diner">Comensal</option>
                                    </select>
                                </td>

                                <td class="p-3.5">
                                    <input type="text" v-model="u.email" placeholder="correo@ejemplo.com" 
                                           class="w-44 px-2.5 py-1 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-xs font-medium" />
                                </td>

                                <td class="p-3.5 text-center">
                                    <button @click="removeUserRow(idx)" type="button" class="text-slate-300 hover:text-rose-500 transition-colors p-1 cursor-pointer" title="Quitar de la lista">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="filteredScannedUsers.length === 0">
                                <td colspan="7" class="p-8 text-center text-slate-400 uppercase font-bold text-xs">
                                    No hay usuarios que coincidan con la búsqueda
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ACTIONS -->
                <div class="flex justify-between items-center pt-4 border-t border-slate-100 dark:border-gray-800">
                    <button @click="step = 'upload'" type="button" class="px-6 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all flex items-center gap-2 cursor-pointer">
                        <ArrowPathIcon class="h-4 w-4" />
                        <span>Volver a Escanear</span>
                    </button>

                    <div class="flex items-center gap-4">
                        <button @click="emit('close')" type="button" class="px-6 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button @click="handleConfirmImport" 
                                :disabled="selectedCount === 0 || isSubmittingImport"
                                type="button"
                                class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-emerald-600/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <UserPlusIcon class="h-5 w-5" :class="{ 'animate-spin': isSubmittingImport }" />
                            <span>Confirmar e Importar {{ selectedCount }} Usuarios</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </Modal>
</template>
