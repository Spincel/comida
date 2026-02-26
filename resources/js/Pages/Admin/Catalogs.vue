<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';  
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user.role === 'super_admin');
const isAdmin = computed(() => ['admin', 'super_admin'].includes(page.props.auth.user.role));

const props = defineProps({
    users: Array,
    departments: Array,
    settings: Object,
    allPasses: Array,
    activeTab: String, // Recibimos el tab desde el controlador
});

// Definición dinámica de pestañas disponibles según el rol
const availableTabs = computed(() => {
    const tabs = ['departments', 'users'];
    if (isSuperAdmin.value) tabs.push('passes');
    if (isAdmin.value) tabs.push('logos');
    if (isSuperAdmin.value) tabs.push('system');
    return tabs;
});

const selectedTabIndex = computed(() => {
    const idx = availableTabs.value.indexOf(props.activeTab);
    return idx === -1 ? 0 : idx;
});

const statusColors = {
    pending: 'text-yellow-600 bg-yellow-100 border-yellow-200',
    approved: 'text-green-600 bg-green-100 border-green-200',
    rejected: 'text-red-600 bg-red-100 border-red-200',
    in_progress: 'text-blue-600 bg-blue-100 border-blue-200',
    completed: 'text-gray-600 bg-gray-100 border-gray-200'
};

const typeLabels = {
    personal: 'Personal',
    health: 'Salud',
    commission: 'Comisión'
};

