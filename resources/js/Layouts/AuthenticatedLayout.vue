<script setup>
import { ref, watch, onUnmounted, onMounted, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeSelectorModal from '@/Pages/Admin/Partials/ThemeSelectorModal.vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import { 
    Bars3Icon, 
    SunIcon, 
    MoonIcon, 
    ChevronDownIcon,
    UsersIcon,
    BuildingOfficeIcon,
    SwatchIcon,
    TableCellsIcon,
    ShieldCheckIcon,
    ClipboardDocumentIcon,
    BriefcaseIcon,
    WrenchScrewdriverIcon,
    BuildingStorefrontIcon,
    CloudArrowUpIcon,
    DocumentChartBarIcon,
    PaintBrushIcon,
    ComputerDesktopIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const showingNavigationDropdown = ref(false);
const page = usePage();
const visibleFlash = ref(null);
let flashTimeout = null;

const user = computed(() => page.props.auth.user);
const backgroundCatalog = computed(() => JSON.parse(page.props.system?.settings?.background_catalog || '[]'));
const dynamicAppName = computed(() => page.props.system?.settings?.app_name || 'Comedor System');

// Dark Mode logic
const isDarkMode = ref(false);
const showThemeSelector = ref(false);

const applyThemeMode = (mode) => {
    if (mode === 'dark') {
        document.documentElement.classList.add('dark');
        isDarkMode.value = true;
    } else if (mode === 'light') {
        document.documentElement.classList.remove('dark');
        isDarkMode.value = false;
    } else {
        // System
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (systemPrefersDark) document.documentElement.classList.add('dark');
        else document.documentElement.classList.remove('dark');
        isDarkMode.value = systemPrefersDark;
    }
};

watch(() => user.value?.theme_settings, (settings) => {
    if (settings?.theme_mode) {
        applyThemeMode(settings.theme_mode);
    }
}, { immediate: true, deep: true });

onMounted(() => {
    const settings = user.value?.theme_settings;
    if (settings?.theme_mode) {
        applyThemeMode(settings.theme_mode);
    } else {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            isDarkMode.value = true;
            document.documentElement.classList.add('dark');
        } else {
            isDarkMode.value = false;
            document.documentElement.classList.remove('dark');
        }
    }
});

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

watch(() => page.props.flash?.success, (newMsg) => {
    if (newMsg) {
        visibleFlash.value = newMsg;
        if (flashTimeout) clearTimeout(flashTimeout);
        flashTimeout = setTimeout(() => {
            visibleFlash.value = null;
        }, 3000);
    }
}, { immediate: true });

onUnmounted(() => {
    if (flashTimeout) clearTimeout(flashTimeout);
});
</script>

