<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import axios from 'axios';

const page = usePage();
// Local state to manage notifications for immediate UI updates
const localNotifications = ref([]);
const unreadCount = computed(() => localNotifications.value.length);
const notificationPermission = ref(Notification.permission);
let pollInterval = null;

// Sync local state with props when they change (e.g. from polling)
watch(() => page.props.auth.notifications, (newVal) => {
    if (newVal) {
        localNotifications.value = newVal;
    }
}, { immediate: true });

const clearAll = () => {
    localNotifications.value = [];
    axios.post(route('notifications.markRead')).catch(() => {
        router.reload({ only: ['auth'] });
    });
};

const requestPermission = async () => {
    if (!("Notification" in window)) return;
    
    const permission = await Notification.requestPermission();
    notificationPermission.value = permission;
};

onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({
            only: ['auth'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 4000); // 4 seconds
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <div class="relative">
        <Dropdown align="right" width="80" contentClasses="py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5">
            <template #trigger>
                <button class="relative p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none transition-colors">
                    <span class="sr-only">Ver notificaciones</span>
                    <!-- Bell Icon -->
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <!-- Badge -->
                    <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white ring-2 ring-white dark:ring-gray-900">
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </span>
                </button>
            </template>

            <template #content>
                <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-400">Notificaciones</span>
                    <div class="flex gap-2">
                        <button v-if="notificationPermission === 'default'" @click.stop="requestPermission" class="text-xs text-indigo-500 hover:text-indigo-600 font-semibold" title="Activar alertas nativas">
                            Activar Alertas
                        </button>
                        <button v-if="unreadCount > 0" @click.stop="clearAll" class="text-gray-400 hover:text-red-500 transition-colors" title="Borrar todas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>

                <div v-if="localNotifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No tienes notificaciones nuevas.
                </div>

                <div v-else class="max-h-96 overflow-y-auto">
                    <div v-for="notification in localNotifications" :key="notification.id" class="relative group border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <Link :href="notification.data.link || '#'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150 ease-in-out">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 pt-0.5">
                                    <span v-if="notification.data.type === 'emergency'" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    </span>
                                    <span v-else-if="notification.data.type === 'success'" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                    <span v-else-if="notification.data.type === 'error'" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </span>
                                    <span v-else class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ notification.data.title }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ notification.data.message }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </template>
        </Dropdown>
    </div>
</template>