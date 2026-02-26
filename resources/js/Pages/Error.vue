<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    status: Number,
    code: String,
    message: String,
});
</script>

<template>
    <Head :title="status" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg text-center">
            <div class="flex justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-4xl font-bold mb-2">{{ status }}</h1>
            
            <p class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-6">
                {{ message }}
            </p>

            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg mb-6 border border-gray-200 dark:border-gray-600">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest">Código de Error</p>
                <p class="text-xl font-mono font-bold tracking-wider select-all text-red-600 dark:text-red-400">{{ code }}</p>
            </div>

            <Link href="/" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Volver al Inicio
            </Link>
        </div>
    </div>
</template>