<template>
    <div class="relative min-h-screen overflow-hidden">
        <Head :title="dynamicAppName" />
        <!-- BACKGROUND LAYER GLOBAL -->
        <div v-if="user?.theme_settings?.background_url" class="fixed inset-0 z-0 transition-opacity duration-1000">
            <img :src="user.theme_settings.background_url" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-white/20 dark:bg-black/40 backdrop-blur-[2px]"></div>
        </div>

        <div class="relative z-10 min-h-screen flex flex-col transition-all duration-700"
             :class="[
                user?.theme_settings?.background_url 
                    ? 'bg-transparent with-custom-bg' 
                    : 'bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950/20'
             ]">
            <nav
                class="border-b border-gray-200 dark:border-gray-700 transition-colors duration-300 sticky top-0 z-50"
                :class="user?.theme_settings?.background_url ? 'bg-white/40 dark:bg-gray-900/40 backdrop-blur-2xl' : 'bg-gray-50/80 dark:bg-gray-800/80 backdrop-blur-xl'"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-[85%] px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                    class="!text-emerald-600"
                                    :class="{ '!border-emerald-500': route().current('dashboard') }"
                                >
                                    Inicio
                                </NavLink>

                                <!-- Admin & Acquisitions Dropdown -->
                                <div v-if="['admin', 'acquisitions_manager'].includes($page.props.auth.user.role)" class="hidden sm:flex sm:items-center sm:ms-6">
                                    <Dropdown align="left" width="64">
                                        <template #trigger>
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-indigo-700 dark:hover:text-indigo-400 focus:outline-none transition ease-in-out duration-150">
                                                <BriefcaseIcon class="h-4 w-4 mr-2" />
                                                Adquisiciones
                                                <ChevronDownIcon class="ms-2 -me-0.5 h-4 w-4" />
                                            </button>
                                        </template>
                                        <template #content>
                                            <div class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b dark:border-gray-700">Gestión de Servicio</div>
                                            <DropdownLink :href="route('admin.history')">
                                                <div class="flex items-center"><ClipboardDocumentIcon class="h-4 w-4 mr-2" /> Historial General</div>
                                            </DropdownLink>
                                            <DropdownLink v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.sessions.logs')">
                                                <div class="flex items-center"><ShieldCheckIcon class="h-4 w-4 mr-2 text-rose-500" /> Auditoría de Sesiones</div>
                                            </DropdownLink>
                                            <DropdownLink :href="route('admin.reports')">
                                                <div class="flex items-center"><TableCellsIcon class="h-4 w-4 mr-2" /> Reportes Generales</div>
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Admin Specialized Tools -->
                                <div v-if="$page.props.auth.user.role === 'admin'" class="hidden sm:flex sm:items-center sm:ms-6">
                                    <Dropdown align="left" width="64">
                                        <template #trigger>
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-indigo-700 dark:hover:text-indigo-400 focus:outline-none transition ease-in-out duration-150">
                                                <WrenchScrewdriverIcon class="h-4 w-4 mr-2" />
                                                Administración
                                                <ChevronDownIcon class="ms-2 -me-0.5 h-4 w-4" />
                                            </button>
                                        </template>
                                        <template #content>
                                            <div class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b dark:border-gray-700">Configuración Base</div>
                                            <DropdownLink :href="route('users.index')">
                                                <div class="flex items-center"><UsersIcon class="h-4 w-4 mr-2" /> Usuarios</div>
                                            </DropdownLink>
                                            <DropdownLink :href="route('areas.index')">
                                                <div class="flex items-center"><BuildingOfficeIcon class="h-4 w-4 mr-2" /> Áreas</div>
                                            </DropdownLink>
                                            <div class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-t border-b dark:border-gray-700 mt-2">Sistema</div>
                                            <DropdownLink :href="route('admin.settings.interface')">
                                                <div class="flex items-center"><SwatchIcon class="h-4 w-4 mr-2" /> Interfaz y Logos</div>
                                            </DropdownLink>
                                            <DropdownLink :href="route('admin.settings.reports')">
                                                <div class="flex items-center"><DocumentChartBarIcon class="h-4 w-4 mr-2" /> Configurar Reportes</div>
                                            </DropdownLink>
                                            <DropdownLink :href="route('admin.settings.roles')">
                                                <div class="flex items-center"><ShieldCheckIcon class="h-4 w-4 mr-2" /> Roles y Permisos</div>
                                            </DropdownLink>
                                            <DropdownLink :href="route('admin.utilities.data')">
                                                <div class="flex items-center"><CloudArrowUpIcon class="h-4 w-4 mr-2" /> Mantenimiento de Datos</div>
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Area Manager & Diner Context -->
                                <template v-if="['area_manager', 'diner', 'acquisitions_manager'].includes($page.props.auth.user.role) || ($page.props.auth.user.role === 'admin' && $page.props.auth.user.area_id)">
                                    <NavLink v-if="['area_manager', 'admin'].includes($page.props.auth.user.role)" :href="route('team.index')" :active="route().current('team.*')">
                                        Mi Plantilla
                                    </NavLink>
                                    <NavLink :href="route('justification.index')" :active="route().current('justification.*')" :class="{ '!text-rose-600 !border-rose-500': route().current('justification.*') }">
                                        Historial / Justificar
                                    </NavLink>
                                    <NavLink :href="route('daily.summary')" :active="route().current('daily.summary')" :class="{ '!text-indigo-600 !border-indigo-500': route().current('daily.summary') }">
                                        {{ ['area_manager', 'admin'].includes($page.props.auth.user.role) ? 'Estadísticas de Área' : 'Resumen Diario' }}
                                    </NavLink>
                                </template>
                            </div>
                        </div>

                        <!-- Right Side Navigation -->
                        <div class="flex items-center space-x-2 sm:ms-6">
                            <!-- Report Settings Icon (Admin, Manager, Acquisitions) -->
                            <Link 
                                v-if="['admin', 'area_manager', 'acquisitions_manager'].includes($page.props.auth.user.role)"
                                :href="route('admin.settings.reports')"
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200"
                                title="Configurar Reportes"
                            >
                                <DocumentChartBarIcon class="h-5 w-5" />
                            </Link>

                            <!-- Providers Button (Acquisitions & Admin) -->
                            <Link 
                                v-if="$page.props.auth.user.role === 'acquisitions_manager' || $page.props.auth.user.role === 'admin'"
                                :href="route('providers.index')"
                                class="hidden sm:inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-2"
                            >
                                Proveedores/Catálogo
                            </Link>

                            <!-- Theme Customizer Button -->
                            <button
                                @click="showThemeSelector = true"
                                type="button"
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none"
                                title="Personalizar Fondo y Tema"
                            >
                                <PaintBrushIcon class="h-5 w-5" />
                            </button>

                            <!-- Theme Toggle Button -->
                            <button
                                @click="toggleDarkMode"
                                type="button"
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none"
                                :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                            >
                                <SunIcon v-if="isDarkMode" class="h-5 w-5 text-yellow-500" />
                                <MoonIcon v-else class="h-5 w-5 text-indigo-600" />
                            </button>

                            <!-- Status Indicator -->
                            <div v-if="$page.props.auth.orderStatus" 
                                 class="h-3 w-3 rounded-full animate-pulse ml-2"
                                 :class="{
                                     'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]': $page.props.auth.orderStatus === 'red',
                                     'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]': $page.props.auth.orderStatus === 'amber',
                                     'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]': $page.props.auth.orderStatus === 'green'
                                 }">
                            </div>

                            <!-- User Dropdown (Avatar Style) -->
                            <div class="relative ml-2 flex items-center">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center p-1 rounded-full border-2 border-transparent hover:border-indigo-300 dark:hover:border-indigo-500 transition duration-150 ease-in-out focus:outline-none"
                                            >
                                                <img :src="$page.props.auth.user.avatar_url" class="h-8 w-8 rounded-full shadow-sm" :alt="$page.props.auth.user.name" />
                                                <ChevronDownIcon class="ml-1 h-3 w-3 text-gray-400" />
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="block px-4 py-2 text-xs text-gray-400 border-b dark:border-gray-700">
                                            {{ $page.props.auth.user.name }}
                                        </div>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Perfil
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Cerrar Sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Mobile Hamburger -->
                            <div class="-me-2 flex items-center sm:hidden">
                                <button
                                    @click="showingNavigationDropdown = !showingNavigationDropdown"
                                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                                >
                                    <Bars3Icon v-if="!showingNavigationDropdown" class="h-6 w-6" />
                                    <svg v-else class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                            class="!text-emerald-600 font-black"
                        >
                            Inicio
                        </ResponsiveNavLink>

                        <!-- Mobile Acquisitions Group -->
                        <template v-if="['admin', 'acquisitions_manager'].includes($page.props.auth.user.role)">
                            <div class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b dark:border-gray-700">Adquisiciones</div>
                            <ResponsiveNavLink :href="route('admin.history')" :active="route().current('admin.history')">Historial General</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.sessions.logs')" :active="route().current('admin.sessions.logs')" class="text-rose-500">Auditoría de Sesiones</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.reports')" :active="route().current('admin.reports')">Reportes Generales</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.settings.reports')" :active="route().current('admin.settings.reports')">Configurar Reportes</ResponsiveNavLink>
                        </template>

                        <!-- Mobile Admin Tools -->
                        <template v-if="$page.props.auth.user.role === 'admin'">
                            <div class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b dark:border-gray-700 mt-2">Administración</div>
                            <ResponsiveNavLink :href="route('users.index')" :active="route().current('users.*')">Usuarios</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('areas.index')" :active="route().current('areas.*')">Áreas</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.settings.interface')" :active="route().current('admin.settings.interface')">Interfaz y Logos</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.settings.roles')" :active="route().current('admin.settings.roles')">Roles y Permisos</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.utilities.data')" :active="route().current('admin.utilities.data')">Mantenimiento de Datos</ResponsiveNavLink>
                        </template>

                                <!-- Mobile Area Manager & Diner Justification -->
                        <template v-if="['area_manager', 'diner', 'acquisitions_manager'].includes($page.props.auth.user.role) || ($page.props.auth.user.role === 'admin' && $page.props.auth.user.area_id)">
                            <div class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b dark:border-gray-700 mt-2">Mi Área / Actividad</div>
                            <ResponsiveNavLink :href="route('justification.index')" :active="route().current('justification.*')" class="text-rose-600 font-bold">Historial / Justificar</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="['admin', 'area_manager'].includes($page.props.auth.user.role)" :href="route('admin.settings.reports')" :active="route().current('admin.settings.reports')">Configurar Reportes</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('daily.summary')" :active="route().current('daily.summary')" class="text-indigo-600 font-bold">Resumen Diario</ResponsiveNavLink>
                        </template>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600"
                    >
                        <div class="px-4 flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="relative">
                                    <img :src="$page.props.auth.user.avatar_url" class="h-10 w-10 rounded-full border-2 border-indigo-200 shadow-sm mr-3" :alt="$page.props.auth.user.name" />
                                    <!-- Mobile Status Dot -->
                                    <div v-if="$page.props.auth.orderStatus" 
                                         class="absolute -top-1 -right-1 h-3 w-3 rounded-full border-2 border-white dark:border-gray-800 animate-pulse"
                                         :class="{
                                             'bg-red-500': $page.props.auth.orderStatus === 'red',
                                             'bg-amber-500': $page.props.auth.orderStatus === 'amber',
                                             'bg-green-500': $page.props.auth.orderStatus === 'green'
                                         }">
                                    </div>
                                </div>
                                <div>
                                    <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                                        {{ $page.props.auth.user.name }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-500">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                </div>
                            </div>
                            <!-- Mobile Theme Toggle -->
                            <button @click="toggleDarkMode" class="p-2 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300">
                                <SunIcon v-if="isDarkMode" class="h-5 w-5" />
                                <MoonIcon v-else class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user.role === 'acquisitions_manager' || $page.props.auth.user.role === 'admin'"
                                :href="route('providers.index')"
                                :active="route().current('providers.*')"
                            >
                                Proveedores/Catálogo
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Perfil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Cerrar Sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform opacity-0 -translate-y-2"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 -translate-y-2"
            >
                <div v-if="visibleFlash"
                     class="mx-auto max-w-[85%] px-4 sm:px-6 lg:px-8 mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm">
                    {{ visibleFlash }}
                </div>
            </transition>

            <!-- Page Heading -->
            <header
                class="bg-white shadow dark:bg-gray-800 transition-colors duration-300"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-[85%] px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="pb-20">
                <slot />
            </main>

            <!-- Institutional Footer -->
            <footer class="bg-gray-50/80 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 backdrop-blur-xl py-12 transition-colors duration-300">
                <div class="mx-auto max-w-[85%] px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-4">
                        <ApplicationLogo class="h-8 w-auto opacity-50 grayscale" />
                        <div>
                            <p class="text-[11px] font-black text-gray-800 dark:text-gray-200 uppercase tracking-[0.2em]">{{ page.props.system?.settings?.footer_title || 'Sistema Integral de Comedor' }}</p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">{{ page.props.system?.settings?.footer_subtitle || 'Gestión Administrativa y Control de Alimentos' }}</p>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">{{ page.props.system?.settings?.footer_brand || 'UTICS ® Marca Registrada' }}</p>
                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-[0.3em] mt-2">© {{ page.props.system?.settings?.footer_year || '2026' }} Todos los derechos reservados</p>
                    </div>
                </div>
            </footer>
        </div>

        <ThemeSelectorModal 
            :show="showThemeSelector" 
            @close="showThemeSelector = false"
            :currentSettings="user?.theme_settings"
            :catalog="backgroundCatalog"
        />
    </div>
</template>

<style>
/* Atmosphere 2.0 - Inspired by propuesta.png */
.with-custom-bg .bg-gray-50,
.with-custom-bg .bg-slate-50,
.with-custom-bg .bg-indigo-50,
.with-custom-bg .bg-gray-100 {
    background-color: transparent !important;
}

.with-custom-bg .dark .bg-gray-900,
.with-custom-bg .dark .bg-gray-950,
.with-custom-bg .dark .bg-slate-900 {
    background-color: transparent !important;
}

/* Glassmorphism only for Header and Nav */
.with-custom-bg nav {
    background-color: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(20px) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
}

.with-custom-bg .dark nav {
    background-color: rgba(15, 23, 42, 0.7) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* Solid Cards with depth - As seen in the reference image */
.with-custom-bg .bg-white:not(nav) {
    background-color: rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}

.with-custom-bg .dark .bg-gray-800:not(nav) {
    background-color: rgba(31, 41, 55, 0.95) !important;
}

/* Main Content Wrapper */
.with-custom-bg main {
    background-color: transparent !important;
}

/* Scrollbar styling */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
</style>
