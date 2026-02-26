<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const notifications = computed(() => page.props.auth.notifications || []);
const showToast = ref(false);
const currentNotification = ref(null);
const seenNotifications = ref(new Set(notifications.value.map(n => n.id)));
const lastFlashMessage = ref('');
const lastFlashError = ref('');

// Watch for new notifications (length change or ID change)
watch(() => notifications.value, (newVal, oldVal) => {
    if (newVal && newVal.length > 0) {
        const latest = newVal[0];
        if (!seenNotifications.value.has(latest.id)) {
            triggerToast(latest);
            triggerSystemNotification(latest);
            seenNotifications.value.add(latest.id);
        }
    }
}, { deep: true });

// Watch for Flash Messages (Session alerts)
watch(() => page.props.flash, (newVal) => {
    if (newVal.message && newVal.message !== lastFlashMessage.value) {
        const isWarning = newVal.message.includes('Sin Retorno') || newVal.message.includes('Advertencia');
        triggerToast({
            data: { 
                title: isWarning ? 'Aviso' : 'Éxito', 
                message: newVal.message, 
                type: isWarning ? 'warning' : 'success' 
            }
        });
        lastFlashMessage.value = newVal.message;
    }
    if (newVal.error && newVal.error !== lastFlashError.value) {
        triggerToast({
            data: { 
                title: 'Error', 
                message: newVal.error, 
                type: 'error' 
            }
        });
        lastFlashError.value = newVal.error;
    }
}, { deep: true });

const triggerSystemNotification = (notification) => {
    if (!("Notification" in window)) return;
    
    if (Notification.permission === "granted") {
        try {
            new Notification(notification.data.title, {
                body: notification.data.message,
                icon: '/favicon.ico', 
            });
        } catch (e) {
            console.error("System notification failed", e);
        }
    }
};

const triggerToast = (notification) => {
    currentNotification.value = notification;
    showToast.value = true;
    
    setTimeout(() => {
        showToast.value = false;
    }, 5000);
};

const toastClasses = computed(() => {
    if (!currentNotification.value) return '';
    const type = currentNotification.value.data.type;
    switch(type) {
        case 'success': return 'bg-green-500 text-white';
        case 'error': return 'bg-red-500 text-white';
        case 'warning': return 'bg-orange-500 text-white';
        case 'emergency': return 'bg-red-600 text-white animate-pulse';
        default: return 'bg-blue-500 text-white';
    }
});
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showToast && currentNotification" class="fixed top-4 right-4 z-50 max-w-sm w-full shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden" :class="toastClasses">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Icons based on type -->
                        <svg v-if="currentNotification.data.type === 'success'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else-if="currentNotification.data.type === 'error'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium truncate">
                            {{ currentNotification.data.title }}
                        </p>
                        <p class="mt-1 text-sm opacity-90">
                            {{ currentNotification.data.message }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="showToast = false" class="bg-transparent rounded-md inline-flex text-white hover:text-gray-200 focus:outline-none">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L10 8.586 5.707 4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>