<script setup>
import { ref, onMounted, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import Modal from '@/Components/Modal.vue';
import QrcodeVue from 'qrcode.vue';
import { Link, usePage } from '@inertiajs/vue3';

const isSidebarOpen = ref(localStorage.getItem('sidebarOpen') === 'true');
const page = usePage();
const user = computed(() => page.props.auth.user);
const settings = computed(() => page.props.settings);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    localStorage.setItem('sidebarOpen', isSidebarOpen.value);
};

const openSidebar = () => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        localStorage.setItem('sidebarOpen', 'true');
    }
};

const toggleTheme = () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
};

const showVirtualCredentialModal = ref(false);

const openVirtualCredential = () => {
    showVirtualCredentialModal.value = true;
};

const defaultLayout = {
    name: { top: '45%', left: '50%', fontSize: '22px', color: '#000000', fontWeight: '700', textAlign: 'center' },
    department: { top: '58%', left: '50%', fontSize: '14px', color: '#374151', fontWeight: '400', textAlign: 'center' },
    employee_number: { top: '70%', left: '50%', fontSize: '16px', color: '#1d4ed8', fontWeight: '600', textAlign: 'center' },
    qr_code: { top: '85%', left: '50%', size: '80' },
    avatar: { top: '22%', left: '50%', size: '100' },
};

const credentialLayout = computed(() => {
    try {
        const savedLayout = JSON.parse(settings.value.credential_layout);
        // Deep merge with defaults to ensure all keys exist
        return {
            name: { ...defaultLayout.name, ...savedLayout.name },
            department: { ...defaultLayout.department, ...savedLayout.department },
            employee_number: { ...defaultLayout.employee_number, ...savedLayout.employee_number },
            qr_code: { ...defaultLayout.qr_code, ...savedLayout.qr_code },
            avatar: { ...defaultLayout.avatar, ...savedLayout.avatar },
        };
    } catch (e) {
        return defaultLayout;
    }
});


