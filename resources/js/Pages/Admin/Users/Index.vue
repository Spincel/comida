<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    UserIcon, 
    ShieldCheckIcon,
    BuildingOfficeIcon,
    EnvelopeIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    users: Array,
    areas: Array,
});

const showModal = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'diner',
    area_id: '',
});

const openCreateModal = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.area_id = user.area_id || '';
    form.password = '';
    form.password_confirmation = '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const deleteUser = (id) => {
    if (confirm('¿Estás seguro de eliminar este usuario?')) {
        form.delete(route('users.destroy', id));
    }
};

const roleLabels = {
    admin: 'Administrador',
    acquisitions_manager: 'Adquisiciones',
    area_manager: 'Gerente de Área',
    diner: 'Comensal',
};

const roleColors = {
    admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    acquisitions_manager: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    area_manager: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    diner: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Gestión de Usuarios
                    </h2>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Control de Accesos y Roles</p>
                </div>
                
                <button @click="openCreateModal" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center shadow-lg shadow-indigo-100 dark:shadow-none transition-all">
                    <PlusIcon class="h-5 w-5 mr-2" stroke-width="3" /> Nuevo Usuario
                </button>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="bg-white dark:bg-gray-800 rounded-[3rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/50">
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Usuario / Email</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Rol</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Área</th>
                                    <th class="p-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-indigo-50/20 dark:hover:bg-indigo-900/5 transition-colors">
                                    <td class="p-6">
                                        <div class="flex items-center">
                                            <img :src="user.avatar_url" class="h-10 w-10 rounded-full mr-4 border-2 border-white dark:border-gray-700 shadow-sm" alt="" />
                                            <div>
                                                <p class="font-black text-sm text-gray-800 dark:text-gray-200 uppercase tracking-tight">{{ user.name }}</p>
                                                <p class="text-[10px] font-bold text-gray-400 flex items-center lowercase">
                                                    <EnvelopeIcon class="h-3 w-3 mr-1" /> {{ user.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest" :class="roleColors[user.role]">
                                            {{ roleLabels[user.role] }}
                                        </span>
                                    </td>
                                    <td class="p-6">
                                        <div v-if="user.area" class="flex items-center text-xs font-bold text-gray-600 dark:text-gray-400">
                                            <BuildingOfficeIcon class="h-4 w-4 mr-2 text-indigo-400" />
                                            {{ user.area.name }}
                                        </div>
                                        <span v-else class="text-[10px] text-gray-300 dark:text-gray-600 uppercase font-black">Sin Área</span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button @click="openEditModal(user)" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-400 hover:text-indigo-600 rounded-xl transition-all shadow-sm">
                                                <PencilSquareIcon class="h-5 w-5" />
                                            </button>
                                            <button v-if="$page.props.auth.user.id !== user.id" @click="deleteUser(user.id)" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-400 hover:text-red-600 rounded-xl transition-all shadow-sm">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL CREAR/EDITAR -->
        <Modal :show="showModal" @close="closeModal" max-width="lg">
            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">
                        {{ editingUser ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-6 w-6" /></button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="name" value="Nombre Completo" class="text-[10px] font-black uppercase text-gray-400" />
                        <TextInput id="name" type="text" class="mt-1 block w-full !rounded-2xl" v-model="form.name" required autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Correo Electrónico" class="text-[10px] font-black uppercase text-gray-400" />
                        <TextInput id="email" type="email" class="mt-1 block w-full !rounded-2xl" v-model="form.email" required />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="role" value="Rol de Usuario" class="text-[10px] font-black uppercase text-gray-400" />
                            <select id="role" v-model="form.role" class="mt-1 block w-full rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                                <option v-for="(label, value) in roleLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>
                        <div>
                            <InputLabel for="area_id" value="Área Asignada" class="text-[10px] font-black uppercase text-gray-400" />
                            <select id="area_id" v-model="form.area_id" class="mt-1 block w-full rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                                <option value="">Sin Área</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.area_id" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="password" :value="editingUser ? 'Nueva Contraseña (Opcional)' : 'Contraseña'" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput id="password" type="password" class="mt-1 block w-full !rounded-2xl" v-model="form.password" :required="!editingUser" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel for="password_confirmation" value="Confirmar Contraseña" class="text-[10px] font-black uppercase text-gray-400" />
                            <TextInput id="password_confirmation" type="password" class="mt-1 block w-full !rounded-2xl" v-model="form.password_confirmation" :required="!editingUser" />
                        </div>
                    </div>

                    <div class="pt-6">
                        <PrimaryButton class="w-full !rounded-2xl !py-4 justify-center" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ editingUser ? 'Actualizar Usuario' : 'Crear Usuario' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
