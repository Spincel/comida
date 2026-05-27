<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    BuildingStorefrontIcon, 
    ArrowLeftIcon, 
    CheckBadgeIcon,
    ClockIcon,
    CalendarDaysIcon,
    MoonIcon,
    SunIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    auth: Object
});

const user = props.auth.user;
const roleName = { 'admin': 'Administrador', 'acquisitions_manager': 'Adquisiciones', 'area_manager': 'Gerente de Área', 'diner': 'Comensal' }[user.role];

const form = useForm({
    name: '',
    address: '',
    contact_person: '',
    contact_phone: '',
    contact_email: '',
    delivery_time_window: '',
});

const submit = () => {
    form.post(route('providers.store'));
};

const currentTime = ref('');
const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('es-MX', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit', 
        hour12: false 
    });
};

const formattedToday = new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const isDarkMode = ref(false);
const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
};

onMounted(() => {
    updateClock();
    const clockInterval = setInterval(updateClock, 1000);
    onUnmounted(() => clearInterval(clockInterval));
});
</script>

<template>
    <Head title="Nuevo Proveedor" />

    <AuthenticatedLayout hide-nav>
        <div class="py-6 bg-[#F8F9FA] dark:bg-gray-950 min-h-screen font-sans transition-colors duration-500">
            <div class="max-w-[95%] mx-auto space-y-8">
                
                <!-- BENTO TOP BAR -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white/40 dark:bg-gray-900/40 backdrop-blur-md p-4 rounded-[2rem] border border-white/60 dark:border-white/10 shadow-sm transition-all">
                    <div class="flex items-center gap-6">
                        <Link :href="route('providers.index')" class="bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-500/20 hover:scale-105 transition-transform">
                            <ArrowLeftIcon class="h-8 w-8 text-white" />
                        </Link>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter">SICOA.</h1>
                                <span class="bg-slate-100 dark:bg-gray-800 text-slate-500 dark:text-gray-400 text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest border border-slate-200 dark:border-gray-700">Proveedores</span>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Registrar Nuevo Establecimiento</p>
                        </div>
                    </div>

                    <!-- CLOCK & DATE -->
                    <div class="flex items-center gap-8">
                        <div class="bg-white/80 dark:bg-gray-800/80 border border-white dark:border-gray-700 p-3 px-8 rounded-3xl shadow-sm flex flex-col items-center min-w-[180px]">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1">Hora Local CDMX</p>
                            <p class="text-3xl font-black text-slate-700 dark:text-white tabular-nums tracking-tighter leading-none">{{ currentTime }}</p>
                        </div>
                        <div class="bg-white/80 dark:bg-gray-800/80 border border-white dark:border-gray-700 p-3 px-8 rounded-3xl shadow-sm flex flex-col items-center">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1">Fecha Sistema</p>
                            <div class="flex items-center gap-2">
                                <CalendarDaysIcon class="h-4 w-4 text-orange-500" />
                                <p class="text-sm font-black text-slate-700 dark:text-gray-300 uppercase whitespace-nowrap">{{ formattedToday }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- USER PROFILE & THEME -->
                    <div class="flex items-center gap-4">
                        <button @click="toggleDarkMode" class="p-3 rounded-2xl bg-white dark:bg-gray-800 border border-slate-100 dark:border-gray-700 text-slate-400 hover:text-indigo-600 transition-all shadow-sm">
                            <MoonIcon v-if="!isDarkMode" class="h-6 w-6" />
                            <SunIcon v-else class="h-6 w-6 text-yellow-500" />
                        </button>
                        
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <div class="flex items-center gap-4 bg-white dark:bg-gray-800 p-2 pr-6 rounded-[2rem] border border-slate-100 dark:border-gray-700 shadow-sm cursor-pointer hover:border-indigo-300 transition-all">
                                    <img :src="user.avatar_url" class="h-12 w-12 rounded-2xl object-cover border-2 border-slate-50 dark:border-gray-700 shadow-md" />
                                    <div class="text-left">
                                        <p class="text-sm font-black text-slate-800 dark:text-white leading-none">{{ user.name }}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ roleName }}</p>
                                    </div>
                                    <ChevronDownIcon class="h-4 w-4 text-slate-300 ml-2" />
                                </div>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('dashboard')">Dashboard</DropdownLink>
                                <DropdownLink :href="route('providers.index')">Proveedores</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-rose-500 font-bold">Cerrar Sesión</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <!-- MAIN CONTENT: PREMIUM FORM -->
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-gray-900 rounded-[3.5rem] shadow-2xl border border-slate-100 dark:border-gray-800 overflow-hidden">
                        <div class="bg-indigo-600 p-12 text-white flex justify-between items-center">
                            <div class="flex items-center gap-8">
                                <div class="h-20 w-20 bg-white/20 rounded-[2rem] flex items-center justify-center backdrop-blur-md border border-white/20 shadow-inner">
                                    <BuildingStorefrontIcon class="h-10 w-10 text-white" />
                                </div>
                                <div>
                                    <h2 class="text-4xl font-black uppercase tracking-tighter leading-none mb-3">Nuevo Proveedor</h2>
                                    <p class="text-[11px] font-bold opacity-70 uppercase tracking-[0.3em]">Carga de datos maestros del sistema</p>
                                </div>
                            </div>
                            <CheckBadgeIcon class="h-12 w-12 opacity-20" />
                        </div>

                        <div class="p-12">
                            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="md:col-span-2">
                                    <InputLabel for="name" value="Nombre Legal o Comercial" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-4" />
                                    <TextInput id="name" type="text" class="w-full !rounded-3xl !py-4 !px-6 border-slate-100 dark:border-gray-800 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 shadow-sm" v-model="form.name" required autofocus placeholder="Ej. Gourmet Industrial S.A." />
                                    <InputError class="mt-2 ml-4" :message="form.errors.name" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="address" value="Dirección Física" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-4" />
                                    <TextInput id="address" type="text" class="w-full !rounded-3xl !py-4 !px-6 border-slate-100 dark:border-gray-800 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 shadow-sm" v-model="form.address" placeholder="Calle, Número, Colonia, Ciudad" />
                                    <InputError class="mt-2 ml-4" :message="form.errors.address" />
                                </div>

                                <div>
                                    <InputLabel for="contact_person" value="Persona de Contacto" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-4" />
                                    <TextInput id="contact_person" type="text" class="w-full !rounded-3xl !py-4 !px-6 border-slate-100 dark:border-gray-800 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 shadow-sm" v-model="form.contact_person" placeholder="Nombre completo" />
                                    <InputError class="mt-2 ml-4" :message="form.errors.contact_person" />
                                </div>

                                <div>
                                    <InputLabel for="contact_phone" value="Teléfono" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-4" />
                                    <TextInput id="contact_phone" type="text" class="w-full !rounded-3xl !py-4 !px-6 border-slate-100 dark:border-gray-800 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 shadow-sm" v-model="form.contact_phone" placeholder="10 dígitos" />
                                    <InputError class="mt-2 ml-4" :message="form.errors.contact_phone" />
                                </div>

                                <div>
                                    <InputLabel for="contact_email" value="Correo Electrónico" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-4" />
                                    <TextInput id="contact_email" type="email" class="w-full !rounded-3xl !py-4 !px-6 border-slate-100 dark:border-gray-800 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 shadow-sm" v-model="form.contact_email" placeholder="proveedor@ejemplo.com" />
                                    <InputError class="mt-2 ml-4" :message="form.errors.contact_email" />
                                </div>

                                <div>
                                    <InputLabel for="delivery_time_window" value="Horario de Entrega" class="text-[10px] font-black uppercase text-slate-400 mb-2 ml-4" />
                                    <TextInput id="delivery_time_window" type="text" class="w-full !rounded-3xl !py-4 !px-6 border-slate-100 dark:border-gray-800 dark:bg-gray-800 dark:text-white focus:ring-indigo-500 shadow-sm" v-model="form.delivery_time_window" placeholder="Ej: 08:00 - 09:00" />
                                    <InputError class="mt-2 ml-4" :message="form.errors.delivery_time_window" />
                                </div>

                                <div class="md:col-span-2 flex items-center justify-between mt-8 bg-slate-50 dark:bg-gray-800/50 p-6 rounded-[2.5rem] border border-slate-100 dark:border-gray-800">
                                    <Link :href="route('providers.index')" class="text-[10px] font-black uppercase text-slate-400 hover:text-rose-500 tracking-widest transition-colors ml-4">
                                        Cancelar Registro
                                    </Link>

                                    <PrimaryButton class="!py-5 !px-12 !rounded-2xl !text-[11px] font-black uppercase tracking-[0.2em] shadow-2xl shadow-indigo-500/20 hover:scale-105 active:scale-95 transition-all" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        Guardar Proveedor
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
