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
    <Head :title="`Editar Menú: ${dailyMenu.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Menú: {{ dailyMenu.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- Provider Select -->
                            <div class="mt-4">
                                <InputLabel for="provider_id" value="Proveedor" />
                                <select
                                    id="provider_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
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
                                <InputLabel for="name" value="Nombre del Plato" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="off"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="description" value="Descripción (Opcional)" />
                                <TextInput
                                    id="description"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.description"
                                    autocomplete="off"
                                />
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="available_on" value="Fecha del Menú" />
                                <TextInput
                                    id="available_on"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.available_on"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.available_on" />
                            </div>
                            <div class="flex items-center justify-end mt-6">
                                <Link :href="route('daily-menus.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Cancelar
                                </Link>

                                <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Guardar Cambios
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>