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
    <Head title="Perfil de Usuario - SICOA" />

    <AuthenticatedLayout bento-tag="Perfil">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- HEADER -->
            <div class="lg:col-span-12 bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        👤
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Ajustes y Perfil Institucional</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Información Personal y Credenciales</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-8">
                <div class="bg-white dark:bg-gray-900 p-10 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                </div>
                <div class="bg-white dark:bg-gray-900 p-10 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                    <UpdatePasswordForm />
                </div>
            </div>

            <div class="lg:col-span-4 space-y-8">
                <div class="bg-gradient-to-br from-tinto-950 via-tinto-900 to-tinto-950 rounded-[3rem] p-10 text-white shadow-tinto-sm border border-oro-400/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center border border-white/20 text-oro-300">
                            <ShieldCheckIcon class="h-7 w-7 text-oro-300" />
                        </div>
                        <h4 class="text-lg font-black uppercase tracking-tight">Seguridad</h4>
                    </div>
                    <p class="text-xs text-oro-200/80 font-medium leading-relaxed">Mantén tus credenciales actualizadas para garantizar el acceso institucional seguro al sistema SICOA.</p>
                </div>
                <div class="bg-rose-50/50 dark:bg-rose-950/20 p-10 rounded-[3rem] border border-rose-200 dark:border-rose-900/60 shadow-2xs">
                    <div class="flex items-center gap-4 mb-4 text-rose-600">
                        <ExclamationTriangleIcon class="h-7 w-7" />
                        <h4 class="text-lg font-black uppercase tracking-tight">Zona Crítica</h4>
                    </div>
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
