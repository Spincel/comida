<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { 
    PhotoIcon, 
    SwatchIcon,
    CloudArrowUpIcon,
    CheckCircleIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Array,
});

const previews = ref({
    logo_main: props.settings.find(s => s.key === 'logo_main')?.value,
    logo_small: props.settings.find(s => s.key === 'logo_small')?.value,
    logo_report: props.settings.find(s => s.key === 'logo_report')?.value,
});

const savingKey = ref(null);
const lastSavedKey = ref(null);

const saveSetting = (key, value, isFile = false) => {
    savingKey.value = key;
    lastSavedKey.value = null;

    const formData = new FormData();
    if (isFile) {
        formData.append(key, value);
    } else {
        formData.append(key, value);
    }

    router.post(route('admin.settings.interface.update'), formData, {
        preserveScroll: true,
        forceFormData: isFile,
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
</script>

<template>
    <Head title="Catálogo de Interfaz" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Catálogo de Interfaz
                    </h2>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Branding dinámico con auto-guardado</p>
                </div>
                <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-2xl border border-green-100 dark:border-green-800">
                    <CheckCircleIcon class="h-5 w-5 text-green-500" />
                    <span class="text-[10px] font-black text-green-700 dark:text-green-400 uppercase tracking-widest">Cambios se guardan al instante</span>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- SECCIÓN LOGOS -->
                <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-10 border-b dark:border-gray-700 pb-6">
                        <PhotoIcon class="h-8 w-8 text-indigo-600" />
                        <div>
                            <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Identidad Visual (Logos)</h3>
                            <p class="text-xs text-gray-400 font-bold uppercase">Sube archivos y se actualizarán en todo el sistema</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                        <div v-for="key in ['logo_main', 'logo_small', 'logo_report']" :key="key" class="space-y-4 relative">
                            <InputLabel :value="key.replace('_', ' ').toUpperCase()" class="text-[10px] font-black uppercase text-gray-400 text-center" />
                            
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
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN COLORES Y TEXTOS -->
                <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-10 border-b dark:border-gray-700 pb-6">
                        <SwatchIcon class="h-8 w-8 text-indigo-600" />
                        <div>
                            <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Ajustes del Sistema</h3>
                            <p class="text-xs text-gray-400 font-bold uppercase">Nombre del sitio y colores de marca</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <!-- APP NAME -->
                        <div class="md:col-span-2 space-y-2">
                            <div class="flex justify-between items-center">
                                <InputLabel value="Nombre de la Aplicación" class="text-[10px] font-black uppercase text-gray-400" />
                                <div v-if="savingKey === 'app_name'" class="flex items-center gap-1 text-indigo-500 animate-pulse">
                                    <ArrowPathIcon class="h-3 w-3 animate-spin" />
                                    <span class="text-[8px] font-black uppercase">Guardando...</span>
                                </div>
                                <div v-else-if="lastSavedKey === 'app_name'" class="flex items-center gap-1 text-green-500">
                                    <CheckCircleIcon class="h-3 w-3" />
                                    <span class="text-[8px] font-black uppercase">Guardado</span>
                                </div>
                            </div>
                            <TextInput type="text" v-model="values.app_name" @input="debouncedSave('app_name')" class="w-full !rounded-2xl" placeholder="Ej. Comedor UTICS" />
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

                <!-- ALERTAS GLOBALES -->
                <div class="p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center gap-4">
                    <CloudArrowUpIcon class="h-6 w-6 text-indigo-600 shrink-0" />
                    <p class="text-[10px] text-indigo-700 dark:text-indigo-400 font-black uppercase leading-relaxed">
                        Cualquier cambio realizado aquí se reflejará inmediatamente para todos los usuarios al recargar el sitio o navegar a otra sección.
                    </p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
