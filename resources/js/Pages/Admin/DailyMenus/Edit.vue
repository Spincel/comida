<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    dailyMenu: Object,
    providers: Array,
});

const form = useForm({
    name: props.dailyMenu.name,
    description: props.dailyMenu.description,
    available_on: props.dailyMenu.available_on,
    provider_id: props.dailyMenu.provider_id,
});

const submit = () => {
    form.put(route('daily-menus.update', props.dailyMenu.id));
};
</script>

<template>
    <Head :title="`Editar Platillo: ${dailyMenu.name}`" />

    <AuthenticatedLayout bento-tag="Catálogos">
        <div class="max-w-3xl mx-auto space-y-8">
            <!-- HEADER -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-2xl shadow-xs">
                        🍳
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Editar Platillo</h2>
                        <p class="text-[10px] font-bold text-tinto-700 dark:text-oro-400 uppercase tracking-widest truncate max-w-sm">{{ dailyMenu.name }}</p>
                    </div>
                </div>
                <Link :href="route('daily-menus.index')" class="px-5 py-2.5 bg-slate-50 dark:bg-gray-800 text-slate-600 dark:text-gray-300 hover:text-tinto-700 dark:hover:text-oro-400 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-200 dark:border-gray-700 transition-all cursor-pointer">
                    ← Volver
                </Link>
            </div>

            <!-- FORM CARD -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-10 shadow-[0_10px_35px_-5px_rgba(120,24,42,0.06)] dark:shadow-none border border-slate-200/80 dark:border-gray-800">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="provider_id" value="Establecimiento / Proveedor" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2 mb-2" />
                        <select
                            id="provider_id"
                            class="w-full bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl py-4 px-6 text-xs font-bold uppercase text-slate-700 dark:text-gray-200 focus:ring-2 focus:ring-tinto-700 shadow-inner"
                            v-model="form.provider_id"
                            required
                        >
                            <option value="" disabled>Selecciona un proveedor</option>
                            <option v-for="provider in providers" :key="provider.id" :value="provider.id">
                                {{ provider.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.provider_id" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Nombre del Platillo" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2 mb-2" />
                        <TextInput
                            id="name"
                            type="text"
                            class="w-full !rounded-2xl !py-4 !px-6 text-xs font-bold uppercase"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="off"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Descripción / Guarniciones (Opcional)" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2 mb-2" />
                        <TextInput
                            id="description"
                            type="text"
                            class="w-full !rounded-2xl !py-4 !px-6 text-xs font-medium"
                            v-model="form.description"
                            autocomplete="off"
                        />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div>
                        <InputLabel for="available_on" value="Fecha Disponible" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2 mb-2" />
                        <TextInput
                            id="available_on"
                            type="date"
                            class="w-full !rounded-2xl !py-4 !px-6 text-xs font-bold"
                            v-model="form.available_on"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.available_on" />
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100 dark:border-gray-800">
                        <Link :href="route('daily-menus.index')" class="px-6 py-4 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600">
                            Cancelar
                        </Link>
                        <button type="submit" 
                                class="px-8 py-4 bg-gradient-to-r from-tinto-900 via-tinto-800 to-tinto-900 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest shadow-tinto-sm border border-oro-400/40 hover:scale-105 active:scale-95 transition-all cursor-pointer shine-effect"
                                :disabled="form.processing">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>