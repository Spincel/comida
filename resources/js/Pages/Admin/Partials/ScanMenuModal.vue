<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useForm } from '@inertiajs/vue3';
import { 
    XMarkIcon, 
    DocumentArrowUpIcon, 
    PhotoIcon, 
    SparklesIcon, 
    CheckCircleIcon, 
    ExclamationCircleIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    provider: Object,
    selectedDate: String,
});

const emit = defineEmits(['close', 'menuScanned']);

// --- States: 'upload', 'scanning', 'review' ---
const step = ref('upload');
const scanProgress = ref(0);
const progressInterval = ref(null);

const fileInput = ref(null);
const previewImage = ref(null);
const currentFile = ref(null);

const form = useForm({
    file: null,
});

const scannedMenuItems = ref([]); 
const existingMenuItems = ref([]);
const scanError = ref(null);

watch(() => props.show, (isVisible) => {
    if (isVisible) {
        resetAll();
        fetchExistingItems();
    }
});

const resetAll = () => {
    step.value = 'upload';
    scanProgress.value = 0;
    if (progressInterval.value) clearInterval(progressInterval.value);
    form.reset();
    fileInput.value = null;
    previewImage.value = null;
    currentFile.value = null;
    scannedMenuItems.value = [];
    scanError.value = null;
};

const fetchExistingItems = async () => {
    try {
        const response = await axios.get(route('daily-menus.existing', props.provider.id), {
            params: { date: props.selectedDate }
        });
        existingMenuItems.value = response.data.items;
    } catch (e) {
        console.error('Error fetching existing items:', e);
    }
};

const selectFile = () => fileInput.value.click();

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        currentFile.value = file;
        if (file.type.startsWith('image')) {
            const reader = new FileReader();
            reader.onload = (e) => previewImage.value = e.target.result;
            reader.readAsDataURL(file);
        } else {
            previewImage.value = null;
        }
    }
};

const removeFile = () => {
    form.file = null;
    currentFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
    previewImage.value = null;
};

const startScanningAnimation = () => {
    step.value = 'scanning';
    scanProgress.value = 0;
    progressInterval.value = setInterval(() => {
        if (scanProgress.value < 90) {
            scanProgress.value += Math.random() * 15;
        }
    }, 600);
};

const scanMenu = async () => {
    if (!form.file) return;
    
    startScanningAnimation();
    scanError.value = null;

    try {
        const formData = new FormData();
        formData.append('file', form.file);

        // Set a manual timeout for axios just in case (60s)
        const response = await axios.post(route('daily-menus.scan', props.provider.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            timeout: 60000 
        });

        // Add "is_duplicate" and "selected" flags to each item
        if (response.data.menu_items) {
            scannedMenuItems.value = response.data.menu_items.map(item => {
                const duplicate = existingMenuItems.value.find(ex => 
                    ex.name.toLowerCase().trim() === item.name.toLowerCase().trim()
                );
                return {
                    ...item,
                    is_duplicate: !!duplicate,
                    selected: !duplicate, 
                    editing: false
                };
            });

            scanProgress.value = 100;
            setTimeout(() => {
                step.value = 'review';
                if (progressInterval.value) clearInterval(progressInterval.value);
            }, 500);
        } else {
            throw new Error('La respuesta de la IA no contiene elementos de menú.');
        }

    } catch (error) {
        console.error('Error scanning menu:', error);
        if (error.code === 'ECONNABORTED') {
            scanError.value = 'El servidor está tardando demasiado en procesar la imagen. Intenta con un archivo más pequeño o una imagen más clara.';
        } else {
            scanError.value = error.response?.data?.error || error.message || 'Error al analizar el menú. Intenta con una imagen más clara.';
        }
        step.value = 'upload';
        if (progressInterval.value) clearInterval(progressInterval.value);
    }
};

const toggleItemSelection = (index) => {
    scannedMenuItems.value[index].selected = !scannedMenuItems.value[index].selected;
};

const removeItem = (index) => {
    scannedMenuItems.value.splice(index, 1);
};

const saveFinalMenu = async () => {
    const itemsToSave = scannedMenuItems.value.filter(i => i.selected);
    
    if (itemsToSave.length === 0) {
        if (confirm('No has seleccionado ningún platillo nuevo para guardar. ¿Deseas cerrar la ventana sin realizar cambios?')) {
            emit('close');
        }
        return;
    }
    
    step.value = 'scanning'; // Reuse scanning state for "saving"
    scanProgress.value = 50;

    try {
        await axios.post(route('daily-menus.batchStore'), {
            items: itemsToSave,
            provider_id: props.provider.id,
            available_on: props.selectedDate,
        });
        
        emit('menuScanned', itemsToSave);
        emit('close');
    } catch (error) {
        console.error('Error saving:', error);
        alert('Error al guardar el menú.');
        step.value = 'review';
    }
};

