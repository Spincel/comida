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
    <Head title="Mantenimiento V2.0" />

    <AuthenticatedLayout bento-tag="Utilidades">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-12 flex items-center gap-3 mb-4"><WrenchScrewdriverIcon class="h-6 w-6 text-indigo-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Gestión de Datos Maestros</h3></div>

            <div v-for="(val, key) in stats" :key="key" class="lg:col-span-4 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-gray-800 flex items-center justify-between">
                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ key === 'users' ? 'Usuarios' : (key === 'areas' ? 'Áreas' : 'Proveedores') }}</p><p class="text-3xl font-black text-slate-800 dark:text-white leading-none">{{ val }}</p></div>
                <div class="h-12 w-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600"><UsersIcon v-if="key === 'users'" class="h-6 w-6" /><BuildingOfficeIcon v-if="key === 'areas'" class="h-6 w-6" /><BuildingStorefrontIcon v-if="key === 'providers'" class="h-6 w-6" /></div>
            </div>

            <div class="lg:col-span-12 bg-indigo-600 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center gap-6">
                    <div class="h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center border border-white/20 shadow-inner"><CloudArrowDownIcon class="h-8 w-8 text-white" /></div>
                    <div><h4 class="text-2xl font-black uppercase tracking-tighter leading-none">Respaldo Integral</h4><p class="text-indigo-100 text-sm mt-2">Exporta o restaura la base de datos completa.</p></div>
                </div>
                <div class="flex gap-4"><a :href="route('admin.utilities.backup')" class="bg-white text-indigo-600 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all">Descargar SQL</a><button @click="$refs.sqlInput.click()" class="bg-indigo-500 text-white border border-white/20 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest">Importar SQL</button><input type="file" ref="sqlInput" class="hidden" @change="handleSqlImport" accept=".sql" /></div>
            </div>

            <div class="lg:col-span-6 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-100 dark:border-gray-800 shadow-sm space-y-8">
                <div class="flex items-center gap-4 border-b dark:border-gray-800 pb-6"><CloudArrowUpIcon class="h-8 w-8 text-indigo-600" /><h4 class="font-black text-slate-800 dark:text-white uppercase tracking-widest">Carga Masiva (CSV)</h4></div>
                <form @submit.prevent="submitImport" class="space-y-6">
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="t in ['users', 'areas', 'providers']" :key="t" type="button" @click="importForm.type = t" class="py-3 rounded-xl text-[9px] font-black uppercase border-2 transition-all" :class="importForm.type === t ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 dark:bg-gray-800 text-slate-400 border-transparent'">{{ t }}</button>
                    </div>
                    <div><label class="text-[9px] font-black uppercase text-slate-400 ml-2 mb-2 block">Archivo CSV</label><input type="file" @change="handleFile" accept=".csv" class="w-full text-[10px] font-black uppercase text-slate-500 file:mr-4 file:py-2 file:px-6 file:rounded-full file:border-0 file:bg-indigo-50 file:text-indigo-600" /></div>
                    <PrimaryButton class="w-full !rounded-2xl !py-5 justify-center text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100" :disabled="importForm.processing">Iniciar Carga</PrimaryButton>
                </form>
            </div>

            <div class="lg:col-span-6 bg-white dark:bg-gray-900 rounded-[3rem] p-10 border border-slate-100 dark:border-gray-800 shadow-sm space-y-8">
                <div class="flex items-center gap-4 border-b dark:border-gray-800 pb-6"><TrashIcon class="h-8 w-8 text-rose-500" /><h4 class="font-black text-slate-800 dark:text-white uppercase tracking-widest">Mantenimiento Crítico</h4></div>
                <div class="space-y-3">
                    <div v-for="t in ['users', 'areas', 'providers', 'sessions']" :key="t" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-gray-800/50 rounded-2xl border transition-all hover:border-rose-200 group">
                        <p class="text-[10px] font-black uppercase text-slate-600 dark:text-gray-400">Limpiar {{ t }}</p>
                        <button @click="openTruncateConfirm(t)" class="p-2 bg-white dark:bg-gray-800 text-rose-300 hover:text-rose-600 rounded-lg shadow-sm border"><TrashIcon class="h-4 w-4" /></button>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showTruncateModal" @close="showTruncateModal = false" max-width="md">
            <div class="p-10 text-center dark:bg-gray-900 transition-colors">
                <div class="h-20 w-20 bg-rose-50 dark:bg-rose-950/30 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner animate-pulse"><ExclamationTriangleIcon class="h-10 w-10" /></div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter mb-4">¿Confirmar Limpieza?</h3>
                <p class="text-sm text-slate-500 dark:text-gray-400 font-medium mb-10 leading-relaxed">Se eliminarán permanentemente los registros de <span class="text-rose-600 font-black">{{ truncateLabel }}</span>.</p>
                <div class="space-y-6"><SwipeButton text="Desliza para confirmar" activeText="Procesando..." colorClass="bg-rose-600" @confirm="executeTruncate" /><button @click="showTruncateModal = false" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">Cancelar Acción</button></div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
