<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { 
    PhotoIcon, 
    SwatchIcon,
    CloudArrowUpIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    UsersIcon,
    UserGroupIcon,
    PaintBrushIcon,
    PlusIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Array,
});

const backgroundCatalog = computed(() => {
    const setting = props.settings.find(s => s.key === 'background_catalog');
    return JSON.parse(setting?.value || '[]');
});

const previews = ref({
    logo_main: props.settings.find(s => s.key === 'logo_main')?.value,
    logo_small: props.settings.find(s => s.key === 'logo_small')?.value,
    logo_report: props.settings.find(s => s.key === 'logo_report')?.value,
    favicon: props.settings.find(s => s.key === 'favicon')?.value,
});

const savingKey = ref(null);
const lastSavedKey = ref(null);

const uploadBackground = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    savingKey.value = 'background_upload';
    const formData = new FormData();
    formData.append('image', file);

    router.post(route('admin.settings.backgrounds.upload'), formData, {
        preserveScroll: true,
        onSuccess: () => {
            savingKey.value = null;
        },
        onError: () => {
            savingKey.value = null;
        }
    });
};

const deleteBackground = (url) => {
    if (!confirm('¿Eliminar este fondo del catálogo global?')) return;
    
    // We send the current catalog minus the deleted URL
    const newCatalog = backgroundCatalog.value.filter(u => u !== url);
    saveSetting('background_catalog', JSON.stringify(newCatalog));
};

