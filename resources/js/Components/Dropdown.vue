<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '64',
    },
    contentClasses: {
        type: String,
        default: 'py-2 bg-white/95 dark:bg-gray-900/95 backdrop-blur-2xl border border-slate-200/90 dark:border-gray-800 rounded-3xl shadow-[0_20px_50px_-10px_rgba(120,24,42,0.16)] dark:shadow-[0_20px_50px_-10px_rgba(0,0,0,0.7)] overflow-hidden',
    },
    direction: {
        type: String,
        default: 'down',
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        '48': 'w-48',
        '56': 'w-56',
        '64': 'w-64',
        '72': 'w-72',
        '80': 'w-80',
        '96': 'w-96',
    }[props.width.toString()] || 'w-64';
});

const alignmentClasses = computed(() => {
    if (props.direction === 'up') {
        if (props.align === 'left') {
            return 'ltr:origin-bottom-left rtl:origin-bottom-right start-0 mb-2 bottom-full';
        } else if (props.align === 'right') {
            return 'ltr:origin-bottom-right rtl:origin-bottom-left end-0 mb-2 bottom-full';
        } else {
            return 'origin-bottom mb-2 bottom-full';
        }
    } else {
        if (props.align === 'left') {
            return 'ltr:origin-top-left rtl:origin-top-right start-0 mt-2';
        } else if (props.align === 'right') {
            return 'ltr:origin-top-right rtl:origin-top-left end-0 mt-2';
        } else {
            return 'origin-top mt-2';
        }
    }
});

const open = ref(false);
</script>

<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-show="open"
                class="absolute z-50 rounded-3xl"
                :class="[widthClass, alignmentClasses]"
                @click="open = false"
            >
                <div
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
