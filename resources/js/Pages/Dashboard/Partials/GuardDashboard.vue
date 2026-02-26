<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    passes: Array,
    attendance: Array,
});

const unifiedActivity = computed(() => {
    const combined = [];
    if (Array.isArray(props.passes)) {
        props.passes.forEach(p => {
            if (p.real_exit_time) {
                combined.push({ id: `pass-exit-${p.id}`, user_name: p.user.name, type: 'SALIDA (PASE)', time: new Date(p.real_exit_time), status: p.will_return ? '🔄 RETORNO' : '🚫 FINAL', is_pass: true, color: 'text-blue-600' });
            }
            if (p.real_return_time) {
                combined.push({ id: `pass-return-${p.id}`, user_name: p.user.name, type: 'REGRESO (PASE)', time: new Date(p.real_return_time), status: 'COMPLETADO', is_pass: true, color: 'text-green-600' });
            }
        });
    }
    if (Array.isArray(props.attendance)) {
        props.attendance.forEach(a => {
            combined.push({ id: `att-${a.id}`, user_name: a.user.name, type: a.type === 'entry' ? 'ENTRADA' : 'SALIDA', time: new Date(a.punched_at), status: a.status === 'late' ? '⚠️ RETARDO' : (a.status === 'early_exit' ? '⚠️ ANTICIPADA' : 'OK'), is_pass: false, color: a.type === 'entry' ? 'text-green-600' : 'text-blue-600' });
        });
    }
    return combined.sort((a, b) => b.time - a.time);
});

const page = usePage();
const scanResult = computed(() => page.props.flash.scan_result);
const currentTime = ref(new Date());
let clockInterval = null;

onMounted(() => {
    clockInterval = setInterval(() => { currentTime.value = new Date(); }, 1000);
    focusScanner();
});

onUnmounted(() => { if (clockInterval) clearInterval(clockInterval); });

const formattedTime = computed(() => currentTime.value.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }));
const formattedDate = computed(() => currentTime.value.toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }).toUpperCase());

const form = useForm({ qr_code: '' });
const qrInput = ref(null);
const focusScanner = () => qrInput.value?.focus();
const showResult = ref(false);

const submitScan = () => {
    if (!form.qr_code) return;
    form.post(route('exit-passes.scan'), {
        onSuccess: () => {
            form.reset();
            focusScanner();
            showResult.value = true;
            setTimeout(() => { showResult.value = false; }, 4000); // Show result longer
        },
        onError: () => {
            form.reset();
            focusScanner();
            setTimeout(() => { form.errors.qr_code = null; }, 3000);
        },
        preserveScroll: true,
    });
};

watch(scanResult, (newVal) => {
    if (newVal) {
        showResult.value = true;
        setTimeout(() => { showResult.value = false; }, 4000);
    }
});

const statusColors = {
    late: 'text-red-600 bg-red-100 border-red-200',
    on_time: 'text-green-600 bg-green-100 border-green-200',
    early_exit: 'text-yellow-600 bg-yellow-100 border-yellow-200',
    ok: 'text-blue-600 bg-blue-100 border-blue-200'
};
</script>

