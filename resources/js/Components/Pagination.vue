<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1 justify-center mt-8">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" 
                     class="mr-1 mb-1 px-4 py-3 text-xs leading-4 text-gray-400 border rounded-xl dark:border-gray-700 bg-white dark:bg-gray-800 font-black uppercase tracking-widest"
                     v-html="link.label" />
                <Link v-else 
                      class="mr-1 mb-1 px-4 py-3 text-xs leading-4 border rounded-xl hover:bg-white dark:hover:bg-gray-700 focus:border-indigo-500 focus:text-indigo-500 transition-all font-black uppercase tracking-widest shadow-sm"
                      :class="{ 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700': link.active, 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 dark:border-gray-700': !link.active }"
                      :href="link.url" 
                      v-html="link.label" />
            </template>
        </div>
    </div>
</template>
