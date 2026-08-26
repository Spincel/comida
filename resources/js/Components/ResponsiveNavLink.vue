<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
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

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-tinto-700 dark:border-oro-400 text-start text-xs font-black uppercase tracking-widest text-tinto-800 dark:text-oro-300 bg-tinto-50 dark:bg-tinto-950/40 focus:outline-none transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-800 hover:border-slate-300 dark:hover:border-gray-700 focus:outline-none transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :method="method" :as="as" :class="classes">
        <slot />
    </Link>
</template>