onMounted(() => {
    if (localStorage.getItem('theme') === 'dark' || 
        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});

const linkClass = (active, colorClass) => {
    if (active) {
        return `${colorClass} text-white shadow-lg scale-[1.02]`;
    }
    return 'text-slate-400 hover:bg-slate-800 hover:text-white';
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-950 flex flex-col md:flex-row overflow-hidden">
        
        <!-- Sidebar -->
        <aside 
            @click="openSidebar"
            :class="[
                isSidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full w-64 md:translate-x-0 md:w-20',
                'bg-slate-900 border-r border-slate-800 transition-all duration-300 ease-in-out z-40 fixed inset-y-0 left-0 md:relative md:inset-auto md:h-screen flex flex-col shadow-2xl overflow-hidden shrink-0'
            ]"
        >
            <div :class="isSidebarOpen ? 'justify-between' : 'justify-center'" class="p-4 flex items-center h-16 shrink-0 border-b border-slate-800 transition-all duration-300">
                <span v-show="isSidebarOpen" class="font-bold text-white text-xs uppercase tracking-widest whitespace-nowrap overflow-hidden">Menú Principal</span>
                <button @click.stop="toggleSidebar" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-800 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6 custom-scrollbar">
                <nav class="flex flex-col gap-1">
                    <p v-show="isSidebarOpen" class="px-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 transition-opacity">General</p>
                    <Link :href="route('dashboard')" :class="[linkClass(route().current('dashboard'), 'bg-indigo-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3V14h6v6h3a1 1 0 001-1V10M9 21V9a1 1 0 011-1h2a1 1 0 011 1v12" /></svg>
                        <span v-show="isSidebarOpen">Panel Inicio</span>
                    </Link>
                    <Link v-if="['admin', 'super_admin', 'boss'].includes(user.role)" :href="route('reports.admin')" :class="[linkClass(route().current('reports.admin'), 'bg-blue-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <span v-show="isSidebarOpen">Bitácora Gral.</span>
                    </Link>
                    <Link v-if="['admin', 'super_admin'].includes(user.role)" :href="route('reports.attendance')" :class="[linkClass(route().current('reports.attendance'), 'bg-cyan-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span v-show="isSidebarOpen">Checador</span>
                    </Link>
                </nav>

                <nav v-if="['admin', 'super_admin'].includes(user.role)" class="flex flex-col gap-1 pt-6 border-t border-slate-800">
                    <p v-show="isSidebarOpen" class="px-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 transition-opacity">Catálogos</p>
                    <Link :href="route('admin.catalogs', {tab: 'departments'})" :class="[linkClass($page.url.includes('tab=departments'), 'bg-emerald-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        <span v-show="isSidebarOpen">Áreas/Deptos</span>
                    </Link>
                    <Link :href="route('admin.catalogs', {tab: 'users'})" :class="[linkClass($page.url.includes('tab=users'), 'bg-teal-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <span v-show="isSidebarOpen">Plantilla</span>
                    </Link>
                    <Link :href="route('admin.catalogs', {tab: 'logos'})" :class="[linkClass($page.url.includes('tab=logos'), 'bg-fuchsia-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span v-show="isSidebarOpen">Configuración</span>
                    </Link>
                    <template v-if="user.role === 'super_admin'">
                        <Link :href="route('admin.credential-editor')" :class="[linkClass(route().current('admin.credential-editor'), 'bg-pink-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            <span v-show="isSidebarOpen">Diseño Credencial</span>
                        </Link>
                        <Link :href="route('admin.catalogs', {tab: 'passes'})" :class="[linkClass($page.url.includes('tab=passes'), 'bg-violet-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                            <span v-show="isSidebarOpen">Gestión Pases</span>
                        </Link>
                        <Link :href="route('admin.catalogs', {tab: 'system'})" :class="[linkClass($page.url.includes('tab=system'), 'bg-rose-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span v-show="isSidebarOpen">Modo Dios</span>
                        </Link>
                        <Link :href="route('admin.attendance.monitor')" :class="[linkClass(route().current('admin.attendance.monitor'), 'bg-amber-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span v-show="isSidebarOpen">Monitor Asistencia</span>
                        </Link>
                    </template>
                </nav>

                <nav class="flex flex-col gap-1 pt-6 border-t border-slate-800">
                    <p v-show="isSidebarOpen" class="px-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 transition-opacity">Reportes</p>
                    <Link :href="route('reports.employee')" :class="[linkClass(route().current('reports.employee'), 'bg-orange-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <span v-show="isSidebarOpen">Mis Registros</span>
                    </Link>
                    <Link v-if="['admin', 'super_admin', 'boss'].includes(user.role)" :href="route('reports.admin')" :class="[linkClass(route().current('reports.admin'), 'bg-rose-600'), isSidebarOpen ? 'px-4' : 'justify-center']" class="flex items-center gap-3 py-3 rounded-xl font-semibold transition-all duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                        <span v-show="isSidebarOpen">Reportes Depto.</span>
                    </Link>
                </nav>
            </div>
            <div :class="isSidebarOpen ? 'px-6' : 'px-2'" class="py-6 flex justify-center border-t border-slate-800 bg-slate-900/50 transition-all">
                <ApplicationLogo class="h-12 w-auto opacity-50 hover:opacity-100 transition-opacity" />
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto flex flex-col relative bg-gray-50 dark:bg-gray-950 h-screen text-gray-900 dark:text-gray-100">
            <nav class="bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800 shrink-0 sticky top-0 z-30 shadow-sm">
                <div class="px-4 sm:px-6 lg:px-8 flex h-16 items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button @click="toggleSidebar" class="md:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800 focus:outline-none transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <slot name="header" />
                    </div>
                    <div class="flex items-center gap-4">
                        <NotificationBell />
                    </div>
                </div>
            </nav>

            <div class="p-4 sm:p-6 lg:p-8 flex-1">
                <slot />
            </div>

            <footer class="py-6 text-center text-gray-400 dark:text-slate-500 text-[10px] border-t border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <p>&copy; {{ settings?.footer_year || '2026' }} {{ settings?.company_name || 'H. Congreso del Estado de Nayarit' }} &reg;. Todos los derechos reservados.</p>
            </footer>
        </main>

        <div class="fixed bottom-6 right-6 z-[100] group">
            <Dropdown align="right" width="48" direction="up">
                <template #trigger>
                    <button class="relative flex items-center justify-center h-14 w-14 rounded-full bg-indigo-600 border-4 border-white dark:border-slate-800 shadow-2xl hover:scale-110 active:scale-95 transition-all overflow-hidden group">
                        <img v-if="user.avatar" :src="user.avatar" class="h-full w-full object-cover object-center" />
                        <span v-else class="text-white font-bold text-xl">{{ user.name.charAt(0) }}</span>
                    </button>
                </template>
                <template #content>
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                        <p class="text-[10px] text-gray-500 truncate capitalize">{{ user.role.replace('_', ' ') }}</p>
                    </div>
                    <div class="p-1">
                        <button @click="toggleTheme" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                            Cambiar Tema
                        </button>
                        <button @click="openVirtualCredential" v-if="user.credential_code" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M1.5 9.75h21" /></svg>
                            Mi Credencial
                        </button>
                        <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            Configuración
                        </DropdownLink>
                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                        <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center gap-2 text-red-600 dark:text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Cerrar Sesión
                        </DropdownLink>
                    </div>
                </template>
            </Dropdown>
        </div>
        
        <Modal :show="showVirtualCredentialModal" @close="showVirtualCredentialModal = false" max-width="sm">
            <div class="p-4 relative">
                <div 
                    class="aspect-[5.4/8.6] w-full max-w-sm mx-auto bg-white dark:bg-gray-700 rounded-2xl shadow-lg relative overflow-hidden border bg-cover bg-center"
                    :style="{ backgroundImage: `url(${settings.credential_background})` }"
                >
                    <!-- Dynamic Credential Elements -->
                    <div 
                        class="absolute -translate-x-1/2 -translate-y-1/2 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-white font-bold text-4xl overflow-hidden"
                        :style="{ 
                            top: credentialLayout.avatar.top, 
                            left: credentialLayout.avatar.left,
                            width: credentialLayout.avatar.size + 'px',
                            height: credentialLayout.avatar.size + 'px',
                        }"
                    >
                        <img v-if="user.avatar" :src="user.avatar" class="h-full w-full object-cover object-center" />
                        <span v-else>{{ user.name.charAt(0) }}</span>
                    </div>

                    <div class="absolute -translate-x-1/2 p-2" :style="{...credentialLayout.name, width: '90%'}">
                        {{ user.name }}
                    </div>
                    
                    <div class="absolute -translate-x-1/2 p-2" :style="{...credentialLayout.department, width: '90%'}">
                        {{ user.department }}
                    </div>

                    <div class="absolute -translate-x-1/2 p-2" :style="{...credentialLayout.employee_number, width: '90%'}">
                        NO. {{ user.employee_number }}
                    </div>

                    <div 
                        class="absolute -translate-x-1/2 -translate-y-1/2 bg-white p-1 rounded"
                         :style="{ 
                            top: credentialLayout.qr_code.top, 
                            left: credentialLayout.qr_code.left,
                         }"
                    >
                        <qrcode-vue :value="user.credential_code" :size="parseInt(credentialLayout.qr_code.size)" level="H" />
                    </div>
                </div>
                 <div class="mt-4 w-full flex justify-center">
                    <SecondaryButton @click="showVirtualCredentialModal = false">Cerrar</SecondaryButton>
                </div>
            </div>
        </Modal>
    </div>
    <ToastNotification />
</template>
