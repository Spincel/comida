<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { 
    PhotoIcon, SwatchIcon, CloudArrowUpIcon, CheckCircleIcon, ArrowPathIcon,
    UsersIcon, UserGroupIcon, PaintBrushIcon, PlusIcon, TrashIcon
} from '@heroicons/vue/24/outline';

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

const activeTab = ref('logos'); // 'logos', 'system'
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
                <div class="flex bg-slate-100 dark:bg-gray-800 p-1.5 rounded-2xl border border-slate-200 dark:border-gray-700">
                    <button @click="activeTab = 'logos'" :class="activeTab === 'logos' ? 'bg-gradient-to-r from-tinto-900 to-tinto-800 text-oro-300 shadow-tinto-sm border border-oro-400/40' : 'text-slate-400 hover:text-slate-600'" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">Logotipos</button>
                    <button @click="activeTab = 'system'" :class="activeTab === 'system' ? 'bg-gradient-to-r from-tinto-900 to-tinto-800 text-oro-300 shadow-tinto-sm border border-oro-400/40' : 'text-slate-400 hover:text-slate-600'" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">Sistema</button>
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
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
