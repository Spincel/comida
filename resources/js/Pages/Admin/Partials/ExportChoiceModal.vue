<script setup>
import Modal from '@/Components/Modal.vue';
import { 
    DocumentIcon, 
    TableCellsIcon, 
    DocumentTextIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    title: String,
});

const emit = defineEmits(['close', 'select']);

const options = [
    { id: 'pdf', name: 'Formato PDF', desc: 'Ideal para imprimir y firmas', icon: DocumentIcon, color: 'text-red-500', bg: 'bg-red-50' },
    { id: 'excel', name: 'Formato Excel (CSV)', desc: 'Para análisis de datos', icon: TableCellsIcon, color: 'text-green-600', bg: 'bg-green-50' },
    { id: 'word', name: 'Formato Word (Doc)', desc: 'Para edición de texto', icon: DocumentTextIcon, color: 'text-blue-600', bg: 'bg-blue-50' },
];

const selectFormat = (format) => {
    emit('select', format);
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="sm">
        <div class="p-8 dark:bg-gray-900 rounded-[2.5rem]">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 flex items-center justify-center text-lg border border-tinto-200 dark:border-tinto-800">
                        📥
                    </div>
                    <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Exportar Reporte</h3>
                </div>
                <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 cursor-pointer">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="space-y-3">
                <button v-for="opt in options" :key="opt.id"
                        @click="selectFormat(opt.id)"
                        class="w-full flex items-center p-4 rounded-2xl border-2 border-slate-100 dark:border-gray-800 hover:border-tinto-700 dark:hover:border-oro-400 hover:bg-tinto-50/20 dark:hover:bg-tinto-950/20 transition-all text-left group cursor-pointer">
                    <div :class="[opt.bg, opt.color]" class="h-12 w-12 rounded-xl flex items-center justify-center mr-4 shrink-0 transition-transform group-hover:scale-110 shadow-2xs">
                        <component :is="opt.icon" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="font-black text-xs text-slate-800 dark:text-white uppercase leading-none mb-1">{{ opt.name }}</p>
                        <p class="text-[10px] text-slate-400 font-bold">{{ opt.desc }}</p>
                    </div>
                </button>
            </div>

            <div class="mt-6">
                <p class="text-[9px] text-center text-slate-400 uppercase font-black tracking-widest leading-relaxed">
                    El reporte se generará con los filtros y acomodos seleccionados actualmente.
                </p>
            </div>
        </div>
    </Modal>
</template>
