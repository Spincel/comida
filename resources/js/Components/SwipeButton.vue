<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDoubleRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    text: { type: String, default: 'Desliza para confirmar' },
    activeText: { type: String, default: '¡Confirmado!' },
    colorClass: { type: String, default: 'bg-red-600' },
});

const emit = defineEmits(['confirm']);

const container = ref(null);
const slider = ref(null);
const isDragging = ref(false);
const startX = ref(0);
const currentTranslate = ref(0);
const isConfirmed = ref(false);

const startDrag = (e) => {
    if (isConfirmed.value) return;
    isDragging.value = true;
    startX.value = e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
};

const onDrag = (e) => {
    if (!isDragging.value || isConfirmed.value) return;
    const x = e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
    const walk = x - startX.value;
    const maxWalk = container.value.offsetWidth - slider.value.offsetWidth - 8;
    
    currentTranslate.value = Math.max(0, Math.min(walk, maxWalk));
    
    if (currentTranslate.value >= maxWalk) {
        confirmAction();
    }
};

const endDrag = () => {
    if (isConfirmed.value) return;
    isDragging.value = false;
    if (currentTranslate.value < (container.value.offsetWidth - slider.value.offsetWidth - 8)) {
        currentTranslate.value = 0;
    }
};

const confirmAction = () => {
    isConfirmed.value = true;
    isDragging.value = false;
    emit('confirm');
    setTimeout(() => {
        isConfirmed.value = false;
        currentTranslate.value = 0;
    }, 2000);
};

onMounted(() => {
    window.addEventListener('mouseup', endDrag);
    window.addEventListener('touchend', endDrag);
});

onUnmounted(() => {
    window.removeEventListener('mouseup', endDrag);
    window.removeEventListener('touchend', endDrag);
});
</script>

<template>
    <div ref="container" 
         class="relative p-1 rounded-2xl h-14 w-full flex items-center overflow-hidden transition-all duration-300 select-none border-2 border-transparent"
         :class="[isConfirmed ? 'bg-green-500' : 'bg-gray-100 dark:bg-gray-900 border-gray-200 dark:border-gray-700']"
         @mousemove="onDrag"
         @touchmove="onDrag">
        
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <span class="text-[10px] font-black uppercase tracking-[0.2em]"
                  :class="isConfirmed ? 'text-white' : 'text-gray-400 dark:text-gray-600'">
                {{ isConfirmed ? activeText : text }}
            </span>
        </div>

        <div ref="slider"
             class="h-12 w-12 rounded-xl flex items-center justify-center cursor-grab active:cursor-grabbing transition-transform duration-75 shadow-lg z-10"
             :class="[isConfirmed ? 'bg-white text-green-500' : colorClass + ' text-white']"
             :style="{ transform: `translateX(${currentTranslate}px)` }"
             @mousedown="startDrag"
             @touchstart="startDrag">
            <ChevronDoubleRightIcon v-if="!isConfirmed" class="h-6 w-6 animate-pulse" />
            <span v-else class="text-xl">✓</span>
        </div>
    </div>
</template>