const selectedCount = computed(() => scannedMenuItems.value.filter(i => i.selected).length);
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="3xl">
        <!-- HEADER FIJO CON ACCIONES -->
        <div class="p-6 border-b border-slate-100 dark:border-gray-800 flex justify-between items-center bg-slate-50/50 dark:bg-gray-800/50 rounded-t-[2.5rem]">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 flex items-center justify-center text-lg border border-tinto-200 dark:border-tinto-800 shadow-2xs">
                    ✨
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-800 dark:text-gray-100 uppercase tracking-tight">
                        {{ step === 'upload' ? 'Escanear Menú con IA' : (step === 'scanning' ? 'Analizando Documento...' : 'Revisar Platillos Detectados') }}
                    </h2>
                    <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">{{ provider.name }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="emit('close')" type="button" class="px-4 py-2 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 text-xs font-black uppercase tracking-widest cursor-pointer">
                    Cancelar
                </button>
                
                <button v-if="step === 'upload'" @click="scanMenu" :disabled="!form.file" 
                        class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white text-[10px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all flex items-center cursor-pointer shine-effect"
                        :class="{ 'opacity-25': !form.file }">
                    <SparklesIcon class="h-4 w-4 mr-2 text-oro-300" /> Iniciar Escaneo
                </button>

                <button v-if="step === 'review'" @click="saveFinalMenu"
                        class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-nayarit-800 to-emerald-700 text-white text-[10px] font-black uppercase tracking-widest shadow-sm hover:scale-105 active:scale-95 transition-all flex items-center cursor-pointer">
                    <CheckCircleIcon class="h-4 w-4 mr-2" /> Guardar Platillos ({{ selectedCount }})
                </button>
            </div>
        </div>

        <div class="p-8 dark:bg-gray-900 rounded-b-[2.5rem]">
            <!-- PASO 1: SUBIDA -->
            <div v-if="step === 'upload'" class="space-y-6">
                <div v-if="scanError" class="p-4 bg-rose-50 border-2 border-rose-100 text-rose-600 rounded-2xl flex items-start gap-3">
                    <ExclamationCircleIcon class="h-5 w-5 shrink-0" />
                    <p class="text-xs font-bold">{{ scanError }}</p>
                </div>

                <div class="relative group">
                    <input type="file" ref="fileInput" @change="handleFileChange" class="hidden" accept="image/*,application/pdf" />
                    
                    <div v-if="!currentFile" 
                         @click="selectFile"
                         class="border-4 border-dashed border-slate-200 dark:border-gray-700 rounded-[2.5rem] p-12 text-center hover:border-tinto-700 hover:bg-tinto-50/20 dark:hover:bg-tinto-950/20 transition-all cursor-pointer">
                        <DocumentArrowUpIcon class="h-16 w-16 text-slate-300 dark:text-gray-600 mx-auto mb-4 group-hover:text-tinto-700 dark:group-hover:text-oro-400 transition-colors" />
                        <p class="text-slate-600 dark:text-gray-300 font-black uppercase tracking-widest text-sm">Selecciona o arrastra tu menú</p>
                        <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase">PNG, JPG o PDF con lista de platillos</p>
                    </div>
                    
                    <div v-else class="bg-slate-50 dark:bg-gray-800 rounded-[2.5rem] p-6 flex items-center justify-between border-2 border-tinto-200 dark:border-tinto-900/40 shadow-xs">
                        <div class="flex items-center">
                            <div class="h-12 w-12 bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 rounded-2xl flex items-center justify-center mr-4 shadow-tinto-sm border border-oro-400/40">
                                <PhotoIcon v-if="currentFile.type.startsWith('image')" class="h-6 w-6" />
                                <DocumentArrowUpIcon v-else class="h-6 w-6" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-800 dark:text-white truncate uppercase">{{ currentFile.name }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ (currentFile.size / 1024 / 1024).toFixed(2) }} MB</p>
                            </div>
                        </div>
                        <button @click="removeFile" class="p-2 bg-white dark:bg-gray-700 rounded-xl text-rose-500 shadow-xs hover:bg-rose-50 cursor-pointer">
                            <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div v-if="previewImage" class="mt-6">
                    <p class="text-[10px] font-black uppercase text-slate-400 mb-2 tracking-widest">Vista previa del archivo cargado:</p>
                    <div class="rounded-3xl overflow-hidden border-4 border-white dark:border-gray-800 shadow-md">
                        <img :src="previewImage" alt="Preview" class="w-full max-h-96 object-contain bg-slate-50 dark:bg-gray-900" />
                    </div>
                </div>
            </div>

            <!-- PASO 2: ESCANEANDO (ANIMACIÓN) -->
            <div v-if="step === 'scanning'" class="py-20 flex flex-col items-center justify-center space-y-10">
                <div class="relative h-40 w-40 flex items-center justify-center">
                    <div class="absolute inset-0 border-8 border-tinto-100 dark:border-tinto-950/40 rounded-full"></div>
                    <div class="absolute inset-0 border-8 border-tinto-800 rounded-full border-t-transparent animate-spin"></div>
                    <SparklesIcon class="h-16 w-16 text-oro-400 animate-pulse" />
                </div>
                
                <div class="text-center space-y-4 max-w-sm">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Procesando Menú con IA</h3>
                    <p class="text-xs text-slate-500 font-medium italic">Extrayendo automáticamente platillos, descripciones e ingredientes del menú oficial...</p>
                </div>

                <div class="w-full max-w-md bg-slate-100 dark:bg-gray-800 h-3 rounded-full overflow-hidden border border-slate-200 dark:border-gray-700 shadow-inner">
                    <div class="h-full bg-gradient-to-r from-tinto-900 to-oro-500 transition-all duration-500 shadow-sm" :style="{ width: `${scanProgress}%` }"></div>
                </div>
                <p class="text-[10px] font-black text-tinto-700 dark:text-oro-400 uppercase tracking-widest">{{ Math.round(scanProgress) }}% completado</p>
            </div>

            <!-- PASO 3: REVISIÓN -->
            <div v-if="step === 'review'" class="space-y-6">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-xs text-slate-500 font-bold">Se encontraron <span class="text-tinto-800 dark:text-oro-300 font-black">{{ scannedMenuItems.length }}</span> opciones de platillos. Modifica o desmarca las que desees:</p>
                </div>

                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <div v-for="(item, index) in scannedMenuItems" :key="index" 
                         class="p-5 rounded-3xl border-2 transition-all relative overflow-hidden group"
                         :class="[
                            item.selected ? 'bg-white dark:bg-gray-800 shadow-sm' : 'bg-slate-50 dark:bg-gray-900/50 opacity-60 grayscale scale-[0.98]',
                            item.is_duplicate ? 'border-amber-500 dark:border-amber-600' : (item.selected ? 'border-tinto-200 dark:border-tinto-900/50' : 'border-transparent')
                         ]">
                        
                        <!-- Etiqueta de Duplicado -->
                        <div v-if="item.is_duplicate" class="absolute top-0 right-0 px-5 py-1 bg-amber-600 text-white text-[9px] font-black uppercase tracking-widest rounded-bl-2xl shadow-xs z-10 flex items-center">
                            <ExclamationCircleIcon class="h-3 w-3 mr-1" /> Ya Existe en Menú
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="pt-1">
                                <Checkbox :checked="item.selected" @change="toggleItemSelection(index)" class="h-6 w-6 !rounded-lg text-tinto-800 focus:ring-tinto-700" />
                            </div>
                            
                            <div class="flex-1 space-y-4">
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="relative">
                                        <InputLabel value="Nombre del Platillo" class="text-[9px] font-black uppercase text-slate-400 mb-1 tracking-widest" />
                                        <input v-model="item.name" type="text" 
                                               class="w-full bg-transparent border-0 border-b-2 border-slate-200 dark:border-gray-700 focus:border-tinto-700 focus:ring-0 font-black text-base p-0 text-slate-800 dark:text-white uppercase" />
                                    </div>
                                    <div>
                                        <InputLabel value="Descripción / Guarniciones" class="text-[9px] font-black uppercase text-slate-400 mb-1 tracking-widest" />
                                        <textarea v-model="item.description" rows="2" 
                                                  class="w-full bg-transparent border-0 border-b border-slate-200 dark:border-gray-700 focus:border-tinto-700 focus:ring-0 text-xs p-0 text-slate-600 dark:text-gray-300 italic"></textarea>
                                    </div>
                                </div>
                            </div>

                            <button @click="removeItem(index)" class="p-2 text-slate-300 hover:text-rose-500 self-start transition-colors cursor-pointer">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="scannedMenuItems.length === 0" class="text-center py-12">
                    <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">No se detectaron platillos válidos en el archivo</p>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(120, 24, 42, 0.2);
    border-radius: 10px;
}
</style>
