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
});

const classes = computed(() => {
    const base = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-black uppercase tracking-widest leading-5 transition duration-150 ease-in-out focus:outline-none';
    
    if (props.active) {
        return `${base} border-indigo-400 dark:border-indigo-600 text-gray-900 dark:text-gray-100`;
    }
    
    return `${base} border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700`;
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