const saveSetting = (key, value, isFile = false) => {
    savingKey.value = key;
    lastSavedKey.value = null;

    const formData = new FormData();
    if (isFile) {
        formData.append(key, value);
    } else {
        formData.append(key, value || '');
    }

    router.post(route('admin.settings.interface.update'), formData, {
        preserveScroll: true,
        forceFormData: true,
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

const handleFile = (key, e) => {
    const file = e.target.files[0];
    if (file) {
        // Preview
        const reader = new FileReader();
        reader.onload = (e) => previews.value[key] = e.target.result;
        reader.readAsDataURL(file);
        
        // Auto-save
        saveSetting(key, file, true);
    }
};

// Values for text/color inputs
const values = ref(props.settings.reduce((acc, s) => {
    acc[s.key] = s.value;
    return acc;
}, {}));

const debouncedSave = (key) => {
    // Basic debounce logic for colors/text
    if (window[`timeout_${key}`]) clearTimeout(window[`timeout_${key}`]);
    window[`timeout_${key}`] = setTimeout(() => {
        saveSetting(key, values.value[key]);
    }, 1000);
};

const activeTab = ref('logos'); // 'logos', 'backgrounds', 'system'
</script>

<template>
    <Head title="Catálogo de Interfaz" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Personalización Maestra
                    </h2>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Branding e Identidad del Sistema</p>
                </div>
                
                <!-- NAVEGACIÓN DE PESTAÑAS -->
                <div class="flex bg-gray-100 dark:bg-gray-800 p-1.5 rounded-[1.5rem] border dark:border-gray-700 shadow-inner">
                    <button @click="activeTab = 'logos'" 
                            :class="activeTab === 'logos' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-md scale-105' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <PhotoIcon class="h-4 w-4" /> Logos
                    </button>
                    <button @click="activeTab = 'backgrounds'" 
                            :class="activeTab === 'backgrounds' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-md scale-105' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <PaintBrushIcon class="h-4 w-4" /> Fondos
                    </button>
                    <button @click="activeTab = 'system'" 
                            :class="activeTab === 'system' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-md scale-105' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <SwatchIcon class="h-4 w-4" /> Sistema
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                
                <transition name="fade" mode="out-in">
                    <div :key="activeTab" class="space-y-8">
                        <!-- SECCIÓN LOGOS -->
                        <div v-if="activeTab === 'logos'" class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                            <div class="flex items-center gap-3 mb-10 border-b border-gray-100 dark:border-gray-700 pb-6">
                                <PhotoIcon class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                                <div>
                                    <h3 class="text-xl font-black text-gray-800 dark:text-gray-100 uppercase tracking-tight">Identidad Visual (Logos)</h3>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase">Sube archivos y se actualizarán en todo el sistema</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                                <div v-for="key in ['logo_main', 'logo_small', 'logo_report', 'favicon']" :key="key" class="space-y-4 relative">
                                    <InputLabel :value="key.replace('_', ' ').toUpperCase()" class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-500 text-center tracking-widest" />
                                    
                                    <div @click="$refs[key + 'Input'][0].click()" 
                                         class="aspect-square rounded-[2rem] border-4 border-dashed flex flex-col items-center justify-center cursor-pointer transition-all overflow-hidden bg-gray-50 dark:bg-gray-900 group relative"
                                         :class="[
                                             savingKey === key ? 'border-indigo-400 animate-pulse' : 'border-gray-100 dark:border-gray-700 hover:border-indigo-400',
                                             lastSavedKey === key ? 'border-green-400 shadow-lg shadow-green-100 dark:shadow-none' : ''
                                         ]">
                                        
                                        <img v-if="previews[key]" :src="previews[key].startsWith('data') || previews[key].startsWith('blob') ? previews[key] : '/storage/' + previews[key]" 
                                             class="max-h-32 object-contain group-hover:scale-110 transition-transform" 
                                             :class="{ 'opacity-50': savingKey === key, 'grayscale': key === 'logo_report' }" />
                                        <CloudArrowUpIcon v-else class="h-10 w-10 text-gray-300" />
                                        
                                        <div v-if="savingKey === key" class="absolute inset-0 flex flex-col items-center justify-center bg-white/60 dark:bg-black/60 backdrop-blur-sm">
                                            <ArrowPathIcon class="h-8 w-8 text-indigo-600 animate-spin mb-2" />
                                            <span class="text-[8px] font-black uppercase text-indigo-600">Subiendo...</span>
                                        </div>

                                        <div v-if="lastSavedKey === key" class="absolute top-2 right-2 bg-green-500 text-white p-1 rounded-full shadow-lg">
                                            <CheckCircleIcon class="h-4 w-4" />
                                        </div>

                                        <input type="file" :ref="key + 'Input'" class="hidden" @change="e => handleFile(key, e)" accept="image/*" />
                                    </div>
                                    
                                    <p v-if="key === 'logo_main'" class="text-[8px] text-gray-400 text-center uppercase font-bold px-4 leading-relaxed">Login y Barra lateral</p>
                                    <p v-if="key === 'logo_small'" class="text-[8px] text-gray-400 text-center uppercase font-bold px-4 leading-relaxed">Versión móvil</p>
                                    <p v-if="key === 'logo_report'" class="text-[8px] text-gray-400 text-center uppercase font-bold px-4 leading-relaxed">Membrete de PDF</p>
                                    <p v-if="key === 'favicon'" class="text-[8px] text-gray-400 text-center uppercase font-bold px-4 leading-relaxed">Icono de pestaña</p>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN CATÁLOGO DE FONDOS -->
                        <div v-if="activeTab === 'backgrounds'" class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-10 border-b border-gray-100 dark:border-gray-700 pb-6">
                                <div class="flex items-center gap-3">
                                    <PaintBrushIcon class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                                    <div>
                                        <h3 class="text-xl font-black text-gray-800 dark:text-gray-100 uppercase tracking-tight">Catálogo Maestro de Fondos</h3>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase">Define qué fondos pueden elegir los usuarios</p>
                                    </div>
                                </div>
                                <button @click="$refs.bgInput.click()" 
                                        :disabled="savingKey === 'background_upload'"
                                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 dark:shadow-none">
                                    <PlusIcon v-if="savingKey !== 'background_upload'" class="h-4 w-4" />
                                    <ArrowPathIcon v-else class="h-4 w-4 animate-spin" />
                                    {{ savingKey === 'background_upload' ? 'Subiendo...' : 'Añadir Fondo' }}
                                </button>
                                <input type="file" ref="bgInput" class="hidden" @change="uploadBackground" accept="image/*" />
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-for="(bg, idx) in backgroundCatalog" :key="idx" 
                                     class="group relative aspect-video rounded-3xl overflow-hidden border-2 border-gray-100 dark:border-gray-700 hover:border-indigo-400 transition-all shadow-sm">
                                    <img :src="bg" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-[1px]">
                                        <button @click="deleteBackground(bg)" class="p-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors shadow-lg">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                                <div v-if="backgroundCatalog.length === 0" class="col-span-full p-12 text-center border-4 border-dashed border-gray-100 dark:border-gray-700 rounded-3xl">
                                    <p class="text-[10px] font-black uppercase text-gray-300 dark:text-gray-600 tracking-[0.3em]">Catálogo Vacío</p>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN COLORES Y TEXTOS -->
                        <div v-if="activeTab === 'system'" class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-10 border-b border-gray-100 dark:border-gray-700 pb-6">
                                <SwatchIcon class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                                <div>
                                    <h3 class="text-xl font-black text-gray-800 dark:text-gray-100 uppercase tracking-tight">Ajustes del Sistema</h3>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase">Nombre del sitio y colores de marca</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <!-- APP NAME -->
                                <div class="md:col-span-2 space-y-2">
                                    <div class="flex justify-between items-center px-2">
                                        <InputLabel value="Nombre de la Aplicación" class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-500" />
                                        <div v-if="savingKey === 'app_name'" class="flex items-center gap-1 text-indigo-500 animate-pulse">
                                            <ArrowPathIcon class="h-3 v-3 animate-spin" />
                                            <span class="text-[8px] font-black uppercase">Guardando...</span>
                                        </div>
                                        <div v-else-if="lastSavedKey === 'app_name'" class="flex items-center gap-1 text-green-500">
                                            <CheckCircleIcon class="h-3 v-3" />
                                            <span class="text-[8px] font-black uppercase">Guardado</span>
                                        </div>
                                    </div>
                                                                    <TextInput type="text" v-model="values.app_name" @input="debouncedSave('app_name')" class="w-full !rounded-2xl dark:bg-gray-900 dark:border-gray-700 dark:text-white" placeholder="Ej. Comedor UTICS" />
                                                            </div>
                                    
                                                            <!-- FOOTER SETTINGS -->
                                                            <div class="md:col-span-2 space-y-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                                                                <InputLabel value="Configuración del Pie de Página (Footer)" class="text-[10px] font-black uppercase text-indigo-500 mb-4" />
                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                                    <div class="space-y-2">
                                                                        <InputLabel value="Título Principal" class="text-[9px] font-black uppercase text-gray-400" />
                                                                        <TextInput type="text" v-model="values.footer_title" @input="debouncedSave('footer_title')" class="w-full !rounded-xl" />
                                                                    </div>
                                                                    <div class="space-y-2">
                                                                        <InputLabel value="Subtítulo / Slogan" class="text-[9px] font-black uppercase text-gray-400" />
                                                                        <TextInput type="text" v-model="values.footer_subtitle" @input="debouncedSave('footer_subtitle')" class="w-full !rounded-xl" />
                                                                    </div>
                                                                    <div class="space-y-2">
                                                                        <InputLabel value="Leyenda de Marca" class="text-[9px] font-black uppercase text-gray-400" />
                                                                        <TextInput type="text" v-model="values.footer_brand" @input="debouncedSave('footer_brand')" class="w-full !rounded-xl" />
                                                                    </div>
                                                                    <div class="space-y-2">
                                                                        <InputLabel value="Año" class="text-[9px] font-black uppercase text-gray-400" />
                                                                        <TextInput type="text" v-model="values.footer_year" @input="debouncedSave('footer_year')" class="w-full !rounded-xl" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                    
                                                            <!-- OPERATION MODE -->                                <div class="md:col-span-2 space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                                    <div class="flex justify-between items-center px-2">
                                        <InputLabel value="Modo de Operación del Sistema" class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-500" />
                                        <div v-if="savingKey === 'operation_mode'" class="flex items-center gap-1 text-indigo-500 animate-pulse">
                                            <ArrowPathIcon class="h-3 v-3 animate-spin" />
                                        </div>
                                        <div v-else-if="lastSavedKey === 'operation_mode'" class="flex items-center gap-1 text-green-500">
                                            <CheckCircleIcon class="h-3 v-3" />
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div @click="values.operation_mode = 'complete'; saveSetting('operation_mode', 'complete')" 
                                             class="p-6 rounded-[2.5rem] border-2 cursor-pointer transition-all flex items-center gap-6"
                                             :class="values.operation_mode === 'complete' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/40' : 'border-gray-100 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                            <div class="h-12 w-12 rounded-2xl flex items-center justify-center" :class="values.operation_mode === 'complete' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500'">
                                                <UsersIcon class="h-6 v-6" />
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-black uppercase tracking-tight" :class="values.operation_mode === 'complete' ? 'text-indigo-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">Modo Completo</p>
                                                <p class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Comensales eligen su propio platillo.</p>
                                            </div>
                                            <div v-if="values.operation_mode === 'complete'" class="h-6 v-6 bg-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-indigo-200">
                                                <CheckCircleIcon class="h-4 v-4 text-white" />
                                            </div>
                                        </div>

                                        <div @click="values.operation_mode = 'simple'; saveSetting('operation_mode', 'simple')" 
                                             class="p-6 rounded-[2.5rem] border-2 cursor-pointer transition-all flex items-center gap-6"
                                             :class="values.operation_mode === 'simple' ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/40' : 'border-gray-100 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                            <div class="h-12 w-12 rounded-2xl flex items-center justify-center" :class="values.operation_mode === 'simple' ? 'bg-rose-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500'">
                                                <UserGroupIcon class="h-6 v-6" />
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-black uppercase tracking-tight" :class="values.operation_mode === 'simple' ? 'text-rose-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">Modo Simple</p>
                                                <p class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Gerente elige por todo el equipo.</p>
                                            </div>
                                            <div v-if="values.operation_mode === 'simple'" class="h-6 v-6 bg-rose-600 rounded-full flex items-center justify-center shadow-lg shadow-rose-200">
                                                <CheckCircleIcon class="h-4 v-4 text-white" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- COLORS -->
                                <div v-for="key in ['color_primary_light', 'color_primary_dark']" :key="key" class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <InputLabel :value="key.replace('color_primary_', 'COLOR ').replace('_', ' ').toUpperCase()" class="text-[10px] font-black uppercase text-gray-400" />
                                        <div v-if="savingKey === key" class="flex items-center gap-1 text-indigo-500 animate-pulse">
                                            <ArrowPathIcon class="h-3 w-3 animate-spin" />
                                        </div>
                                        <div v-else-if="lastSavedKey === key" class="flex items-center gap-1 text-green-500">
                                            <CheckCircleIcon class="h-3 w-3" />
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <input type="color" v-model="values[key]" @input="debouncedSave(key)" class="h-14 w-24 rounded-xl border-0 cursor-pointer" />
                                        <TextInput type="text" v-model="values[key]" @input="debouncedSave(key)" class="flex-1 !rounded-xl font-mono text-sm uppercase" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- ALERTAS GLOBALES -->
                <div class="p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center gap-4 mt-8">
                    <CloudArrowUpIcon class="h-6 w-6 text-indigo-600 shrink-0" />
                    <p class="text-[10px] text-indigo-700 dark:text-indigo-400 font-black uppercase leading-relaxed">
                        Cualquier cambio realizado aquí se reflejará inmediatamente para todos los usuarios al recargar el sitio o navegar a otra sección.
                    </p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.fade-enter-from { opacity: 0; transform: translateY(10px); }
.fade-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
