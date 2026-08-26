<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';
import { CheckBadgeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    session: Object, // { id, date, meal_type, provider_name, orders: [...] }
});

const emit = defineEmits(['close']);

const form = useForm({
    justifications: [],
});

watch(() => props.show, (isVisible) => {
    if (isVisible && props.session) {
        form.justifications = props.session.orders.map(o => ({
            id: o.id,
            user_name: o.user_name,
            platillo: o.platillo,
            activity_performed: o.activity_performed || '',
        }));
    }
});

const submit = () => {
    form.put(route('orders.saveJustifications'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="2xl">
        <div class="p-8 dark:bg-gray-900 rounded-[2.5rem]">
            <div class="flex items-center gap-4 mb-4">
                <div class="h-12 w-12 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 flex items-center justify-center text-xl border border-tinto-200 dark:border-tinto-800 shadow-2xs">
                    📝
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-gray-100 uppercase tracking-tight">
                        Justificación de Actividades
                    </h2>
                    <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest">
                        {{ session?.meal_type }} — {{ session?.date }} — {{ session?.provider_name }}
                    </p>
                </div>
            </div>

            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar mb-6">
                <div v-for="(item, index) in form.justifications" :key="item.id" class="p-5 bg-slate-50 dark:bg-gray-800/60 rounded-2xl border border-slate-200/80 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-black text-xs text-slate-800 dark:text-gray-200 uppercase">{{ item.user_name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">🍽️ {{ item.platillo }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <InputLabel :for="'act_' + item.id" value="Actividad Institucional Justificada" class="text-[9px] uppercase font-black text-tinto-700 dark:text-oro-400 mb-1 tracking-wider" />
                        <textarea
                            :id="'act_' + item.id"
                            v-model="form.justifications[index].activity_performed"
                            rows="2"
                            class="block w-full rounded-xl border-slate-200 dark:border-gray-700 dark:bg-gray-900 shadow-inner focus:border-tinto-700 focus:ring-tinto-700 text-xs font-medium text-slate-700 dark:text-gray-200"
                            placeholder="Escribe la labor o sesión extraordinaria que justifica el consumo..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <button 
                    @click="submit" 
                    type="button"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white text-[11px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect"
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                >
                    Guardar Todas las Justificaciones
                </button>
                
                <button @click="emit('close')" type="button" class="w-full justify-center py-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 font-bold uppercase text-[10px] tracking-widest cursor-pointer">
                    Cerrar sin guardar
                </button>
            </div>
        </div>
    </Modal>
</template>
