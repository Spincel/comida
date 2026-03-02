<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SwipeButton from '@/Components/SwipeButton.vue';
import { 
    CloudArrowUpIcon, 
    TrashIcon, 
    TableCellsIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    ArrowDownTrayIcon,
    UsersIcon,
    BuildingOfficeIcon,
    BuildingStorefrontIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object,
});

const importForm = useForm({
    type: 'users',
    file: null,
});

const truncateForm = useForm({
    type: '',
});

const showTruncateModal = ref(false);
const truncateType = ref('');
const truncateLabel = ref('');

const handleFile = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    if (!importForm.file) return alert('Selecciona un archivo primero.');
    importForm.post(route('admin.utilities.import'), {
        preserveScroll: true,
        onSuccess: () => importForm.reset(),
    });
};

const openTruncateConfirm = (type) => {
    const labels = { 
        users: 'Usuarios', 
        areas: 'Áreas', 
        providers: 'Proveedores', 
        sessions: 'SESIONES Y PEDIDOS',
        all: 'TODA LA BASE DE DATOS' 
    };
    truncateType.value = type;
    truncateLabel.value = labels[type];
    showTruncateModal.value = true;
};

const executeTruncate = () => {
    truncateForm.type = truncateType.value;
    truncateForm.post(route('admin.utilities.truncate'), {
        preserveScroll: true,
        onSuccess: () => {
            showTruncateModal.value = false;
        }
    });
};