<template>
    <div class="space-y-6" @click="focusScanner">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Clock and Scanner -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl shadow-2xl p-8 text-white flex flex-col items-center justify-center min-h-[300px] relative overflow-hidden">
                    <div class="absolute -top-1/2 -left-1/4 w-full h-full opacity-10 blur-xl">
                        <div class="w-96 h-96 bg-white rounded-full"></div>
                    </div>
                    <h2 class="text-2xl font-medium tracking-widest uppercase opacity-80 mb-2">{{ formattedDate }}</h2>
                    <div class="text-8xl md:text-9xl font-black tracking-tighter tabular-nums drop-shadow-lg">
                        {{ formattedTime }}
                    </div>
                    <div class="mt-6 flex items-center gap-3 bg-white/20 px-6 py-2 rounded-full backdrop-blur-sm">
                         <span class="relative flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span></span>
                        <span class="font-bold tracking-widest uppercase text-sm">Sistema Activo</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border-2 border-dashed border-indigo-200 dark:border-indigo-900 flex flex-col items-center">
                    <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mb-4 border-4 border-white dark:border-gray-800 ring-4 ring-indigo-100 dark:ring-indigo-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-6 uppercase tracking-wider">Presente su Código QR</h3>
                    <form @submit.prevent="submitScan" class="w-full max-w-md">
                        <TextInput ref="qrInput" id="qr_code" v-model="form.qr_code" type="text" class="block w-full text-center text-3xl font-black p-5 border-4 focus:border-indigo-500 rounded-2xl dark:bg-gray-900 dark:text-white" placeholder="ESCANEE AQUÍ" autocomplete="off" />
                        <p class="text-center text-gray-400 mt-4 text-sm font-medium italic">El sistema detectará automáticamente el código al escanear</p>
                    </form>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl flex flex-col overflow-hidden h-[calc(300px+2rem+304px)]">
                <div class="p-6 bg-gray-50 dark:bg-gray-900/50 border-b dark:border-gray-700">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200 uppercase tracking-widest text-sm flex items-center gap-2">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Actividad Reciente
                    </h3>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                    <div v-for="item in unifiedActivity" :key="item.id" class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700 flex flex-col gap-1 transition hover:bg-white dark:hover:bg-gray-800">
                        <div class="flex justify-between items-start">
                             <div class="min-w-0 flex-1">
                                 <p class="font-black text-gray-900 dark:text-gray-100 truncate text-sm uppercase tracking-tight">{{ item.user_name }}</p>
                                 <p class="text-[10px] font-bold uppercase tracking-widest" :class="item.color">{{ item.type }}</p>
                             </div>
                             <span class="text-[10px] font-mono font-bold bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-600 dark:text-gray-300">
                                 {{ item.time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                             </span>
                        </div>
                        <div class="flex justify-between items-center mt-1 text-[9px] font-black uppercase">
                            <span class="px-2 py-0.5 rounded-full border border-current opacity-80" :class="item.color"> {{ item.status }} </span>
                            <span class="text-gray-400 tracking-widest"> {{ item.is_pass ? 'PASE QR' : 'CREDENCIAL' }} </span>
                        </div>
                    </div>
                    <div v-if="unifiedActivity.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 italic text-sm">
                        <p>No hay movimientos registrados hoy.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Overlay -->
        <transition enter-active-class="transform transition duration-300 ease-out" enter-from-class="scale-90 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transform transition duration-200 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-90 opacity-0">
            <div v-if="showResult && scanResult" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md">
                <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border-4 border-white dark:border-gray-700">
                    <div class="p-6 text-center" :class="scanResult.type === 'ENTRADA' ? 'bg-green-600' : (scanResult.type.includes('SALIDA') ? 'bg-blue-600' : 'bg-purple-600')">
                        <h2 class="text-3xl font-black text-white uppercase tracking-[0.2em]">{{ scanResult.type }}</h2>
                    </div>
                    <div class="p-8 flex flex-col items-center">
                        <div class="w-32 h-32 rounded-full border-4 border-gray-100 dark:border-gray-700 overflow-hidden shadow-lg bg-gray-50 -mt-24 mb-4">
                            <img v-if="scanResult.user_avatar" :src="scanResult.user_avatar" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full flex items-center justify-center text-5xl font-black text-gray-300">{{ scanResult.user_name.charAt(0) }}</div>
                        </div>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white text-center leading-tight uppercase">{{ scanResult.user_name }}</h3>
                        <p class="text-md text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest mb-4">{{ scanResult.user_department }}</p>
                        <div class="bg-gray-100 dark:bg-gray-900 px-8 py-3 rounded-2xl mb-4">
                            <span class="text-4xl font-mono font-black text-indigo-600 dark:text-indigo-400">{{ scanResult.time }}</span>
                        </div>
                        <div v-if="scanResult.status && scanResult.status !== 'on_time' && scanResult.status !== 'ok'" class="px-5 py-1.5 rounded-full font-black text-lg border-2 uppercase" :class="statusColors[scanResult.status]">
                            {{ scanResult.status === 'late' ? '⚠️ RETARDO' : (scanResult.status === 'early_exit' ? '⚠️ ANTICIPADA' : scanResult.status) }}
                        </div>
                         <div v-else class="text-green-500 font-black text-lg uppercase tracking-[0.2em]">✓ REGISTRO EXITOSO</div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Error Toast -->
        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
            <div v-if="form.errors.qr_code" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[110] bg-red-600 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-4">
                <div>
                    <p class="font-black text-xl uppercase">Error de Escaneo</p>
                    <p class="text-red-100 font-medium">{{ form.errors.qr_code }}</p>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
#qr_code { caret-color: transparent; }
</style>
