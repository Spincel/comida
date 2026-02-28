<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    provider: Object,
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

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Proveedor: {{ provider.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div>
                                <InputLabel for="name" value="Nombre del Proveedor" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="organization"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="address" value="Domicilio" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.address"
                                    autocomplete="street-address"
                                />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="contact_person" value="Persona de Contacto (Opcional)" />
                                <TextInput
                                    id="contact_person"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.contact_person"
                                    autocomplete="name"
                                />
                                <InputError class="mt-2" :message="form.errors.contact_person" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="contact_phone" value="Teléfono de Contacto (Opcional)" />
                                <TextInput
                                    id="contact_phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.contact_phone"
                                    autocomplete="tel"
                                />
                                <InputError class="mt-2" :message="form.errors.contact_phone" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="contact_email" value="Email de Contacto (Opcional)" />
                                <TextInput
                                    id="contact_email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.contact_email"
                                    autocomplete="email"
                                />
                                <InputError class="mt-2" :message="form.errors.contact_email" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="delivery_time_window" value="Franja Horaria de Entrega (Opcional)" />
                                <TextInput
                                    id="delivery_time_window"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.delivery_time_window"
                                    placeholder="Ej: 12:00 - 13:00"
                                    autocomplete="off"
                                />
                                <InputError class="mt-2" :message="form.errors.delivery_time_window" />
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <Link :href="route('providers.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
