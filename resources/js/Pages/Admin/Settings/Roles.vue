<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { 
    ShieldCheckIcon, 
    CheckCircleIcon, 
    LockClosedIcon,
    InformationCircleIcon,
    KeyIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    roles: Array,
    permissions: Object, // Grouped by 'group'
});

const selectedRole = ref(props.roles[0]);

const form = useForm({
    permissions: selectedRole.value.permissions.map(p => p.id),
});

const selectRole = (role) => {
    selectedRole.value = role;
    form.permissions = role.permissions.map(p => p.id);
};

const togglePermission = (id) => {
    const index = form.permissions.indexOf(id);
    if (index > -1) form.permissions.splice(index, 1);
    else form.permissions.push(id);
};

const submit = () => {
    form.put(route('admin.settings.roles.update', selectedRole.value.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Control de Roles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                Permisos y Seguridad
            </h2>
            <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Configuración de Accesos por Rol</p>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- LISTA DE ROLES (IZQUIERDA) -->
                    <div class="lg:col-span-4 space-y-4">
                        <div v-for="role in roles" :key="role.id" 
                             @click="selectRole(role)"
                             class="p-6 bg-white dark:bg-gray-800 rounded-[2rem] border-2 transition-all cursor-pointer group"
                             :class="selectedRole.id === role.id ? 'border-indigo-500 shadow-xl shadow-indigo-100 dark:shadow-none bg-indigo-50/10' : 'border-transparent hover:border-gray-200 dark:hover:border-gray-700'">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-2xl flex items-center justify-center transition-all"
                                     :class="selectedRole.id === role.id ? 'bg-indigo-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'">
                                    <ShieldCheckIcon class="h-6 w-6" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-black text-sm uppercase tracking-tight" :class="selectedRole.id === role.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300'">
                                        {{ role.name }}
                                    </h4>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">{{ role.permissions.length }} Permisos activos</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-amber-50 dark:bg-amber-900/20 rounded-[2rem] border border-amber-100 dark:border-amber-800 flex gap-4 mt-8">
                            <InformationCircleIcon class="h-6 w-6 text-amber-600 shrink-0" />
                            <p class="text-[9px] text-amber-700 dark:text-amber-400 font-black uppercase leading-relaxed">
                                Los cambios en los permisos se aplicarán de inmediato a todos los usuarios con el rol seleccionado.
                            </p>
                        </div>
                    </div>

                    <!-- MATRIZ DE PERMISOS (DERECHA) -->
                    <div class="lg:col-span-8">
                        <div class="bg-white dark:bg-gray-800 rounded-[3rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="p-10 border-b dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex justify-between items-center">
                                <div>
                                    <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Privilegios para: {{ selectedRole.name }}</h3>
                                    <p class="text-xs text-gray-400 font-bold mt-1 uppercase">{{ selectedRole.description }}</p>
                                </div>
                                <KeyIcon class="h-8 w-8 text-indigo-600" />
                            </div>

                            <form @submit.prevent="submit" class="p-10 space-y-10">
                                <div v-for="(group, groupName) in permissions" :key="groupName" class="space-y-6">
                                    <div class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                        <h5 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">{{ groupName }}</h5>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div v-for="permission in group" :key="permission.id" 
                                             @click="togglePermission(permission.id)"
                                             class="flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer group/perm"
                                             :class="form.permissions.includes(permission.id) 
                                                ? 'border-indigo-100 bg-indigo-50/30 dark:border-indigo-900/30 dark:bg-indigo-900/10' 
                                                : 'border-gray-50 dark:border-gray-900 hover:border-gray-100 dark:hover:border-gray-700'">
                                            <div class="mr-4">
                                                <Checkbox :checked="form.permissions.includes(permission.id)" @change="togglePermission(permission.id)" class="h-5 w-5 !rounded-lg" />
                                            </div>
                                            <span class="text-xs font-bold uppercase tracking-tight" :class="form.permissions.includes(permission.id) ? 'text-indigo-700 dark:text-indigo-400' : 'text-gray-500'">
                                                {{ permission.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-10 border-t dark:border-gray-700 flex justify-end">
                                    <PrimaryButton class="!rounded-2xl !py-4 !px-12 bg-indigo-600 hover:bg-indigo-700 uppercase font-black tracking-widest"
                                                   :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        <CheckCircleIcon class="h-5 w-5 mr-2" stroke-width="3" /> Actualizar Accesos
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
