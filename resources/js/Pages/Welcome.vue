<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const page = usePage();
const companyName = computed(() => {
    return page.props.system?.settings?.app_name || 'Comedor System';
});
</script>

<template>
    <Head :title="companyName" />
    <div class="bg-slate-50 text-slate-600 dark:bg-gray-950 dark:text-gray-400 min-h-screen flex flex-col items-center justify-center p-6 transition-colors duration-300">
        <div class="relative w-full max-w-md">
            <!-- Card de Bienvenida -->
            <div class="bg-white dark:bg-gray-900 shadow-[0_20px_50px_-10px_rgba(120,24,42,0.12)] dark:shadow-none rounded-[3rem] overflow-hidden border border-slate-200/80 dark:border-gray-800">
                <div class="p-10 text-center">
                    <!-- Logo Central -->
                    <div class="flex justify-center mb-6">
                        <ApplicationLogo class="h-28 w-auto drop-shadow-md transition-transform hover:scale-105" />
                    </div>

                    <h1 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight mb-1">
                        {{ companyName }}
                    </h1>
                    <p class="text-xs font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest mb-8">
                        Sistema Institucional de Control de Alimentación (SICOA)
                    </p>

                    <div class="space-y-4">
                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="route('dashboard')"
                                class="flex items-center justify-center w-full px-6 py-4 bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white font-black text-xs uppercase tracking-widest rounded-2xl transition-all shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 cursor-pointer shine-effect"
                            >
                                Entrar al Panel de Control
                            </Link>
                        </template>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="flex items-center justify-center w-full px-6 py-4 bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white font-black text-xs uppercase tracking-widest rounded-2xl transition-all shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 cursor-pointer shine-effect"
                            >
                                Iniciar Sesión
                            </Link>

                            <p class="text-[9px] text-slate-400 uppercase tracking-widest font-black mt-6">
                                Acceso reservado exclusivamente para personal del Congreso
                            </p>
                        </template>
                    </div>
                </div>

                <!-- Footer de la Card -->
                <div class="bg-slate-50 dark:bg-gray-800/50 p-4 border-t border-slate-100 dark:border-gray-800 text-center">
                    <p class="text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">
                        &copy; 2026 H. Congreso del Estado de Nayarit
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>