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
    const base = 'inline-flex items-center px-1 pt-1 border-b-2 text-xs font-black uppercase tracking-widest leading-5 transition duration-150 ease-in-out focus:outline-none';
    
    if (props.active) {
        return `${base} border-tinto-700 dark:border-oro-400 text-tinto-900 dark:text-oro-300`;
    }
    
    return `${base} border-transparent text-slate-500 dark:text-gray-400 hover:text-slate-700 dark:hover:text-gray-200 hover:border-slate-300 dark:hover:border-gray-700`;
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
