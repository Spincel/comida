<script setup>
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import { 
    XMarkIcon, SunIcon, MoonIcon, ComputerDesktopIcon, 
    CheckCircleIcon, PhotoIcon, PaintBrushIcon 
} from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    currentSettings: Object,
    catalog: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    background_url: props.currentSettings?.background_url || '',
    theme_mode: props.currentSettings?.theme_mode || 'system',
});

watch(() => props.currentSettings, (newSettings) => {
    if (newSettings) {
        form.background_url = newSettings.background_url || '';
        form.theme_mode = newSettings.theme_mode || 'system';
    }
}, { deep: true });

const selectBackground = (url) => {
    form.background_url = url;
    submit();
};

const selectTheme = (mode) => {
    form.theme_mode = mode;
    submit();
};

const submit = () => {
    form.post(route('settings.theme.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Theme application logic is handled in AuthenticatedLayout via shared props
        },
    });
};

const themes = [
    { id: 'light', name: 'Claro', icon: SunIcon },
    { id: 'dark', name: 'Oscuro', icon: MoonIcon },
    { id: 'system', name: 'Sistema', icon: ComputerDesktopIcon },
];
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="2xl">
        <div class="p-8 bg-white dark:bg-gray-900 rounded-[2.5rem] overflow-hidden">
            <div class="flex justify-between items-center mb-10">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-xl">
                        <PaintBrushIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter leading-none">Personalizar Interfaz</h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Elige tu atmósfera de trabajo</p>
                    </div>
                </div>
                <button @click="emit('close')" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                    <XMarkIcon class="h-7 w-7" />
                </button>
            </div>

            <div class="space-y-10">
                <!-- Modo de Color -->
                <section>
                    <h4 class="text-[11px] font-black uppercase tracking-[0.3em] text-gray-400 mb-6 flex items-center gap-3">
                        <SunIcon class="h-4 w-4" /> Modo de Color
                    </h4>
                    <div class="grid grid-cols-3 gap-4">
                        <button v-for="theme in themes" :key="theme.id"
                                @click="selectTheme(theme.id)"
                                class="p-6 rounded-[2rem] border-2 transition-all flex flex-col items-center gap-4 group"
                                :class="form.theme_mode === theme.id 
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 shadow-lg scale-[1.02]' 
                                    : 'border-gray-100 dark:border-gray-800 hover:border-indigo-200 text-gray-400'">
                            <component :is="theme.icon" class="h-8 w-8 transition-transform group-hover:scale-110" />
                            <span class="text-[10px] font-black uppercase tracking-widest">{{ theme.name }}</span>
                        </button>
                    </div>
                </section>

                <!-- Catálogo de Fondos -->
                <section>
                    <h4 class="text-[11px] font-black uppercase tracking-[0.3em] text-gray-400 mb-6 flex items-center gap-3">
                        <PhotoIcon class="h-4 w-4" /> Fondo de Pantalla
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        <!-- Opción Ninguno -->
                        <button @click="selectBackground('')"
                                class="aspect-video rounded-3xl border-2 transition-all flex flex-col items-center justify-center gap-2 group overflow-hidden bg-gray-50 dark:bg-gray-950"
                                :class="!form.background_url ? 'border-indigo-500 shadow-lg' : 'border-gray-100 dark:border-gray-800 hover:border-indigo-200'">
                            <XMarkIcon class="h-6 w-6 text-gray-300" />
                            <span class="text-[8px] font-black uppercase tracking-widest text-gray-400">Sin Fondo</span>
                        </button>

                        <div v-for="(bg, idx) in catalog" :key="idx" 
                             @click="selectBackground(bg)"
                             class="aspect-video rounded-3xl border-2 transition-all cursor-pointer relative group overflow-hidden shadow-sm"
                             :class="form.background_url === bg ? 'border-indigo-500 ring-4 ring-indigo-500/20 scale-[1.02]' : 'border-transparent hover:border-indigo-300'">
                            <img :src="bg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                            <div v-if="form.background_url === bg" class="absolute inset-0 bg-indigo-600/20 flex items-center justify-center">
                                <CheckCircleIcon class="h-8 w-8 text-white drop-shadow-lg" />
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="mt-12 pt-8 border-t dark:border-gray-800 flex justify-end">
                <button @click="emit('close')" class="px-10 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-2xl active:scale-95 transition-all">
                    Listo
                </button>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
</style>
