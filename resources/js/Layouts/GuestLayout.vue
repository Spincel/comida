<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 dark:bg-gray-950 p-6 transition-colors duration-300">
        <div class="mb-8 text-center flex flex-col items-center">
            <Link href="/" class="transition-transform hover:scale-105 inline-block">
                <ApplicationLogo class="h-24 w-auto fill-current" />
            </Link>
            <div class="mt-4">
                <h1 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">SICOA</h1>
                <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">Sistema de Control de Alimentación</p>
                <p class="text-[9px] font-semibold text-slate-400 uppercase tracking-wider">H. Congreso del Estado de Nayarit</p>
            </div>
        </div>

        <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-[3rem] p-10 shadow-[0_15px_45px_-5px_rgba(120,24,42,0.08)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
            <slot />
        </div>

        <footer class="mt-8 text-center text-slate-400 dark:text-gray-500 text-[10px] uppercase font-bold tracking-widest">
            <p>&copy; {{ $page.props.settings?.footer_year || '2026' }} {{ $page.props.settings?.company_name || 'H. Congreso del Estado de Nayarit' }} &reg;. Todos los derechos reservados.</p>
        </footer>
    </div>
</template>
