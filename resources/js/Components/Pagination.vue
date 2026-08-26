<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1 justify-center mt-8 gap-1.5">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" 
                     class="px-4 py-2.5 text-[10px] leading-4 text-slate-400 border border-slate-200 dark:border-gray-800 rounded-xl bg-white/60 dark:bg-gray-800/60 font-black uppercase tracking-widest"
                     v-html="link.label" />
                <Link v-else 
                      class="px-4 py-2.5 text-[10px] leading-4 border rounded-xl hover:bg-tinto-50 dark:hover:bg-tinto-950/40 focus:border-tinto-700 transition-all font-black uppercase tracking-widest shadow-xs cursor-pointer"
                      :class="{ 'bg-gradient-to-tr from-tinto-900 to-tinto-800 text-oro-300 border-oro-500/40 shadow-tinto-sm': link.active, 'bg-white dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700': !link.active }"
                      :href="link.url" 
                      v-html="link.label" />
            </template>
        </div>
    </div>
</template>
