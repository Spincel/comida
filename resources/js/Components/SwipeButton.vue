<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDoubleRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    text: { type: String, default: 'Desliza para confirmar' },
    activeText: { type: String, default: '¡Confirmado!' },
    colorClass: { type: String, default: 'bg-emerald-600' },
    icon: { type: String, default: '🍽️' },
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
    }, 2500);
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
         class="relative p-1.5 rounded-3xl h-16 w-full flex items-center overflow-hidden transition-all duration-300 select-none border-2 shadow-inner"
         :class="[
            isConfirmed 
                ? 'bg-gradient-to-r from-nayarit-800 via-emerald-600 to-emerald-500 border-emerald-400 shadow-emerald-900/20' 
                : 'bg-slate-100 dark:bg-gray-900/90 border-slate-200/80 dark:border-gray-800'
         ]"
         @mousemove="onDrag"
         @touchmove="onDrag">
        
        <!-- Track Background Progress Fill -->
        <div v-if="!isConfirmed && currentTranslate > 0"
             class="absolute left-0 top-0 bottom-0 bg-gradient-to-r from-emerald-500/30 to-oro-400/40 rounded-3xl pointer-events-none transition-all duration-75"
             :style="{ width: `${currentTranslate + 56}px` }">
        </div>

        <!-- Center Text -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none px-12 text-center">
            <span class="text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2"
                  :class="isConfirmed ? 'text-white scale-105 animate-pulse' : 'text-slate-500 dark:text-gray-400'">
                <span v-if="isConfirmed" class="text-base">✨</span>
                {{ isConfirmed ? activeText : text }}
                <span v-if="isConfirmed" class="text-base">✨</span>
            </span>
        </div>

        <!-- Draggable Knob / Handle -->
        <div ref="slider"
             class="h-12 w-12 rounded-2xl flex items-center justify-center cursor-grab active:cursor-grabbing transition-transform duration-75 shadow-md z-10 select-none text-lg border border-white/20"
             :class="[
                isConfirmed 
                    ? 'bg-white text-emerald-700 shadow-emerald-500/40 scale-105' 
                    : `${colorClass} text-white shadow-tinto-sm hover:scale-105`
             ]"
             :style="{ transform: `translateX(${currentTranslate}px)` }"
             @mousedown="startDrag"
             @touchstart="startDrag">
            <span v-if="!isConfirmed" class="transition-transform duration-150 transform hover:scale-125 select-none pointer-events-none">
                {{ icon }}
            </span>
            <span v-else class="text-xl font-black text-emerald-600 animate-bounce">
                ✓
            </span>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(0.98); }
}
</style>
