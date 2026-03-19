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
    CloudArrowDownIcon,
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
    providers: { type: Array, default: () => [] }
});

const importForm = useForm({
    type: 'users',
    file: null,
});

const truncateForm = useForm({
    type: '',
    provider_id: null,
});

const showTruncateModal = ref(false);
const truncateType = ref('');
const truncateLabel = ref('');
const selectedProviderIdForTruncate = ref('');

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
        menus: 'CATÁLOGO DE MENÚS',
        all: 'TODA LA BASE DE DATOS' 
    };
    truncateType.value = type;
    truncateLabel.value = labels[type];
    selectedProviderIdForTruncate.value = '';
    showTruncateModal.value = true;
};

const executeTruncate = () => {
    truncateForm.type = truncateType.value;
    truncateForm.provider_id = selectedProviderIdForTruncate.value || null;
    truncateForm.post(route('admin.utilities.truncate'), {
        preserveScroll: true,
        onSuccess: () => {
            showTruncateModal.value = false;
        }
    });
};

const handleSqlImport = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (!confirm('¡ATENCIÓN! Importar un SQL reemplazará o alterará las tablas actuales. ¿Deseas continuar?')) return;

    const formData = new FormData();
    formData.append('file', file);
    
    importForm.processing = true;
    router.post(route('admin.utilities.import.sql'), formData, {
        onSuccess: () => importForm.processing = false,
        onError: () => importForm.processing = false,
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

                <!-- RESPALDO DEL SISTEMA -->
                <div class="bg-indigo-600 rounded-[3rem] p-10 shadow-2xl relative overflow-hidden group">
                    <!-- Decoración de fondo -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 h-64 w-64 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-all duration-700"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 h-40 w-40 bg-indigo-400/20 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                        <div class="flex items-center gap-6">
                            <div class="h-20 w-20 bg-white/20 rounded-[2rem] flex items-center justify-center backdrop-blur-md border border-white/30 shadow-inner">
                                <CloudArrowDownIcon class="h-10 w-10 text-white" />
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-white uppercase tracking-tight">Respaldo Integral</h3>
                                <p class="text-sm text-indigo-100 font-medium max-w-md mt-1">Descarga o restaura toda la base de datos (Compatible con SQLite y MySQL).</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-4">
                            <a :href="route('admin.utilities.backup')" 
                               class="bg-white text-indigo-600 px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-2xl hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                Descargar SQL
                            </a>

                            <button @click="$refs.sqlInput.click()" 
                                    class="bg-indigo-500/30 text-white border border-white/30 px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-500/50 transition-all flex items-center gap-3">
                                <CloudArrowUpIcon class="h-4 w-4" />
                                Importar SQL
                            </button>
                            <input type="file" ref="sqlInput" class="hidden" @change="handleSqlImport" accept=".sql" />
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
                            <div v-for="t in ['users', 'areas', 'providers', 'menus', 'sessions']" :key="t" 
                                 class="flex items-center justify-between p-5 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:border-red-200 transition-all">
                                <div>
                                    <p class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase leading-none mb-1">
                                        {{ 
                                            t === 'users' ? 'Catálogo de Usuarios' : 
                                            (t === 'areas' ? 'Catálogo de Áreas' : 
                                            (t === 'providers' ? 'Catálogo de Proveedores' : 
                                            (t === 'menus' ? 'Catálogos de Menús' : 'Historial de Sesiones y Pedidos'))) 
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
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-6">
                    Estás a punto de eliminar permanentemente registros de <span class="text-red-600 font-black">{{ truncateLabel }}</span>.
                </p>

                <!-- Provider Selector for Menus -->
                <div v-if="truncateType === 'menus'" class="mb-8 p-6 bg-gray-50 dark:bg-gray-900 rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <InputLabel value="¿Qué catálogo deseas borrar?" class="text-[10px] font-black uppercase text-gray-400 mb-3" />
                    <select v-model="selectedProviderIdForTruncate" 
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs font-bold uppercase tracking-widest focus:ring-red-500 focus:border-red-500">
                        <option value="">TODOS LOS PROVEEDORES</option>
                        <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <p class="mt-3 text-[9px] text-gray-400 font-bold uppercase italic">
                        {{ selectedProviderIdForTruncate ? 'Se borrarán solo los platillos del proveedor seleccionado.' : 'Esta acción vaciará el catálogo completo de todos los proveedores.' }}
                    </p>
                </div>

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
