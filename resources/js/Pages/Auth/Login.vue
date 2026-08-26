<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión - SICOA" />

        <div v-if="status" class="mb-4 text-xs font-bold text-nayarit-700 dark:text-emerald-400 p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="login" value="Correo, Usuario o No. Empleado" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2 tracking-widest" />
                <TextInput
                    id="login"
                    type="text"
                    class="w-full !rounded-2xl !py-3.5 !px-5 text-xs font-bold"
                    v-model="form.login"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="ejemplo@congresonay.gob.mx"
                />
                <InputError class="mt-2 ml-2" :message="form.errors.login" />
            </div>

            <div>
                <InputLabel for="password" value="Contraseña" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-2 tracking-widest" />
                <TextInput
                    id="password"
                    type="password"
                    class="w-full !rounded-2xl !py-3.5 !px-5 text-xs font-bold"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-2 ml-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-tinto-800 focus:ring-tinto-700 !rounded-lg" />
                    <span class="ms-2 text-xs font-bold text-slate-600 dark:text-gray-400">Recordarme</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-[11px] font-bold text-tinto-700 dark:text-oro-400 hover:underline"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <div class="pt-4">
                <PrimaryButton
                    class="w-full justify-center !py-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Ingresar al Sistema
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>