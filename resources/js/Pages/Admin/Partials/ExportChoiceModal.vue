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
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight">Exportar Reporte</h3>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="space-y-3">
                <button v-for="opt in options" :key="opt.id"
                        @click="selectFormat(opt.id)"
                        class="w-full flex items-center p-4 rounded-2xl border-2 border-gray-100 dark:border-gray-700 hover:border-indigo-500 transition-all text-left group">
                    <div :class="[opt.bg, opt.color]" class="h-12 w-12 rounded-xl flex items-center justify-center mr-4 shrink-0 transition-transform group-hover:scale-110">
                        <component :is="opt.icon" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="font-black text-sm text-gray-800 dark:text-white uppercase leading-none mb-1">{{ opt.name }}</p>
                        <p class="text-[10px] text-gray-400 font-bold">{{ opt.desc }}</p>
                    </div>
                </button>
            </div>

            <div class="mt-6">
                <p class="text-[9px] text-center text-gray-400 uppercase font-black tracking-widest leading-relaxed">
                    El reporte se generará con los filtros y acomodos seleccionados actualmente.
                </p>
            </div>
        </div>
    </Modal>
</template>
