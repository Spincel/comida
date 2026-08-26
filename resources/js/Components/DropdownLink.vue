<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        default: '#',
    },
    method: {
        type: String,
        default: 'get',
    },
    as: {
        type: String,
        default: 'a',
    },
});
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :as="as"
        class="flex items-center gap-3 w-full px-4 py-2.5 text-left text-xs font-bold text-slate-700 dark:text-gray-200 hover:text-tinto-900 dark:hover:text-oro-300 hover:bg-tinto-50/80 dark:hover:bg-tinto-950/50 transition-all group rounded-xl my-0.5 mx-auto max-w-[calc(100%-0.75rem)] cursor-pointer"
    >
        <slot />
    </Link>
</template>