const downloadTemplate = (type) => {
    const templates = {
        users: `nombre,apellido_paterno,apellido_materno,email,no_empleado,usuario,rol,area,password
Juan,Perez,Lopez,juan@perez.com,1001,jperez,diner,SISTEMAS,secret123`,
        areas: `nombre
SISTEMAS
CONTABILIDAD
RECURSOS HUMANOS`,
        providers: `nombre,contacto,telefono,email,direccion
EL TAQUERO,JUAN,3312345678,juan@tacos.com,CALLE 123`,
    };
    
    const blob = new Blob([templates[type]], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `plantilla_${type}.csv`;
    a.click();
};
</script>

<template>
    <Head title="Gestión de Datos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                Mantenimiento de Datos
            </h2>
            <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Importación Masiva y Limpieza</p>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-10">
                
                <!-- RESUMEN ACTUAL -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="(val, key) in stats" :key="key" 
                         class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 shadow-xl flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ key === 'users' ? 'Usuarios' : (key === 'areas' ? 'Áreas' : 'Proveedores') }}</p>
                            <p class="text-4xl font-black text-gray-800 dark:text-white leading-none">{{ val }}</p>
                        </div>
                        <div class="h-12 w-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <UsersIcon v-if="key === 'users'" class="h-6 w-6" />
                            <BuildingOfficeIcon v-if="key === 'areas'" class="h-6 w-6" />
                            <BuildingStorefrontIcon v-if="key === 'providers'" class="h-6 w-6" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <!-- IMPORTACIÓN -->
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-8">
                        <div class="flex items-center gap-3 border-b dark:border-gray-700 pb-6">
                            <CloudArrowUpIcon class="h-8 w-8 text-indigo-600" />
                            <div>
                                <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Carga Masiva (Excel/CSV)</h3>
                                <p class="text-xs text-gray-400 font-bold uppercase">Sube múltiples registros a la vez</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitImport" class="space-y-6">
                            <div>
                                <InputLabel value="1. ¿Qué deseas importar?" class="text-[10px] font-black uppercase text-gray-400 mb-2" />
                                <div class="grid grid-cols-3 gap-2">
                                    <button v-for="t in ['users', 'areas', 'providers']" :key="t" type="button"
                                            @click="importForm.type = t"
                                            class="py-3 rounded-xl text-[9px] font-black uppercase transition-all border-2"
                                            :class="importForm.type === t ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 dark:bg-gray-900 text-gray-400 border-transparent hover:border-indigo-200'">
                                        {{ t === 'users' ? 'Usuarios' : (t === 'areas' ? 'Áreas' : 'Provs') }}
                                    </button>
                                </div>
                            </div>

                            <div class="p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-3xl border border-indigo-100 dark:border-indigo-800">
                                <div class="flex justify-between items-center mb-4">
                                    <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Descargar Formato Base:</p>
                                    <button @click="downloadTemplate(importForm.type)" type="button" class="text-[10px] font-black text-indigo-600 hover:underline flex items-center">
                                        <ArrowDownTrayIcon class="h-3 w-3 mr-1" /> .CSV Template
                                    </button>
                                </div>
                                <p class="text-[10px] text-indigo-400 leading-relaxed uppercase font-bold">Asegúrate de respetar el orden de las columnas del archivo para evitar errores.</p>
                            </div>

                            <div class="space-y-2">
                                <InputLabel value="2. Seleccionar Archivo" class="text-[10px] font-black uppercase text-gray-400" />
                                <input type="file" @change="handleFile" accept=".csv" 
                                       class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-indigo-400" />
                            </div>

                            <PrimaryButton class="w-full !rounded-2xl !py-4 justify-center" :class="{ 'opacity-25': importForm.processing }" :disabled="importForm.processing">
                                <CloudArrowUpIcon class="h-5 w-5 mr-2" /> Iniciar Importación
                            </PrimaryButton>
                        </form>
                    </div>

                    <!-- LIMPIEZA -->
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-10 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-8">
                        <div class="flex items-center gap-3 border-b dark:border-gray-700 pb-6">
                            <TrashIcon class="h-8 w-8 text-red-500" />
                            <div>
                                <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Limpiar Tablas</h3>
                                <p class="text-xs text-gray-400 font-bold uppercase">Borrado masivo de información</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-for="t in ['users', 'areas', 'providers', 'sessions']" :key="t" 
                                 class="flex items-center justify-between p-5 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:border-red-200 transition-all">
                                <div>
                                    <p class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase leading-none mb-1">
                                        {{ 
                                            t === 'users' ? 'Catálogo de Usuarios' : 
                                            (t === 'areas' ? 'Catálogo de Áreas' : 
                                            (t === 'providers' ? 'Catálogo de Proveedores' : 'Historial de Sesiones y Pedidos')) 
                                        }}
                                    </p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">Acción destructiva e irreversible</p>
                                </div>
                                <button @click="openTruncateConfirm(t)" class="p-3 bg-white dark:bg-gray-800 rounded-xl text-red-500 shadow-sm opacity-40 group-hover:opacity-100 transition-all hover:bg-red-50">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="pt-6">
                                <button @click="openTruncateConfirm('all')"
                                        class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.3em] shadow-xl shadow-red-100 dark:shadow-none transition-all flex items-center justify-center">
                                    <ExclamationTriangleIcon class="h-5 w-5 mr-3" /> Borrar Todo el Sistema
                                </button>
                            </div>
                        </div>

                        <div class="p-6 bg-red-50 dark:bg-red-900/20 rounded-3xl flex items-start gap-4">
                            <InformationCircleIcon class="h-6 w-6 text-red-500 shrink-0" />
                            <p class="text-[9px] text-red-600 dark:text-red-400 uppercase font-black leading-relaxed">
                                El sistema protegerá automáticamente tu usuario actual y las áreas que tengan personal activo para evitar errores críticos.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL DE CONFIRMACIÓN CON DESLIZADOR -->
        <Modal :show="showTruncateModal" @close="showTruncateModal = false" max-width="md">
            <div class="p-10 text-center">
                <div class="h-20 w-20 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-100 dark:shadow-none">
                    <ExclamationTriangleIcon class="h-10 w-10" />
                </div>
                
                <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight mb-2">Confirmar Limpieza</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-8">
                    Estás a punto de eliminar permanentemente todos los registros de <span class="text-red-600 font-black">{{ truncateLabel }}</span>.
                </p>

                <div class="space-y-6">
                    <SwipeButton 
                        text="Desliza para borrar registros" 
                        activeText="Eliminando..." 
                        colorClass="bg-red-600"
                        @confirm="executeTruncate" 
                    />
                    
                    <button @click="showTruncateModal = false" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-colors">
                        Cancelar y volver
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
