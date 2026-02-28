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

        const response = await axios.post(route('daily-menus.scan', props.provider.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        // Add "is_duplicate" and "selected" flags to each item
        scannedMenuItems.value = response.data.menu_items.map(item => {
            const duplicate = existingMenuItems.value.find(ex => 
                ex.name.toLowerCase().trim() === item.name.toLowerCase().trim()
            );
            return {
                ...item,
                is_duplicate: !!duplicate,
                selected: !duplicate, // Default select only non-duplicates
                editing: false
            };
        });

        scanProgress.value = 100;
        setTimeout(() => {
            step.value = 'review';
            if (progressInterval.value) clearInterval(progressInterval.value);
        }, 500);

    } catch (error) {
        console.error('Error scanning menu:', error);
        scanError.value = error.response?.data?.error || 'Error al analizar el menú. Intenta con una imagen más clara.';
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
        <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
            <div>
                <h2 class="text-xl font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight">
                    {{ step === 'upload' ? 'Subir Menú' : (step === 'scanning' ? 'Analizando...' : 'Revisar Resultados') }}
                </h2>
                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">{{ provider.name }}</p>
            </div>
            
            <div class="flex gap-2">
                <SecondaryButton @click="emit('close')" class="!rounded-xl border-none">
                    Cancelar
                </SecondaryButton>
                
                <PrimaryButton v-if="step === 'upload'" @click="scanMenu" :disabled="!form.file" :class="{ 'opacity-25': !form.file }"
                               class="!rounded-xl bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-100 dark:shadow-none">
                    <SparklesIcon class="h-4 w-4 mr-2" /> Iniciar Escaneo
                </PrimaryButton>

                <PrimaryButton v-if="step === 'review'" @click="saveFinalMenu"
                               class="!rounded-xl bg-green-600 hover:bg-green-700 shadow-lg shadow-green-100 dark:shadow-none uppercase font-black">
                    <CheckCircleIcon class="h-4 w-4 mr-2" /> Guardar ({{ selectedCount }})
                </PrimaryButton>
            </div>
        </div>

        <div class="p-8">
            <!-- PASO 1: SUBIDA -->
            <div v-if="step === 'upload'" class="space-y-6">
                <div v-if="scanError" class="p-4 bg-red-50 border-2 border-red-100 text-red-600 rounded-2xl flex items-start gap-3">
                    <ExclamationCircleIcon class="h-5 w-5 shrink-0" />
                    <p class="text-xs font-bold">{{ scanError }}</p>
                </div>

                <div class="relative group">
                    <input type="file" ref="fileInput" @change="handleFileChange" class="hidden" accept="image/*,application/pdf" />
                    
                    <div v-if="!currentFile" 
                         @click="selectFile"
                         class="border-4 border-dashed border-gray-200 dark:border-gray-700 rounded-[2.5rem] p-12 text-center hover:border-indigo-400 hover:bg-indigo-50/30 transition-all cursor-pointer">
                        <DocumentArrowUpIcon class="h-16 w-16 text-gray-300 mx-auto mb-4 group-hover:text-indigo-400 transition-colors" />
                        <p class="text-gray-500 dark:text-gray-400 font-black uppercase tracking-widest text-sm">Selecciona o arrastra tu menú</p>
                        <p class="text-[10px] text-gray-400 mt-2 font-bold uppercase">PNG, JPG o PDF hasta 10MB</p>
                    </div>
                    
                    <div v-else class="bg-gray-100 dark:bg-gray-900 rounded-[2.5rem] p-6 flex items-center justify-between border-2 border-indigo-100 dark:border-indigo-900/30">
                        <div class="flex items-center">
                            <div class="h-12 w-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white mr-4 shadow-lg shadow-indigo-100 dark:shadow-none">
                                <PhotoIcon v-if="currentFile.type.startsWith('image')" class="h-6 w-6" />
                                <DocumentArrowUpIcon v-else class="h-6 w-6" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-gray-800 dark:text-white truncate uppercase">{{ currentFile.name }}</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ (currentFile.size / 1024 / 1024).toFixed(2) }} MB</p>
                            </div>
                        </div>
                        <button @click="removeFile" class="p-2 bg-white dark:bg-gray-800 rounded-xl text-red-500 shadow-sm hover:bg-red-50 transition-all">
                            <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div v-if="previewImage" class="mt-6">
                    <p class="text-[10px] font-black uppercase text-gray-400 mb-2 tracking-[0.2em]">Vista previa del archivo:</p>
                    <div class="rounded-3xl overflow-hidden border-4 border-white dark:border-gray-800 shadow-2xl">
                        <img :src="previewImage" alt="Preview" class="w-full max-h-96 object-contain bg-gray-50 dark:bg-gray-900" />
                    </div>
                </div>
            </div>

            <!-- PASO 2: ESCANEANDO (ANIMACIÓN) -->
            <div v-if="step === 'scanning'" class="py-20 flex flex-col items-center justify-center space-y-10">
                <div class="relative h-40 w-40 flex items-center justify-center">
                    <!-- Animación circular -->
                    <div class="absolute inset-0 border-8 border-indigo-100 dark:border-indigo-900/30 rounded-full"></div>
                    <div class="absolute inset-0 border-8 border-indigo-600 rounded-full border-t-transparent animate-spin"></div>
                    <SparklesIcon class="h-16 w-16 text-indigo-600 animate-pulse" />
                </div>
                
                <div class="text-center space-y-4 max-w-sm">
                    <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Procesando con Inteligencia Artificial</h3>
                    <p class="text-sm text-gray-500 font-medium italic">Estamos extrayendo platillos, descripciones y precios automáticamente de tu imagen...</p>
                </div>

                <div class="w-full max-w-md bg-gray-100 dark:bg-gray-800 h-4 rounded-full overflow-hidden border dark:border-gray-700 shadow-inner">
                    <div class="h-full bg-indigo-600 transition-all duration-500 shadow-lg shadow-indigo-200" :style="{ width: `${scanProgress}%` }"></div>
                </div>
                <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.3em]">{{ Math.round(scanProgress) }}% completado</p>
            </div>

            <!-- PASO 3: REVISIÓN -->
            <div v-if="step === 'review'" class="space-y-6">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-sm text-gray-500 font-bold">Hemos encontrado <span class="text-indigo-600">{{ scannedMenuItems.length }}</span> platillos. Revisa y edita antes de guardar:</p>
                </div>

                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <div v-for="(item, index) in scannedMenuItems" :key="index" 
                         class="p-5 rounded-3xl border-2 transition-all relative overflow-hidden group"
                         :class="[
                            item.selected ? 'bg-white dark:bg-gray-800 shadow-lg' : 'bg-gray-50 dark:bg-gray-900/50 opacity-60 grayscale scale-[0.98]',
                            item.is_duplicate ? 'border-orange-500 dark:border-orange-600' : (item.selected ? 'border-indigo-100 dark:border-indigo-900/50' : 'border-transparent')
                         ]">
                        
                        <!-- Etiqueta de Duplicado (Más llamativa) -->
                        <div v-if="item.is_duplicate" class="absolute top-0 right-0 px-6 py-1.5 bg-orange-600 text-white text-[9px] font-black uppercase tracking-[0.2em] rounded-bl-2xl shadow-md z-10 flex items-center">
                            <ExclamationCircleIcon class="h-3 w-3 mr-1" /> Ya Existe en Menú
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="pt-1">
                                <Checkbox :checked="item.selected" @change="toggleItemSelection(index)" class="h-6 w-6 !rounded-lg" />
                            </div>
                            
                            <div class="flex-1 space-y-4">
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="relative">
                                        <InputLabel value="Nombre del Platillo" class="text-[9px] font-black uppercase text-gray-400 mb-1" />
                                        <input v-model="item.name" type="text" 
                                               class="w-full bg-transparent border-0 border-b-2 border-gray-100 dark:border-gray-700 focus:border-indigo-500 focus:ring-0 font-black text-lg p-0 dark:text-white" />
                                    </div>
                                    <div>
                                        <InputLabel value="Descripción / Guarniciones" class="text-[9px] font-black uppercase text-gray-400 mb-1" />
                                        <textarea v-model="item.description" rows="2" 
                                                  class="w-full bg-transparent border-0 border-b border-gray-100 dark:border-gray-700 focus:border-indigo-500 focus:ring-0 text-xs p-0 dark:text-gray-300 italic"></textarea>
                                    </div>
                                </div>
                            </div>

                            <button @click="removeItem(index)" class="p-2 text-gray-300 hover:text-red-500 self-start transition-colors">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="scannedMenuItems.length === 0" class="text-center py-12">
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No se detectaron platillos válidos</p>
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
    background: rgba(99, 102, 241, 0.2);
    border-radius: 10px;
}
</style>
