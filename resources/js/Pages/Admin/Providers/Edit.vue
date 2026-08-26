<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    BuildingStorefrontIcon, 
    ArrowLeftIcon, 
    CheckBadgeIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    provider: Object,
    auth: Object
});

const form = useForm({
    name: props.provider.name,
    address: props.provider.address,
    contact_person: props.provider.contact_person,
    contact_phone: props.provider.contact_phone,
    contact_email: props.provider.contact_email,
    delivery_time_window: props.provider.delivery_time_window,
});

const submit = () => {
    form.put(route('providers.update', props.provider.id));
};
</script>

<template>
    <Head :title="`Editar Proveedor: ${provider.name}`" />

    <AuthenticatedLayout bento-tag="Gestión">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- HEADER -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        👨‍🍳
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Editar Expediente</h2>
                        <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest truncate max-w-sm">{{ provider.name }}</p>
                    </div>
                </div>
                <Link :href="route('providers.index')" class="px-5 py-2.5 bg-slate-50 dark:bg-gray-800 text-slate-600 dark:text-gray-300 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-200 dark:border-gray-700 transition-all cursor-pointer">
                    ← Volver
                </Link>
            </div>

            <!-- FORM CARD -->
            <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 overflow-hidden">
                <div class="bg-gradient-to-r from-tinto-950 via-tinto-900 to-tinto-950 p-8 text-white flex justify-between items-center border-b border-oro-500/30">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 bg-white/10 rounded-2xl flex items-center justify-center border border-white/20 text-oro-300">
                            <BuildingStorefrontIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black uppercase tracking-tight">Modificar Datos de Establecimiento</h3>
                            <p class="text-[10px] font-bold text-oro-300 uppercase tracking-widest">Padrón de proveedores activos</p>
                        </div>
                    </div>
                    <ShieldCheckIcon class="h-8 w-8 text-oro-400 opacity-60" />
                </div>

                <div class="p-10">
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <InputLabel for="name" value="Nombre Legal o Comercial" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2" />
                            <TextInput id="name" type="text" class="w-full !rounded-2xl !py-4 !px-6 text-xs font-bold uppercase" v-model="form.name" required autofocus placeholder="Ej. Restaurante Las Cazuelas" />
                            <InputError class="mt-2 ml-2" :message="form.errors.name" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="address" value="Dirección Física" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2" />
                            <TextInput id="address" type="text" class="w-full !rounded-2xl !py-4 !px-6 text-xs font-medium" v-model="form.address" placeholder="Calle, Número, Colonia, Tepic, Nayarit" />
                            <InputError class="mt-2 ml-2" :message="form.errors.address" />
                        </div>

                        <div>
                            <InputLabel for="contact_person" value="Persona de Contacto" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2" />
                            <TextInput id="contact_person" type="text" class="w-full !rounded-2xl !py-4 !px-6 text-xs font-bold" v-model="form.contact_person" placeholder="Nombre completo" />
                            <InputError class="mt-2 ml-2" :message="form.errors.contact_person" />
                        </div>

                        <div>
                            <InputLabel for="contact_phone" value="Teléfono de Contacto" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2" />
                            <TextInput id="contact_phone" type="text" class="w-full !rounded-2xl !py-4 !px-6 text-xs font-bold" v-model="form.contact_phone" placeholder="311 XXX XXXX" />
                            <InputError class="mt-2 ml-2" :message="form.errors.contact_phone" />
                        </div>

                        <div>
                            <InputLabel for="contact_email" value="Correo Electrónico" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2" />
                            <TextInput id="contact_email" type="email" class="w-full !rounded-2xl !py-4 !px-6 text-xs font-medium lowercase" v-model="form.contact_email" placeholder="contacto@restaurante.com" />
                            <InputError class="mt-2 ml-2" :message="form.errors.contact_email" />
                        </div>

                        <div>
                            <InputLabel for="delivery_time_window" value="Horario de Entrega Habitual" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2" />
                            <TextInput id="delivery_time_window" type="text" class="w-full !rounded-2xl !py-4 !px-6 text-xs font-bold" v-model="form.delivery_time_window" placeholder="Ej: 08:30 - 09:30 hrs" />
                            <InputError class="mt-2 ml-2" :message="form.errors.delivery_time_window" />
                        </div>

                        <div class="md:col-span-2 flex items-center justify-between mt-6 pt-6 border-t border-slate-100 dark:border-gray-800">
                            <Link :href="route('providers.index')" class="text-[10px] font-black uppercase text-slate-400 hover:text-rose-600 tracking-widest transition-colors ml-2 cursor-pointer">
                                Cancelar
                            </Link>

                            <button type="submit" 
                                    class="py-4 px-10 bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect" 
                                    :disabled="form.processing">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
