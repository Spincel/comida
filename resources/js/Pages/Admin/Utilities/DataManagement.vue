<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SwipeButton from '@/Components/SwipeButton.vue';
import { 
    CloudArrowUpIcon, CloudArrowDownIcon, TrashIcon, TableCellsIcon, ExclamationTriangleIcon,
    InformationCircleIcon, ArrowDownTrayIcon, UsersIcon, BuildingOfficeIcon, BuildingStorefrontIcon,
    XMarkIcon, WrenchScrewdriverIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ stats: Object, providers: { type: Array, default: () => [] } });

const importForm = useForm({ type: 'users', file: null });
const truncateForm = useForm({ type: '', provider_id: null });

const showTruncateModal = ref(false), truncateType = ref(''), truncateLabel = ref(''), selectedProviderIdForTruncate = ref('');

const handleFile = (e) => { importForm.file = e.target.files[0]; };
const submitImport = () => { if (!importForm.file) return alert('Archivo requerido'); importForm.post(route('admin.utilities.import'), { preserveScroll: true, onSuccess: () => importForm.reset() }); };

const openTruncateConfirm = (t) => {
    const labels = { users: 'Usuarios', areas: 'Áreas', providers: 'Proveedores', sessions: 'Sesiones y Pedidos', menus: 'Catálogo de Menús', all: 'Sistema Completo' };
    truncateType.value = t; truncateLabel.value = labels[t]; selectedProviderIdForTruncate.value = ''; showTruncateModal.value = true;
};

const executeTruncate = () => {
    truncateForm.type = truncateType.value; truncateForm.provider_id = selectedProviderIdForTruncate.value || null;
    truncateForm.post(route('admin.utilities.truncate'), { preserveScroll: true, onSuccess: () => { showTruncateModal.value = false; } });
};

const handleSqlImport = (e) => {
    const file = e.target.files[0]; if (!file || !confirm('¡Atención! Acción irreversible. ¿Continuar?')) return;
    const fd = new FormData(); fd.append('file', file);
    router.post(route('admin.utilities.import.sql'), fd, { preserveScroll: true });
};
</script>

