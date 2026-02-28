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
        <div class="p-8">
            <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100 mb-2 uppercase tracking-tight">
                Justificación de Actividades
            </h2>
            <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-6">
                {{ session?.meal_type }} - {{ session?.date }} - {{ session?.provider_name }}
            </p>

            <div class="space-y-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar mb-8">
                <div v-for="(item, index) in form.justifications" :key="item.id" class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="font-black text-sm text-gray-800 dark:text-gray-200">{{ item.user_name }}</p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">{{ item.platillo }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <InputLabel :for="'act_' + item.id" value="Actividad Realizada / Justificación" class="text-[10px] uppercase font-black text-indigo-500 mb-1" />
                        <textarea
                            :id="'act_' + item.id"
                            v-model="form.justifications[index].activity_performed"
                            rows="2"
                            class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="Escribe la actividad que justifica el alimento..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <PrimaryButton 
                    @click="submit" 
                    class="w-full justify-center py-4 text-base font-black uppercase tracking-widest"
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                >
                    Guardar Todas las Justificaciones
                </PrimaryButton>
                
                <SecondaryButton @click="emit('close')" class="w-full justify-center py-3 border-none shadow-none text-gray-400 hover:text-gray-600 font-bold uppercase text-[10px] tracking-widest">
                    Cerrar sin guardar
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
