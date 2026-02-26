<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-800"
        >
            <slot />
        </div>

        <footer class="mt-8 text-center text-gray-500 dark:text-gray-400 text-[10px]">
            <p>&copy; {{ $page.props.settings?.footer_year || '2026' }} {{ $page.props.settings?.company_name || 'H. Congreso del Estado de Nayarit' }} &reg;. Todos los derechos reservados.</p>
        </footer>
    </div>
</template>
