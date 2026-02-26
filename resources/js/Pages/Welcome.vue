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
const companyName = computed(() => page.props.settings?.company_name || 'H. Congreso del Estado de Nayarit');
</script>

<template>
    <Head title="Bienvenido" />
    <div class="bg-slate-50 text-black/50 dark:bg-slate-950 dark:text-white/50 min-h-screen flex flex-col items-center justify-center p-6">
        <div class="relative w-full max-w-md">
            <!-- Card de Bienvenida -->
            <div class="bg-white dark:bg-slate-900 shadow-2xl rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800">
                <div class="p-8 text-center">
                    <!-- Logo Central -->
                    <div class="flex justify-center mb-6">
                        <ApplicationLogo class="h-24 w-auto drop-shadow-xl" />
                    </div>

                    <h1 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight mb-2">
                        {{ companyName }}
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mb-8 font-medium">
                        Sistema Digital de Pases de Salida
                    </p>

                    <div class="space-y-4">
                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="route('dashboard')"
                                class="flex items-center justify-center w-full px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition-all shadow-lg shadow-indigo-200 dark:shadow-none"
                            >
                                Entrar al Panel de Control
                            </Link>
                        </template>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="flex items-center justify-center w-full px-6 py-4 bg-slate-800 hover:bg-black text-white font-bold rounded-2xl transition-all shadow-lg"
                            >
                                Iniciar Sesión
                            </Link>

                            <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mt-8">
                                Acceso restringido a personal autorizado
                            </p>
                        </template>
                    </div>
                </div>

                <!-- Footer de la Card -->
                <div class="bg-slate-50 dark:bg-slate-800/50 p-4 border-t border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                        &copy; {{ $page.props.settings?.footer_year || '2026' }} {{ companyName }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>