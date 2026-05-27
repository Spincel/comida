<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { UserIcon, ShieldCheckIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({ mustVerifyEmail: Boolean, status: String });
</script>

<template>
    <Head title="Perfil V2.0" />

    <AuthenticatedLayout bento-tag="Perfil">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-12 flex items-center gap-3 mb-4"><UserIcon class="h-6 w-6 text-indigo-600" /><h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Ajustes de Cuenta</h3></div>

            <div class="lg:col-span-8 space-y-8">
                <div class="bg-white dark:bg-gray-900 p-10 rounded-[3rem] shadow-sm border border-slate-100 dark:border-gray-800"><UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" /></div>
                <div class="bg-white dark:bg-gray-900 p-10 rounded-[3rem] shadow-sm border border-slate-100 dark:border-gray-800"><UpdatePasswordForm /></div>
            </div>

            <div class="lg:col-span-4 space-y-8">
                <div class="bg-indigo-600 rounded-[3rem] p-10 text-white shadow-2xl shadow-indigo-900/20"><div class="flex items-center gap-4 mb-6"><ShieldCheckIcon class="h-8 w-8 opacity-40" /><h4 class="text-lg font-black uppercase tracking-tighter">Seguridad</h4></div><p class="text-xs text-indigo-100 font-medium leading-relaxed">Mantén tus credenciales actualizadas para garantizar el acceso seguro al sistema de comedor.</p></div>
                <div class="bg-rose-50 dark:bg-rose-950/20 p-10 rounded-[3rem] border border-rose-100 dark:border-rose-900 shadow-sm"><div class="flex items-center gap-4 mb-6 text-rose-600"><ExclamationTriangleIcon class="h-8 w-8" /><h4 class="text-lg font-black uppercase tracking-tighter">Zona de Peligro</h4></div><DeleteUserForm /></div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
