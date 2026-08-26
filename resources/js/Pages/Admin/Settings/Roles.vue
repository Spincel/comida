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
    <Head title="Control de Roles SICOA" />

    <AuthenticatedLayout bento-tag="Seguridad">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- HEADER -->
            <div class="lg:col-span-12 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        🛡️
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Permisos y Privilegios</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Matriz de Acceso y Control por Rol</p>
                    </div>
                </div>
            </div>

            <!-- LISTA DE ROLES (IZQUIERDA) -->
            <div class="lg:col-span-4 space-y-4">
                <div v-for="role in roles" :key="role.id" 
                     @click="selectRole(role)"
                     class="p-6 bg-white dark:bg-gray-900 rounded-[2.5rem] border-2 transition-all cursor-pointer group shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none"
                     :class="selectedRole.id === role.id ? 'border-tinto-700 bg-tinto-50/20 dark:bg-tinto-950/40 shadow-tinto-sm' : 'border-transparent hover:border-slate-200 dark:hover:border-gray-800'">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl flex items-center justify-center transition-all"
                             :class="selectedRole.id === role.id ? 'bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 shadow-tinto-sm border border-oro-400/40' : 'bg-slate-100 dark:bg-gray-800 text-slate-400'">
                            <ShieldCheckIcon class="h-6 w-6" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-black text-sm uppercase tracking-tight" :class="selectedRole.id === role.id ? 'text-tinto-800 dark:text-oro-300' : 'text-slate-700 dark:text-gray-300'">
                                {{ role.name }}
                            </h4>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ role.permissions.length }} Permisos activos</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-oro-50 dark:bg-oro-950/30 rounded-[2.5rem] border border-oro-200 dark:border-oro-800 flex gap-4 mt-6">
                    <InformationCircleIcon class="h-6 w-6 text-oro-700 shrink-0" />
                    <p class="text-[9px] text-oro-900 dark:text-oro-300 font-black uppercase leading-relaxed tracking-wider">
                        Los cambios en los permisos se aplicarán de inmediato a todos los usuarios con el rol seleccionado.
                    </p>
                </div>
            </div>

            <!-- MATRIZ DE PERMISOS (DERECHA) -->
            <div class="lg:col-span-8">
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden">
                    <div class="p-8 border-b border-slate-100 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/40 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Privilegios para: {{ selectedRole.name }}</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ selectedRole.description }}</p>
                        </div>
                        <KeyIcon class="h-8 w-8 text-tinto-700 dark:text-oro-400" />
                    </div>

                    <form @submit.prevent="submit" class="p-8 space-y-8">
                        <div v-for="(group, groupName) in permissions" :key="groupName" class="space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-tinto-700 dark:bg-oro-400"></span>
                                <h5 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ groupName }}</h5>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="permission in group" :key="permission.id" 
                                     @click="togglePermission(permission.id)"
                                     class="flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer group/perm"
                                     :class="form.permissions.includes(permission.id) 
                                        ? 'border-tinto-200 bg-tinto-50/40 dark:border-tinto-900/40 dark:bg-tinto-950/20' 
                                        : 'border-slate-100 dark:border-gray-800 hover:border-slate-200 dark:hover:border-gray-700'">
                                    <div class="mr-4">
                                        <Checkbox :checked="form.permissions.includes(permission.id)" @change="togglePermission(permission.id)" class="h-5 w-5 !rounded-lg text-tinto-800 focus:ring-tinto-700" />
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-tight" :class="form.permissions.includes(permission.id) ? 'text-tinto-900 dark:text-oro-300 font-black' : 'text-slate-500'">
                                        {{ permission.name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-100 dark:border-gray-800 flex justify-end">
                            <button type="submit" 
                                    class="!rounded-2xl !py-4 !px-12 bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white uppercase font-black tracking-widest text-[11px] shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all flex items-center cursor-pointer shine-effect"
                                    :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                <CheckCircleIcon class="h-5 w-5 mr-2 text-oro-300" stroke-width="3" /> Actualizar Accesos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
