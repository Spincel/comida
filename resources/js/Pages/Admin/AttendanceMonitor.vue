<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { debounce } from 'lodash';

const props = defineProps({
    groupedLogs: Array,
    date: String,
    departments: Array,
    filters: Object,
});

const currentDate = ref(props.date);
const search = ref(props.filters?.search || '');
const departmentId = ref(props.filters?.department_id || '');

// Navigate on changes
const updateParams = () => {
    router.get(route('admin.attendance.monitor'), { 
        date: currentDate.value, 
        search: search.value,
        department_id: departmentId.value 
    }, { preserveState: true, preserveScroll: true });
};

watch(currentDate, updateParams);
watch(departmentId, updateParams);
watch(search, debounce(updateParams, 300));

// Edit Logic
const editingLog = ref(null);
const showEditModal = ref(false);
const editForm = useForm({
    user_id: null,
    date: null,
    entry_time: '',
    exit_time: '',
});

const openEditModal = (log) => {
    editingLog.value = log;
    editForm.user_id = log.user_id;
    editForm.date = log.date;
    editForm.entry_time = log.entry_time; 
    editForm.exit_time = log.exit_time;
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.post(route('admin.attendance.update'), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        }
    });
};

// Clear Day Logic
const confirmClear = ref(false);
const clearForm = useForm({ date: props.date });

const clearDay = () => {
    clearForm.date = currentDate.value;
    clearForm.post(route('admin.attendance.clear-day'), {
        onSuccess: () => confirmClear.value = false
    });
};

// Clear ALL Logic
const confirmClearAll = ref(false);
const clearAllForm = useForm({});

const clearAll = () => {
    clearAllForm.post(route('admin.attendance.clear-all'), {
        onSuccess: () => confirmClearAll.value = false
    });
};

// Export Logic
const showExportModal = ref(false);
const exportForm = useForm({
    start_date: props.date,
    end_date: props.date,
});

const submitExport = () => {
    // Standard submit for file download
    window.location.href = route('admin.attendance.export', {
        start_date: exportForm.start_date,
        end_date: exportForm.end_date
    });
    showExportModal.value = false;
};
</script>

<template>
    <Head title="Monitor de Asistencia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Monitor de Asistencia (Modo Dios)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Controls -->
                <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center bg-white dark:bg-gray-800 p-4 rounded-lg shadow gap-4">
                    
                    <!-- Filters -->
                    <div class="flex flex-col md:flex-row items-center gap-4 w-full xl:w-auto">
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <InputLabel for="date" value="Fecha:" class="font-bold" />
                            <TextInput id="date" type="date" v-model="currentDate" class="block w-full" />
                        </div>
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <InputLabel for="department" value="Depto:" class="font-bold" />
                            <select id="department" v-model="departmentId" class="block w-full md:w-48 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="">Todas las áreas</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <InputLabel for="search" value="Buscar:" class="font-bold" />
                            <TextInput id="search" type="text" v-model="search" placeholder="Nombre o No. Empleado..." class="block w-full md:w-64" />
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2 w-full xl:w-auto justify-end">
                        <PrimaryButton @click="showExportModal = true" class="bg-emerald-600 hover:bg-emerald-700">
                            📥 Respaldar (Excel/CSV)
                        </PrimaryButton>
                        <DangerButton @click="confirmClear = true" class="opacity-90 hover:opacity-100">
                            Limpiar Día
                        </DangerButton>
                        <DangerButton @click="confirmClearAll = true" class="bg-red-800 hover:bg-red-900 border-red-900">
                            🔥 BORRAR TODO
                        </DangerButton>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Empleado</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Emp</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Entrada</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Salida</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="log in groupedLogs" :key="log.user_id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover" :src="log.user_avatar || 'https://ui-avatars.com/api/?name='+log.user_name" alt="" />
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ log.user_name }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 font-mono">
                                            {{ log.employee_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                :class="log.entry_time ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400'">
                                                {{ log.entry_time || '--:--:--' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                :class="log.exit_time ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-400'">
                                                {{ log.exit_time || '--:--:--' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openEditModal(log)" class="text-indigo-600 hover:text-indigo-900 font-bold bg-indigo-50 px-3 py-1 rounded">
                                                Editar
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="groupedLogs.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No hay registros para este día que coincidan con la búsqueda.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Editar Registros de {{ editingLog?.user_name }}
                </h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="entry_time" value="Hora Entrada" />
                        <TextInput id="entry_time" type="time" step="1" v-model="editForm.entry_time" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="exit_time" value="Hora Salida" />
                        <TextInput id="exit_time" type="time" step="1" v-model="editForm.exit_time" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showEditModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitEdit" :disabled="editForm.processing">
                        Guardar Cambios
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Clear Day Confirm Modal -->
        <Modal :show="confirmClear" @close="confirmClear = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-red-600 mb-4">
                    ⚠️ ¿Limpiar día {{ currentDate }}?
                </h2>
                <p class="text-gray-600 dark:text-gray-300">
                    Esto eliminará <strong>TODOS</strong> los registros de asistencia del día seleccionado.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmClear = false">Cancelar</SecondaryButton>
                    <DangerButton @click="clearDay" :disabled="clearForm.processing">
                        Eliminar Día
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Clear ALL Confirm Modal -->
        <Modal :show="confirmClearAll" @close="confirmClearAll = false">
            <div class="p-6 border-4 border-red-600 rounded-lg">
                <h2 class="text-2xl font-black text-red-600 mb-4 text-center">
                    ☢️ PELIGRO: BORRADO TOTAL ☢️
                </h2>
                <p class="text-gray-800 dark:text-gray-200 text-center font-bold text-lg mb-4">
                    ¿Estás seguro de que quieres BORRAR TODO EL HISTORIAL DE ASISTENCIAS?
                </p>
                <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
                    Esta acción vaciará la tabla de checador por completo. Se perderán todos los registros históricos de todos los empleados. <br>
                    <strong>NO SE PUEDE DESHACER.</strong>
                </p>
                <div class="mt-6 flex justify-center gap-4">
                    <SecondaryButton @click="confirmClearAll = false" class="text-lg py-3">Cancelar</SecondaryButton>
                    <DangerButton @click="clearAll" :disabled="clearAllForm.processing" class="text-lg py-3 bg-red-700 hover:bg-red-800">
                        SÍ, BORRAR TODO EL HISTORIAL
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Export Modal -->
        <Modal :show="showExportModal" @close="showExportModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Respaldar Registros de Asistencia
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Selecciona el rango de fechas para descargar el reporte en formato CSV (compatible con Excel).
                </p>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="start_date" value="Fecha Inicio" />
                        <TextInput id="start_date" type="date" v-model="exportForm.start_date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="end_date" value="Fecha Fin" />
                        <TextInput id="end_date" type="date" v-model="exportForm.end_date" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showExportModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitExport" class="bg-emerald-600 hover:bg-emerald-700">
                        Descargar Archivo
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>