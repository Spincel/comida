<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import SwipeButton from '@/Components/SwipeButton.vue';
import { 
    ClipboardDocumentCheckIcon, 
    XMarkIcon, 
    CheckBadgeIcon, 
    SparklesIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    mealType: {
        type: String,
        default: 'Comida',
    },
    count: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['close', 'confirm']);

const isProcessing = ref(false);
const isSuccess = ref(false);

const handleConfirm = () => {
    isProcessing.value = true;
    isSuccess.value = true;
    emit('confirm');
    // Keep success animation visible for a few seconds before closing
    setTimeout(() => {
        emit('close');
    }, 2800);
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        isProcessing.value = false;
        isSuccess.value = false;
    }
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="md">
        <div class="p-8 sm:p-10 text-center relative overflow-hidden dark:bg-gray-900 rounded-[3rem] transition-all">
            <!-- BOTÓN CERRAR -->
            <button v-if="!isSuccess" @click="emit('close')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-gray-800 cursor-pointer">
                <XMarkIcon class="h-6 w-6" />
            </button>

            <!-- ESTADO 1: CONFIRMACIÓN DESLIZABLE -->
            <div v-if="!isSuccess" class="space-y-6">
                <div class="flex items-center justify-center mb-2">
                    <div class="h-24 w-24 bg-gradient-to-tr from-nayarit-800 via-emerald-700 to-emerald-600 rounded-[2.5rem] flex items-center justify-center text-white shadow-[0_10px_25px_-5px_rgba(22,101,52,0.4)] border border-emerald-300/30 transition-transform hover:scale-105">
                        <span class="text-4xl animate-bounce">🍽️</span>
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                        Firmar y Enviar Pedidos
                    </h2>
                    <div class="flex items-center justify-center gap-2 mt-2">
                        <span class="px-2.5 py-0.5 rounded-lg bg-oro-100 dark:bg-oro-950/60 text-oro-900 dark:text-oro-300 border border-oro-300 dark:border-oro-800 text-[10px] font-black uppercase tracking-widest">
                            Turno: {{ mealType }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-tinto-100 dark:bg-tinto-950/60 text-tinto-900 dark:text-oro-300 border border-tinto-300 dark:border-tinto-800 text-[10px] font-black uppercase tracking-widest">
                            {{ count }} Raciones
                        </span>
                    </div>
                </div>
                
                <p class="text-xs text-slate-500 dark:text-gray-400 font-medium px-4 leading-relaxed">
                    Estás a punto de enviar las solicitudes consolidadas a Adquisiciones y Cocina para su preparación inmediata.
                </p>

                <div class="space-y-4 pt-2">
                    <SwipeButton 
                        text="Desliza para enviar a cocina" 
                        activeText="¡Registrando Platillos...!" 
                        colorClass="bg-gradient-to-r from-tinto-900 to-tinto-800"
                        icon="👨‍🍳"
                        @confirm="handleConfirm" 
                    />
                    
                    <button @click="emit('close')" 
                            class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-gray-300 transition-colors cursor-pointer py-1">
                        Cancelar y revisar pedidos
                    </button>
                </div>
            </div>

            <!-- ESTADO 2: ANIMACIÓN CELEBRATORIA DE PLATILLOS REGISTRADOS -->
            <div v-else class="py-6 space-y-6 animate-fade-in relative">
                <!-- Partículas flotantes decorativas -->
                <div class="absolute -top-4 left-1/4 animate-float-slow text-2xl select-none pointer-events-none opacity-80">✨</div>
                <div class="absolute -top-2 right-1/4 animate-float-fast text-2xl select-none pointer-events-none opacity-80">🎉</div>
                <div class="absolute top-1/2 left-6 animate-float-slow text-xl select-none pointer-events-none opacity-70">🍲</div>
                <div class="absolute top-1/2 right-6 animate-float-fast text-xl select-none pointer-events-none opacity-70">🍳</div>
                <div class="absolute -bottom-2 left-1/3 animate-float-fast text-xl select-none pointer-events-none opacity-70">🌮</div>
                <div class="absolute -bottom-2 right-1/3 animate-float-slow text-xl select-none pointer-events-none opacity-70">🥗</div>

                <!-- Insignia animada con campana gastronómica y check -->
                <div class="relative mx-auto h-32 w-32 flex items-center justify-center">
                    <!-- Ondas de pulso concéntricas -->
                    <div class="absolute inset-0 rounded-full bg-emerald-500/20 dark:bg-emerald-500/10 animate-ping"></div>
                    <div class="absolute -inset-2 rounded-full bg-oro-400/20 dark:bg-oro-400/10 animate-pulse"></div>
                    
                    <!-- Contenedor central -->
                    <div class="relative h-28 w-28 rounded-full bg-gradient-to-tr from-nayarit-800 via-emerald-700 to-emerald-600 text-white flex flex-col items-center justify-center shadow-[0_15px_35px_-5px_rgba(22,101,52,0.5)] border-4 border-oro-300">
                        <span class="text-4xl animate-bounce">🍲</span>
                        <!-- Vapor animado -->
                        <div class="flex gap-1 -mt-1 select-none pointer-events-none">
                            <span class="animate-steam-1 text-xs opacity-75">♨️</span>
                            <span class="animate-steam-2 text-xs opacity-75">♨️</span>
                        </div>
                    </div>

                    <!-- Badge de palomita dorada -->
                    <div class="absolute bottom-0 right-0 h-10 w-10 bg-gradient-to-r from-oro-500 to-oro-400 text-white rounded-full flex items-center justify-center border-2 border-white dark:border-gray-900 shadow-md animate-scale-pop">
                        <CheckBadgeIcon class="h-6 w-6 text-white" />
                    </div>
                </div>

                <div class="space-y-2">
                    <h3 class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-nayarit-800 via-emerald-700 to-oro-600 dark:from-emerald-400 dark:to-oro-300 bg-clip-text text-transparent uppercase tracking-tight">
                        ¡Tus platillos han sido registrados!
                    </h3>
                    <p class="text-xs font-bold text-slate-600 dark:text-gray-300 leading-relaxed max-w-sm mx-auto">
                        La lista de <strong class="text-emerald-700 dark:text-emerald-400">{{ count }} raciones</strong> de <strong class="text-tinto-800 dark:text-oro-300 uppercase">{{ mealType }}</strong> ha sido enviada exitosamente a cocina.
                    </p>
                </div>

                <!-- Barra de progreso institucional -->
                <div class="w-full bg-slate-100 dark:bg-gray-800 h-2.5 rounded-full overflow-hidden border border-slate-200 dark:border-gray-700 shadow-inner max-w-xs mx-auto">
                    <div class="h-full bg-gradient-to-r from-nayarit-800 via-emerald-600 to-oro-400 rounded-full animate-progress"></div>
                </div>

                <div class="pt-2">
                    <button @click="emit('close')" class="px-8 py-3 rounded-2xl bg-gradient-to-r from-nayarit-800 via-emerald-700 to-emerald-600 text-white text-[10px] font-black uppercase tracking-widest shadow-md hover:scale-105 active:scale-95 transition-all cursor-pointer">
                        Aceptar y Continuar
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
@keyframes float-slow {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-12px) rotate(8deg); }
}

@keyframes float-fast {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-16px) rotate(-8deg); }
}

@keyframes steam {
    0% { transform: translateY(0) scale(0.9); opacity: 0.8; }
    50% { transform: translateY(-6px) scale(1.1); opacity: 0.4; }
    100% { transform: translateY(-10px) scale(1.2); opacity: 0; }
}

@keyframes scale-pop {
    0% { transform: scale(0); }
    70% { transform: scale(1.25); }
    100% { transform: scale(1); }
}

@keyframes progress-fill {
    0% { width: 0%; }
    100% { width: 100%; }
}

.animate-float-slow {
    animation: float-slow 3s ease-in-out infinite;
}

.animate-float-fast {
    animation: float-fast 2.2s ease-in-out infinite;
}

.animate-steam-1 {
    animation: steam 1.8s ease-out infinite;
}

.animate-steam-2 {
    animation: steam 1.8s ease-out 0.9s infinite;
}

.animate-scale-pop {
    animation: scale-pop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

.animate-progress {
    animation: progress-fill 2.8s linear forwards;
}

.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
