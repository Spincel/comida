<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const showCropper = ref(false);
const imageSrc = ref(null);
const cropperRef = ref(null);

const form = useForm({
    name: user.name,
    email: user.email,
    avatar: null,
});

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            imageSrc.value = event.target.result;
            showCropper.value = true;
        };
        reader.readAsDataURL(file);
    }
};

const cropImage = () => {
    const { canvas } = cropperRef.value.getResult();
    if (canvas) {
        canvas.toBlob((blob) => {
            const file = new File([blob], "avatar.jpg", { type: "image/jpeg" });
            form.avatar = file;
            showCropper.value = false;
        }, 'image/jpeg');
    }
};

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.avatar = null;
            imageSrc.value = null;
        }
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Información del Perfil
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Actualiza tu información básica y tu fotografía de perfil.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="avatar" value="Fotografía de Perfil" />
                <div class="mt-2 flex items-center gap-6">
                    <div class="h-24 w-24 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 border-4 border-white dark:border-slate-700 shadow-xl flex items-center justify-center">
                        <img v-if="user.avatar" :src="user.avatar" class="h-full w-full object-cover" />
                        <span v-else class="text-indigo-500 font-bold text-3xl">{{ user.name.charAt(0) }}</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <input id="avatar" type="file" class="hidden" ref="fileInput" @change="onFileChange" accept="image/*" />
                        <SecondaryButton @click.prevent="$refs.fileInput.click()">
                            Seleccionar Imagen
                        </SecondaryButton>
                    </div>
                </div>
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <!-- Modal simple para evitar conflictos de Teleport/Recursión -->
            <div v-if="showCropper" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 overflow-hidden">
                    <h3 class="text-lg font-bold mb-4 text-center dark:text-white">Ajusta tu fotografía</h3>
                    <div class="h-80 bg-gray-100 rounded-xl overflow-hidden relative">
                        <cropper
                            ref="cropperRef"
                            class="h-full w-full"
                            :src="imageSrc"
                            :stencil-props="{
                                aspectRatio: 1/1,
                                handlers: {},
                                movable: true,
                                resizable: true,
                            }"
                            image-restriction="stencil"
                        />
                    </div>
                    <div class="mt-6 flex gap-3">
                        <SecondaryButton @click="showCropper = false" class="flex-1 justify-center">Cancelar</SecondaryButton>
                        <PrimaryButton @click.prevent="cropImage" class="flex-1 justify-center bg-indigo-600">Aplicar</PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Resto de los campos en español -->
            <div>
                <InputLabel for="name" value="Nombre Completo" />
                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Guardar Cambios</PrimaryButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Guardado correctamente.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>