<template>
    <Head title="Mantenimiento de Datos SICOA" />

    <AuthenticatedLayout bento-tag="Utilidades">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- HEADER -->
            <div class="lg:col-span-12 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        🛠️
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Gestión de Datos Maestros</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Respaldos, Cargas Masivas y Depuración de Tablas</p>
                    </div>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div v-for="(val, key) in stats" :key="key" class="lg:col-span-4 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ key === 'users' ? 'Usuarios Activos' : (key === 'areas' ? 'Áreas Registradas' : 'Proveedores') }}</p>
                    <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-2">{{ val }}</p>
                </div>
                <div class="h-14 w-14 bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 rounded-2xl flex items-center justify-center">
                    <UsersIcon v-if="key === 'users'" class="h-7 w-7" />
                    <BuildingOfficeIcon v-if="key === 'areas'" class="h-7 w-7" />
                    <BuildingStorefrontIcon v-if="key === 'providers'" class="h-7 w-7" />
                </div>
            </div>

            <!-- HERO BACKUP BANNER -->
            <div class="lg:col-span-12 bg-gradient-to-r from-tinto-950 via-tinto-900 to-tinto-950 rounded-[3rem] p-10 text-white shadow-tinto-sm border border-oro-500/30 relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center gap-6">
                    <div class="h-16 w-16 bg-white/10 rounded-2xl flex items-center justify-center border border-white/20 text-oro-300 shadow-inner">
                        <CloudArrowDownIcon class="h-8 w-8 text-oro-300" />
                    </div>
                    <div>
                        <h4 class="text-2xl font-black uppercase tracking-tight leading-none text-white">Respaldo Integral de Base de Datos</h4>
                        <p class="text-oro-200/80 text-xs mt-2 font-medium">Exporta la estructura y datos completos del sistema SICOA o restaura un volcado SQL.</p>
                    </div>
                </div>
                <div class="flex gap-4 shrink-0">
                    <a :href="route('admin.utilities.backup')" class="bg-gradient-to-r from-oro-600 to-oro-500 hover:from-oro-700 hover:to-oro-600 text-white px-7 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-oro-sm border border-oro-300/40 hover:scale-105 active:scale-95 transition-all cursor-pointer">
                        Descargar SQL
                    </a>
                    <button @click="$refs.sqlInput.click()" class="bg-white/10 hover:bg-white/20 text-oro-300 border border-white/20 px-7 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">
                        Importar SQL
                    </button>
                    <input type="file" ref="sqlInput" class="hidden" @change="handleSqlImport" accept=".sql" />
                </div>
            </div>

            <!-- CSV MASS IMPORT -->
            <div class="lg:col-span-6 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none space-y-6">
                <div class="flex items-center gap-4 border-b border-slate-100 dark:border-gray-800 pb-4">
                    <CloudArrowUpIcon class="h-7 w-7 text-tinto-700 dark:text-oro-400" />
                    <h4 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-xs">Carga Masiva (CSV)</h4>
                </div>
                <form @submit.prevent="submitImport" class="space-y-6">
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="t in ['users', 'areas', 'providers']" :key="t" type="button" @click="importForm.type = t" 
                                class="py-3 rounded-xl text-[9px] font-black uppercase border transition-all cursor-pointer" 
                                :class="importForm.type === t ? 'bg-gradient-to-r from-tinto-900 to-tinto-800 text-oro-300 border-oro-400/40 shadow-tinto-sm' : 'bg-slate-50 dark:bg-gray-800 text-slate-400 border-slate-200 dark:border-gray-700'">
                            {{ t === 'users' ? 'Usuarios' : (t === 'areas' ? 'Áreas' : 'Proveedores') }}
                        </button>
                    </div>
                    <div>
                        <label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-widest">Archivo CSV Delimitado</label>
                        <input type="file" @change="handleFile" accept=".csv" class="w-full text-[10px] font-black uppercase text-slate-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:bg-tinto-50 dark:file:bg-tinto-950/60 file:text-tinto-800 dark:file:text-oro-300 cursor-pointer" />
                    </div>
                    <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white text-[10px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect" :disabled="importForm.processing">
                        Iniciar Carga
                    </button>
                </form>
            </div>

            <!-- CRITICAL PURGE -->
            <div class="lg:col-span-6 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-200/80 dark:border-gray-800 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none space-y-6">
                <div class="flex items-center gap-4 border-b border-slate-100 dark:border-gray-800 pb-4">
                    <TrashIcon class="h-7 w-7 text-rose-500" />
                    <h4 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-xs">Mantenimiento Crítico de Tablas</h4>
                </div>
                <div class="space-y-3">
                    <div v-for="t in ['users', 'areas', 'providers', 'sessions']" :key="t" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-gray-800/50 rounded-2xl border border-slate-100 dark:border-gray-800 hover:border-rose-300 transition-all group">
                        <p class="text-[10px] font-black uppercase text-slate-700 dark:text-gray-300">Limpiar {{ t === 'users' ? 'Usuarios' : (t === 'areas' ? 'Áreas' : (t === 'providers' ? 'Proveedores' : 'Sesiones y Pedidos')) }}</p>
                        <button @click="openTruncateConfirm(t)" class="p-2.5 bg-white dark:bg-gray-800 text-rose-400 hover:text-rose-600 rounded-xl shadow-xs border border-slate-200 dark:border-gray-700 cursor-pointer"><TrashIcon class="h-4 w-4" /></button>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showTruncateModal" @close="showTruncateModal = false" max-width="md">
            <div class="p-10 text-center dark:bg-gray-900 rounded-[2.5rem]">
                <div class="h-20 w-20 bg-rose-50 dark:bg-rose-950/30 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner animate-pulse">
                    <ExclamationTriangleIcon class="h-10 w-10" />
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3">¿Confirmar Limpieza?</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400 font-medium mb-8 leading-relaxed">Se eliminarán permanentemente los registros de <span class="text-rose-600 font-black">{{ truncateLabel }}</span>.</p>
                <div class="space-y-4">
                    <SwipeButton text="Desliza para confirmar" activeText="Procesando..." colorClass="bg-rose-600" @confirm="executeTruncate" />
                    <button @click="showTruncateModal = false" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">Cancelar Acción</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