const calculateDuration = (start, end) => {
    if (!start || !end) return '-';
    const diff = new Date(end) - new Date(start);
    const minutes = Math.floor((diff / 1000) / 60);
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}m`;
};

const roles = computed(() => {
    const list = {
        employee: 'Empleado',
        boss: 'Jefe de Área',
        guard: 'Vigilancia',
        admin: 'Admin RH',
    };
    if (isSuperAdmin.value) {
        list.super_admin = 'Super Admin TI';
    }
    return list;
});

// --- Pass Management ---
const editingPass = ref(null);
const passForm = useForm({
    type: '',
    reason: '',
    status: '',
    is_emergency: false,
    will_return: false,
});

const openPassModal = (pass) => {
    editingPass.value = pass;
    passForm.type = pass.type || 'personal';
    passForm.reason = pass.reason;
    passForm.status = pass.status;
    passForm.is_emergency = Boolean(pass.is_emergency);
    passForm.will_return = Boolean(pass.will_return);
};

const submitPassUpdate = () => {
    passForm.put(route('admin.exit-passes.update', editingPass.value.id), {
        onSuccess: () => editingPass.value = null
    });
};

const deletePass = (id) => {
    if (confirm('¿Eliminar este pase permanentemente?')) {
        router.delete(route('admin.exit-passes.destroy', id));
    }
};

// --- System Logic ---
const systemInfo = ref({ logs: '', php_version: '', laravel_version: '', database_size: '', server_os: '' });
const systemErrors = ref({ data: [] });
const credentialLogs = ref({ data: [] }); // New
const viewingError = ref(null);

const fetchSystemInfo = async () => {
    if (!isSuperAdmin.value) return;
    try {
        const response = await axios.get(route('admin.system.info'));
        systemInfo.value = response.data;
        const errorResponse = await axios.get(route('admin.system.errors'));
        systemErrors.value = errorResponse.data;
        const credResponse = await axios.get(route('admin.system.credentials')); // New
        credentialLogs.value = credResponse.data;
    } catch (e) {}
};

const cleanTable = (table, name) => {
    if (confirm(`⚠️ PELIGRO: ¿Borrar todo en ${name}?`)) {
        router.post(route('admin.system.clean'), { table });
    }
};

const clearSystemErrors = () => {
    if (confirm('¿Borrar todo el historial de errores?')) {
        router.post(route('admin.system.errors.clear'), {}, {
            onSuccess: () => fetchSystemInfo()
        });
    }
};

onMounted(() => {
    if (isSuperAdmin.value) fetchSystemInfo();
});

const restoreForm = useForm({ database_file: null });
const submitRestore = () => {
    if (confirm('🛑 ATENCIÓN: Se sobrescribirá toda la BD. ¿Continuar?')) {
        restoreForm.post(route('admin.system.restore'));
    }
};

const userExcelForm = useForm({ excel_file: null });
const deptExcelForm = useForm({ excel_file: null });

const submitUserImport = () => userExcelForm.post(route('admin.system.excel.users.import'));
const submitDeptImport = () => deptExcelForm.post(route('admin.system.excel.departments.import'));

// --- Credential Modal Logic ---
const showCredentialModal = ref(false);
const newCredentials = ref({ password: '', user: '' });
const tempPasswords = ref({}); // { userId: { password: '...', expiry: timestamp } }
const now = ref(Date.now());
const lastProcessedPassword = ref(''); // To prevent re-opening modal

// Update 'now' every second for countdowns
let timeInterval = null;
let credentialPollInterval = null;

const fetchRecentCredentials = async () => {
    if (!isSuperAdmin.value) return;
    try {
        const response = await axios.get(route('admin.users.recent-credentials'));
        // Merge with existing local tempPasswords (don't overwrite local session ones if newer)
        // Actually, server source is truth for Super Admin.
        const serverPasswords = response.data;
        for (const [userId, data] of Object.entries(serverPasswords)) {
            // Only update if not present or server expiry is different (meaning new gen)
            if (!tempPasswords.value[userId] || tempPasswords.value[userId].expiry !== data.expiry) {
                tempPasswords.value[userId] = data;
            }
        }
    } catch (e) {}
};

onMounted(() => {
    if (isSuperAdmin.value) {
        fetchSystemInfo();
        fetchRecentCredentials();
        // Poll for credentials every 10 seconds to catch updates from RH
        credentialPollInterval = setInterval(fetchRecentCredentials, 10000);
    }
    timeInterval = setInterval(() => { now.value = Date.now(); }, 1000);
});

onUnmounted(() => { 
    if (timeInterval) clearInterval(timeInterval); 
    if (credentialPollInterval) clearInterval(credentialPollInterval);
});

watch(() => page.props.flash, (newVal) => {
    if (newVal && newVal.new_password && newVal.new_password !== lastProcessedPassword.value) {
        lastProcessedPassword.value = newVal.new_password;
        newCredentials.value = {
            password: newVal.new_password,
            user: newVal.new_user_name
        };
        showCredentialModal.value = true;

        if (newVal.new_user_id) {
            tempPasswords.value[newVal.new_user_id] = {
                password: newVal.new_password,
                expiry: Date.now() + (5 * 60 * 1000) // 5 minutes
            };

            // Trigger Email Sending Background
            if (newVal.trigger_email) {
                axios.post(route('admin.users.send-credentials', newVal.new_user_id))
                    .then(response => {
                        // Success toast handled by axios response or we can trigger one manually
                        // But usually we don't want to spam toasts. 
                        // Maybe update the modal text?
                        console.log('Email sent successfully');
                    })
                    .catch(error => {
                        console.error('Email sending failed', error);
                        // Optional: Show error toast manually if needed
                    });
            }
        }
    }
}, { deep: true });

const getRemainingTime = (userId) => {
    const entry = tempPasswords.value[userId];
    if (!entry || entry.expiry < now.value) return null;
    const diff = Math.floor((entry.expiry - now.value) / 1000);
    const m = Math.floor(diff / 60);
    const s = diff % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
};

// --- Settings Logic ---
const settingsForm = useForm({ 
    app_logo: null, 
    report_logo: null,
    credential_background: null,
    company_name: props.settings?.company_name || '',
    footer_year: props.settings?.footer_year || '',
    monthly_personal_limit: props.settings?.monthly_personal_limit || '',
    monthly_emergency_limit: props.settings?.monthly_emergency_limit || '',
});

const submitSettings = () => {
    settingsForm.post(route('admin.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            settingsForm.app_logo = null;
            settingsForm.report_logo = null;
            settingsForm.credential_background = null;
        }
    });
};

// --- Departments Logic ---
const creatingDept = ref(false);
const deptForm = useForm({ name: '', prefix: '' });
const submitDept = () => deptForm.post(route('admin.departments.store'), { onSuccess: () => { creatingDept.value = false; deptForm.reset(); } });
const deleteDept = (id) => { if (confirm('¿Eliminar área?')) router.delete(route('admin.departments.destroy', id)); };

// --- Users Logic ---
const editingUser = ref(null);
const userForm = useForm({ 
    id: null, name: '', email: '', employee_number: '', phone_number: '', 
    role: 'employee', department: '', can_authorize: false,
    worker_type: 'base', schedule_type: '8-3', custom_entry_time: '', custom_exit_time: '' // New fields
});

const openUserModal = (user = null) => {
    if (user) {
        userForm.id = user.id; userForm.name = user.name; userForm.email = user.email; userForm.employee_number = user.employee_number;
        userForm.phone_number = user.phone_number;
        userForm.role = user.role; userForm.department = user.department; userForm.can_authorize = Boolean(user.can_authorize);
        userForm.worker_type = user.worker_type || 'base';
        userForm.schedule_type = user.schedule_type || '8-3';
        userForm.custom_entry_time = user.custom_entry_time;
        userForm.custom_exit_time = user.custom_exit_time;
    } else {
        userForm.reset(); userForm.id = null;
        userForm.worker_type = 'base'; userForm.schedule_type = '8-3';
    }
    editingUser.value = true;
};
const submitUser = () => {
    if (userForm.id) userForm.put(route('admin.users.update', userForm.id), { onSuccess: () => editingUser.value = false });
    else userForm.post(route('admin.users.store'), { onSuccess: () => editingUser.value = false });
};
const resetPassword = (user) => {
    if (confirm(`¿Regenerar contraseña para ${user.name}?`)) {
        router.post(route('admin.users.reset-password', user.id));
    }
};
const deleteUser = (user) => { if (confirm(`¿Borrar a ${user.name}?`)) router.delete(route('admin.users.destroy', user.id)); };

// --- Manual Password Logic (Super Admin) ---
const settingPasswordUser = ref(null);
const manualPasswordForm = useForm({ password: '' });

const openManualPasswordModal = (user) => {
    settingPasswordUser.value = user;
    manualPasswordForm.reset();
};

const submitManualPassword = () => {
    manualPasswordForm.post(route('admin.users.set-manual-password', settingPasswordUser.value.id), {
        onSuccess: () => settingPasswordUser.value = null
    });
};
</script>

<template>
    <Head title="Catálogos" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Administración - {{ isSuperAdmin ? 'TI (Super Admin)' : 'Recursos Humanos' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <TabGroup :selectedIndex="selectedTabIndex">
                    <TabList class="hidden">
                        <!-- Generar tabs dinámicamente para que el índice coincida con los paneles -->
                        <Tab v-for="tab in availableTabs" :key="tab">{{ tab }}</Tab>
                    </TabList>

                    <TabPanels>
                        <!-- Pestaña Departamentos (Index 0) -->
                        <TabPanel class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold">Áreas Registradas</h3>
                                <PrimaryButton @click="creatingDept = true">+ Nueva Área</PrimaryButton>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b"><tr><th class="py-2">Nombre</th><th class="py-2">Prefijo QR</th><th class="py-2 text-right">Acciones</th></tr></thead>
                                    <tbody>
                                        <tr v-for="dept in departments" :key="dept.id" class="border-b">
                                            <td class="py-3">{{ dept.name }}</td><td class="py-3 font-mono font-bold">{{ dept.prefix }}</td>
                                            <td class="py-3 text-right"><button @click="deleteDept(dept.id)" class="text-red-500">Borrar</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </TabPanel>

                        <!-- Pestaña Usuarios (Index 1) -->
                        <TabPanel class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold">Directorio de Personal</h3>
                                <PrimaryButton @click="openUserModal()">+ Nuevo Usuario</PrimaryButton>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b"><tr><th>No. Emp</th><th>Nombre</th><th>Rol</th><th>Contraseña Temp.</th><th>Depto</th><th class="text-right">Acciones</th></tr></thead>
                                        <tbody>
                                            <tr v-for="user in users" :key="user.id" class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="py-3 font-mono text-gray-500">{{ user.employee_number }}</td>
                                                <td class="py-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0 border border-gray-300 dark:border-gray-600 flex items-center justify-center">
                                                            <img v-if="user.avatar" :src="user.avatar" class="h-full w-full object-cover" />
                                                            <span v-else class="text-[10px] font-bold text-gray-500">{{ user.name.charAt(0) }}</span>
                                                        </div>
                                                                                                            <div>
                                                                                                                <div class="font-bold text-gray-900 dark:text-gray-100">{{ user.name }}</div>
                                                                                                                <div class="text-[10px] text-gray-400 leading-tight">
                                                                                                                    {{ user.email }} | <span class="font-mono font-bold text-indigo-500">{{ user.credential_code }}</span>
                                                                                                                </div>
                                                                                                                <div class="text-[10px] text-gray-400 font-mono">{{ user.phone_number }}</div>
                                                                                                            </div>                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold" :class="{'bg-black text-white': user.role === 'super_admin', 'bg-blue-100 text-blue-800': user.role !== 'super_admin'}">{{ roles[user.role] || user.role }}</span>
                                                <span v-if="user.can_authorize" class="ml-1 text-[10px] bg-yellow-100 p-0.5 rounded">✍️</span>
                                            </td>
                                            <td class="py-3">
                                                <div v-if="getRemainingTime(user.id)" class="inline-flex flex-col items-center bg-yellow-50 border border-yellow-200 px-2 py-1 rounded animate-pulse">
                                                    <span class="font-mono font-black text-blue-600 select-all">{{ tempPasswords[user.id].password }}</span>
                                                    <span class="text-[10px] text-red-500 font-bold">Expira en: {{ getRemainingTime(user.id) }}</span>
                                                </div>
                                                <span v-else class="text-gray-300 text-xs italic">-</span>
                                            </td>
                                            <td>{{ user.department }}</td>
                                            <td class="text-right flex justify-end gap-2 items-center h-full pt-3">
                                                <button v-if="isSuperAdmin" @click="openManualPasswordModal(user)" class="text-purple-500 text-[10px] font-bold border border-purple-200 px-1 rounded hover:bg-purple-50" title="Establecer Manual">Key</button>
                                                <button @click="resetPassword(user)" class="text-orange-500 text-[10px] font-bold border border-orange-200 px-1 rounded hover:bg-orange-50">Reset</button>
                                                <button @click="openUserModal(user)" class="text-blue-500">Editar</button>
                                                <button @click="deleteUser(user)" class="text-red-500">Borrar</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </TabPanel>

                        <!-- Pestaña Gestión Pases (Index 2 - Super Admin Only) -->
                        <TabPanel v-if="isSuperAdmin" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold mb-6">Bitácora Maestra de Pases</h3>
                            <div v-for="dept in departments" :key="'g-'+dept.id" class="mb-8">
                                <h4 class="bg-blue-50 dark:bg-blue-900/20 p-2 font-black text-blue-800 dark:text-blue-300 uppercase tracking-widest text-xs">📁 {{ dept.name }}</h4>
                                <table class="w-full text-left text-[10px]">
                                    <tr v-for="pass in allPasses.filter(p => p.user.department === dept.name)" :key="pass.id" class="border-b hover:bg-gray-50">
                                        <td class="p-2"><b>{{ pass.user.name }}</b></td>
                                        <td class="p-2"><span :class="pass.is_emergency ? 'text-red-600' : 'text-blue-600'">[{{ pass.type ?? 'EMG' }}]</span> {{ pass.reason }}</td>
                                        <td class="p-2 text-center">{{ pass.real_exit_time ? new Date(pass.real_exit_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '--:--' }}</td>
                                        <td class="p-2 text-center">{{ pass.real_return_time ? new Date(pass.real_return_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '--:--' }}</td>
                                        <td class="p-2 text-center font-bold text-gray-600">{{ pass.will_return ? calculateDuration(pass.real_exit_time, pass.real_return_time) : 'N/A' }}</td>
                                        <td class="p-2">
                                            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase border" :class="statusColors[pass.status]">{{ pass.status }}</span>
                                        </td>
                                        <td class="p-2 text-right"><button @click="openPassModal(pass)" class="text-blue-500 mr-2">Editar</button><button @click="deletePass(pass.id)" class="text-red-500">Borrar</button></td>
                                    </tr>
                                </table>
                            </div>
                        </TabPanel>

                        <!-- Pestaña Logos y Configuración -->
                        <TabPanel v-if="isAdmin" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold mb-6 text-gray-800 dark:text-gray-100">Identidad y Límites</h3>
                            
                            <form @submit.prevent="submitSettings">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-b dark:border-gray-700 pb-8">
                                    <!-- Identity Section -->
                                    <div class="space-y-4">
                                        <h4 class="font-bold text-gray-700 dark:text-gray-300">Identidad de la Empresa</h4>
                                        <div>
                                            <InputLabel value="Nombre de la Empresa / Institución" />
                                            <TextInput v-model="settingsForm.company_name" class="w-full" />
                                        </div>
                                        <div>
                                            <InputLabel value="Año en Pie de Página" />
                                            <TextInput v-model="settingsForm.footer_year" class="w-full" maxlength="4" />
                                        </div>
                                    </div>
                                    
                                    <!-- Limits Section -->
                                    <div class="space-y-4">
                                        <h4 class="font-bold text-gray-700 dark:text-gray-300">Límites Mensuales (0 = Sin Límite)</h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel value="Límite Pases Personales" />
                                                <TextInput type="number" v-model="settingsForm.monthly_personal_limit" class="w-full" placeholder="Ej: 3" />
                                            </div>
                                            <div>
                                                <InputLabel value="Límite Pases de Emergencia" />
                                                <TextInput type="number" v-model="settingsForm.monthly_emergency_limit" class="w-full" placeholder="Ej: 1" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-4">
                                    <div class="border p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700">
                                        <h4 class="font-bold mb-4">Logo Principal (Web)</h4>
                                        <div v-if="settingsForm.errors.app_logo" class="text-red-500 text-xs mb-2">{{ settingsForm.errors.app_logo }}</div>
                                        <div class="mb-4 h-32 flex items-center justify-center bg-white p-2 rounded shadow-inner"><img v-if="settings?.app_logo" :src="settings.app_logo" class="max-h-full max-w-full" /></div>
                                        <input type="file" @input="settingsForm.app_logo = $event.target.files[0]" class="text-xs" accept="image/*" />
                                    </div>
                                    <div class="border p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700">
                                        <h4 class="font-bold mb-4">Logo Reportes (PDF)</h4>
                                        <div v-if="settingsForm.errors.report_logo" class="text-red-500 text-xs mb-2">{{ settingsForm.errors.report_logo }}</div>
                                        <div class="mb-4 h-32 flex items-center justify-center bg-white p-2 rounded shadow-inner"><img v-if="settings?.report_logo" :src="settings.report_logo" class="max-h-full max-w-full" /></div>
                                        <input type="file" @input="settingsForm.report_logo = $event.target.files[0]" class="text-xs" accept="image/*" />
                                    </div>
                                    <div class="border p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700">
                                        <h4 class="font-bold mb-4">Fondo de Credencial</h4>
                                        <div v-if="settingsForm.errors.credential_background" class="text-red-500 text-xs mb-2">{{ settingsForm.errors.credential_background }}</div>
                                        <div class="mb-4 h-32 flex items-center justify-center bg-white p-2 rounded shadow-inner"><img v-if="settings?.credential_background" :src="settings.credential_background" class="max-h-full max-w-full" /></div>
                                        <input type="file" @input="settingsForm.credential_background = $event.target.files[0]" class="text-xs" accept="image/*" />
                                        <p class="text-[10px] text-gray-500 mt-2">Recomendado: 1011x638 px</p>
                                    </div>
                                </div>
                                <div class="flex justify-end mt-8">
                                    <PrimaryButton type="submit" :disabled="settingsForm.processing">Guardar Cambios</PrimaryButton>
                                </div>
                            </form>
                        </TabPanel>

                        <!-- Pestaña Sistema (Index Final - Super Admin Only) -->
                        <TabPanel v-if="isSuperAdmin" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-red-600 mb-6">Mantenimiento y Respaldo</h3>
                            <div class="grid grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <div class="bg-red-50 p-4 rounded border border-red-100 flex justify-between items-center">
                                        <span class="text-sm font-bold">Limpiar Pases</span>
                                        <DangerButton @click="cleanTable('exit_passes', 'Pases')">Limpiar</DangerButton>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded border border-green-100 flex justify-between items-center">
                                        <span class="text-sm font-bold">Respaldo SQLITE</span>
                                        <a :href="route('admin.system.backup')" class="text-xs bg-green-600 text-white px-3 py-1 rounded">Descargar</a>
                                    </div>
                                    <div class="bg-indigo-50 p-4 rounded border border-indigo-100">
                                        <span class="text-sm font-bold block mb-2 font-mono">Restauración SQLITE / Carga Excel</span>
                                        <div class="space-y-2">
                                            <input type="file" @input="restoreForm.database_file = $event.target.files[0]" class="text-[10px]" accept=".sqlite" />
                                            <PrimaryButton @click="submitRestore" class="text-[9px] bg-indigo-600">Restaurar DB</PrimaryButton>
                                        </div>
                                        <div class="mt-4 border-t pt-2">
                                            <input type="file" @input="userExcelForm.excel_file = $event.target.files[0]" class="text-[10px]" accept=".xlsx" />
                                            <button @click="submitUserImport" class="text-[9px] text-blue-600 underline">Subir Personal</button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xs font-mono mb-2">Monitor de Logs:</h4>
                                    <textarea readonly class="w-full h-64 text-[9px] font-mono bg-black text-green-400 p-2 rounded" :value="systemInfo.logs"></textarea>
                                </div>
                            </div>

                            <!-- Tabla de Errores del Sistema -->
                            <div class="mt-8 border-t pt-8">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-red-800">Errores del Sistema</h3>
                                    <DangerButton @click="clearSystemErrors" class="text-xs">Limpiar Historial</DangerButton>
                                </div>
                                <div class="overflow-x-auto mb-8">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-gray-100 dark:bg-gray-700 font-bold">
                                            <tr>
                                                <th class="p-2">Código</th>
                                                <th class="p-2">Status</th>
                                                <th class="p-2">Mensaje</th>
                                                <th class="p-2">Usuario</th>
                                                <th class="p-2">Fecha</th>
                                                <th class="p-2">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="error in systemErrors.data" :key="error.id" class="border-b hover:bg-red-50 dark:hover:bg-red-900/20">
                                                <td class="p-2 font-mono text-red-600 font-bold">{{ error.code }}</td>
                                                <td class="p-2 font-mono">{{ error.status_code }}</td>
                                                <td class="p-2 truncate max-w-xs" :title="error.message">{{ error.message }}</td>
                                                <td class="p-2">{{ error.user ? error.user.name : 'Guest' }}</td>
                                                <td class="p-2">{{ new Date(error.created_at).toLocaleString() }}</td>
                                                <td class="p-2"><button @click="viewingError = error" class="text-blue-600 underline">Detalle</button></td>
                                            </tr>
                                            <tr v-if="systemErrors.data.length === 0">
                                                <td colspan="6" class="p-4 text-center text-gray-500">Sin errores registrados. ¡Buen trabajo!</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Tabla de Historial de Contraseñas (Modo Dios) -->
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-indigo-800 dark:text-indigo-400">Historial de Credenciales Generadas</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-gray-100 dark:bg-gray-700 font-bold">
                                            <tr>
                                                <th class="p-2">Usuario</th>
                                                <th class="p-2">No. Empleado</th>
                                                <th class="p-2">Contraseña Generada</th>
                                                <th class="p-2">Generada Por</th>
                                                <th class="p-2">Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="log in credentialLogs.data" :key="log.id" class="border-b hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                                                <td class="p-2 font-bold">{{ log.user ? log.user.name : 'Usuario Eliminado' }}</td>
                                                <td class="p-2 font-mono">{{ log.user ? log.user.employee_number : 'N/A' }}</td>
                                                <td class="p-2 font-mono bg-yellow-100 dark:bg-yellow-900/50 px-2 rounded select-all">{{ log.decrypted_password }}</td>
                                                <td class="p-2">{{ log.creator ? log.creator.name : 'Sistema' }}</td>
                                                <td class="p-2">{{ new Date(log.created_at).toLocaleString() }}</td>
                                            </tr>
                                            <tr v-if="credentialLogs.data.length === 0">
                                                <td colspan="5" class="p-4 text-center text-gray-500">No hay registros de credenciales generadas.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>

        <!-- MODALES -->
        <Modal :show="!!viewingError" @close="viewingError = null">
            <div class="p-6">
                <h2 class="text-lg font-bold text-red-600 mb-2">Detalle del Error {{ viewingError?.code }}</h2>
                <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-xs font-mono overflow-auto h-96">
                    <p class="mb-2"><strong>URL:</strong> {{ viewingError?.url }}</p>
                    <p class="mb-2"><strong>IP:</strong> {{ viewingError?.ip }}</p>
                    <p class="mb-2 text-red-700"><strong>Mensaje:</strong> {{ viewingError?.message }}</p>
                    <hr class="my-2 border-gray-300"/>
                    <pre class="whitespace-pre-wrap">{{ viewingError?.stack_trace }}</pre>
                </div>
                <div class="flex justify-end mt-4">
                    <SecondaryButton @click="viewingError = null">Cerrar</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="creatingDept" @close="creatingDept = false">
            <div class="p-6"><h2 class="font-bold mb-4">Nueva Área</h2>
                <TextInput v-model="deptForm.name" class="w-full mb-4" placeholder="Nombre de la Dependencia" />
                <TextInput v-model="deptForm.prefix" class="w-full mb-4" placeholder="Prefijo QR (3-5 letras)" maxlength="5" />
                <div class="flex justify-end gap-2"><SecondaryButton @click="creatingDept = false">Cancelar</SecondaryButton><PrimaryButton @click="submitDept">Crear</PrimaryButton></div>
            </div>
        </Modal>

        <Modal :show="!!editingUser" @close="editingUser = false">
            <div class="p-6"><h2 class="font-bold mb-4">{{ userForm.id ? 'Editar' : 'Nuevo' }} Usuario</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <TextInput v-model="userForm.name" class="w-full" placeholder="Nombre Completo" />
                        <InputError :message="userForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <TextInput v-model="userForm.employee_number" class="w-full" placeholder="No. Emp" />
                        <InputError :message="userForm.errors.employee_number" class="mt-1" />
                    </div>
                    <div>
                        <TextInput v-model="userForm.email" class="w-full" placeholder="Correo Institucional" />
                        <InputError :message="userForm.errors.email" class="mt-1" />
                    </div>
                    <div>
                        <TextInput v-model="userForm.phone_number" class="w-full" placeholder="Teléfono (WhatsApp)" />
                        <InputError :message="userForm.errors.phone_number" class="mt-1" />
                    </div>
                    <div>
                        <select v-model="userForm.role" class="w-full rounded border-gray-300 text-sm">
                            <option v-for="(label, value) in roles" :key="value" :value="value">{{ label }}</option>
                        </select>
                        <InputError :message="userForm.errors.role" class="mt-1" />
                    </div>
                    <div>
                        <select v-model="userForm.department" class="w-full rounded border-gray-300 text-sm">
                            <option value="" disabled>Selecciona Área</option>
                            <option v-for="d in departments" :key="d.id" :value="d.name">{{ d.name }}</option>
                        </select>
                        <InputError :message="userForm.errors.department" class="mt-1" />
                    </div>
                    
                    <!-- Attendance Fields -->
                    <div>
                        <select v-model="userForm.worker_type" class="w-full rounded border-gray-300 text-sm">
                            <option value="base">Base</option>
                            <option value="confianza">Confianza</option>
                            <option value="jefe">Jefe</option>
                        </select>
                        <InputError :message="userForm.errors.worker_type" class="mt-1" />
                    </div>
                    <div>
                        <select v-model="userForm.schedule_type" class="w-full rounded border-gray-300 text-sm">
                            <option value="8-3">08:00 - 15:00</option>
                            <option value="9-4">09:00 - 16:00</option>
                            <option value="custom">Personalizado</option>
                        </select>
                        <InputError :message="userForm.errors.schedule_type" class="mt-1" />
                    </div>
                    <div v-if="userForm.schedule_type === 'custom'" class="col-span-2 grid grid-cols-2 gap-4 bg-gray-50 p-2 rounded">
                        <div>
                            <InputLabel value="Entrada" class="text-xs" />
                            <TextInput v-model="userForm.custom_entry_time" type="time" class="w-full" />
                            <InputError :message="userForm.errors.custom_entry_time" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Salida" class="text-xs" />
                            <TextInput v-model="userForm.custom_exit_time" type="time" class="w-full" />
                            <InputError :message="userForm.errors.custom_exit_time" class="mt-1" />
                        </div>
                    </div>

                    <div v-if="userForm.role === 'employee'" class="flex items-center"><label class="text-xs font-bold"><input type="checkbox" v-model="userForm.can_authorize"> Autorizador</label></div>
                    <div class="text-xs text-gray-500 italic col-span-2 mt-2">* La contraseña se generará automáticamente y se enviará por WhatsApp.</div>
                </div>
                <div class="flex justify-end mt-6 gap-2"><SecondaryButton @click="editingUser = false">Cancelar</SecondaryButton><PrimaryButton @click="submitUser">Guardar</PrimaryButton></div>
            </div>
        </Modal>

        <!-- Modal de Credenciales Generadas -->
        <Modal :show="showCredentialModal" @close="showCredentialModal = false">
            <div class="p-6 text-center">
                <div class="mb-4 text-green-500">
                    <svg class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-gray-100">Credenciales Generadas</h2>
                <p class="text-gray-500 mb-6">Por favor, comparta esta contraseña con <strong>{{ newCredentials.user }}</strong>.</p>
                
                <div class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg mb-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Nueva Contraseña</p>
                    <p class="text-3xl font-mono font-black text-blue-600 dark:text-blue-400 select-all tracking-wider break-all">{{ newCredentials.password }}</p>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded text-xs text-yellow-700 dark:text-yellow-400 mb-6">
                    ⚠️ Asegúrese de copiarla. Solo se mostrará esta vez aquí. Si se pierde, el administrador deberá regenerarla.
                </div>

                <PrimaryButton @click="showCredentialModal = false" class="w-full justify-center">Entendido, Cerrar</PrimaryButton>
            </div>
        </Modal>

        <!-- Modal Contraseña Manual (Super Admin) -->
        <Modal :show="!!settingPasswordUser" @close="settingPasswordUser = null">
            <div class="p-6">
                <h2 class="font-bold mb-4 text-purple-700">Establecer Contraseña Manual</h2>
                <p class="text-sm text-gray-500 mb-4">Para usuario: <strong>{{ settingPasswordUser?.name }}</strong></p>
                <div class="mb-4">
                    <InputLabel value="Nueva Contraseña" />
                    <TextInput v-model="manualPasswordForm.password" type="text" class="w-full font-mono" placeholder="Escribe la nueva contraseña..." />
                    <div v-if="manualPasswordForm.errors.password" class="text-red-500 text-xs">{{ manualPasswordForm.errors.password }}</div>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="settingPasswordUser = null">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitManualPassword" class="bg-purple-600 hover:bg-purple-700">Guardar</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="!!editingPass" @close="editingPass = null">
            <div class="p-6"><h2 class="font-bold mb-4">Editar Pase #{{ editingPass?.id }}</h2>
                <div class="space-y-4">
                    <select v-model="passForm.type" class="w-full rounded border-gray-300">
                        <option value="personal">Personal</option><option value="health">Salud</option><option value="commission">Comisión</option>
                    </select>
                    <TextInput v-model="passForm.reason" class="w-full" placeholder="Motivo" />
                    <select v-model="passForm.status" class="w-full rounded border-gray-300">
                        <option value="pending">Pendiente</option><option value="approved">Autorizado</option><option value="in_progress">En Curso</option><option value="completed">Finalizado</option>
                    </select>
                    <div class="flex gap-4 p-2 bg-gray-50 rounded">
                        <label class="text-xs font-bold"><input type="checkbox" v-model="passForm.is_emergency"> 🚨 Emergencia</label>
                        <label class="text-xs font-bold"><input type="checkbox" v-model="passForm.will_return"> 🔄 Regresa</label>
                    </div>
                </div>
                <div class="flex justify-end mt-6 gap-2"><SecondaryButton @click="editingPass = null">Cancelar</SecondaryButton><PrimaryButton @click="submitPassUpdate">Actualizar</PrimaryButton></div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>