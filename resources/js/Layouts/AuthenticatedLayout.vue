<script setup>
import { ref, watch, onUnmounted, onMounted, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
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
    CalendarDaysIcon,
    XMarkIcon,
    CheckBadgeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    hideNav: { type: Boolean, default: false },
    bentoTitle: { type: String, default: 'SICOA.' },
    bentoSubtitle: { type: String, default: 'Sistema de Control de Alimentación' },
    bentoTag: { type: String, default: 'Bento V2.0' }
});

const page = usePage();
const visibleFlash = ref(null);
let flashTimeout = null;

const user = computed(() => page.props.auth.user);
const roleName = computed(() => ({ 
    'admin': 'Administrador', 
    'acquisitions_manager': 'Adquisiciones', 
    'area_manager': 'Gerente de Área', 
    'diner': 'Comensal' 
}[user.value?.role] || 'Usuario'));

const can = (permission) => {
    if (user.value?.role === 'admin') return true;
    if (user.value?.role === 'acquisitions_manager' && ['areas.manage', 'reports.global', 'providers.manage', 'menus.manage', 'sessions.manage', 'orders.monitor'].includes(permission)) return true;
    return user.value?.permissions?.includes(permission);
};

const dynamicAppName = computed(() => page.props.system?.settings?.app_name || 'Comedor System');

// --- CLOCK & DATE LOGIC ---
const currentTime = ref('');
const formattedToday = new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('es-MX', { 
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false 
    });
};

// --- DARK MODE LOGIC ---
const isDarkMode = ref(false);
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

onMounted(() => {
    updateClock();
    const clockInterval = setInterval(updateClock, 1000);
    
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }

    onUnmounted(() => clearInterval(clockInterval));
});

watch(() => page.props.flash?.success, (newMsg) => {
    if (newMsg) {
        visibleFlash.value = newMsg;
        if (flashTimeout) clearTimeout(flashTimeout);
        flashTimeout = setTimeout(() => { visibleFlash.value = null; }, 3000);
    }
}, { immediate: true });
</script>

<template>
    <div class="relative min-h-screen bg-[#F8F9FA] dark:bg-gray-950 transition-colors duration-500 overflow-x-hidden font-sans">
        <Head :title="dynamicAppName" />

        <div class="max-w-[95%] mx-auto py-6 space-y-8">
            
            <!-- BENTO TOP BAR (GLOBAL REUSABLE) -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white/40 dark:bg-gray-900/40 backdrop-blur-md p-4 rounded-[2rem] border border-white/60 dark:border-white/10 shadow-sm transition-all z-50 sticky top-4">
                <div class="flex items-center gap-6">
                    <Link :href="route('dashboard')" class="bg-orange-500 p-3 rounded-2xl shadow-lg shadow-orange-500/20 hover:scale-105 transition-transform shrink-0">
                        <BuildingStorefrontIcon class="h-8 w-8 text-white" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter">{{ bentoTitle }}</h1>
                            <span class="bg-slate-100 dark:bg-gray-800 text-slate-500 dark:text-gray-400 text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest border border-slate-200 dark:border-gray-700">{{ bentoTag }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ bentoSubtitle }}</p>
                    </div>
                </div>

                <!-- CLOCK & DATE -->
                <div class="flex items-center gap-4 md:gap-8 overflow-hidden">
                    <div class="bg-white/80 dark:bg-gray-800/80 border border-white dark:border-gray-700 p-3 px-6 md:px-8 rounded-3xl shadow-sm flex flex-col items-center min-w-[140px] md:min-w-[180px]">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1">Hora Local CDMX</p>
                        <p class="text-2xl md:text-3xl font-black text-slate-700 dark:text-white tabular-nums tracking-tighter leading-none">{{ currentTime }}</p>
                    </div>
                    <div class="hidden sm:flex bg-white/80 dark:bg-gray-800/80 border border-white dark:border-gray-700 p-3 px-6 md:px-8 rounded-3xl shadow-sm flex flex-col items-center">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1">Fecha Sistema</p>
                        <div class="flex items-center gap-2">
                            <CalendarDaysIcon class="h-4 w-4 text-orange-500" />
                            <p class="text-xs md:text-sm font-black text-slate-700 dark:text-gray-300 uppercase whitespace-nowrap">{{ formattedToday }}</p>
                        </div>
                    </div>
                </div>

                    <!-- USER PROFILE & THEME -->
                    <div class="flex items-center gap-4">
                        <button @click="toggleDarkMode" class="p-3 rounded-2xl bg-white dark:bg-gray-800 border border-slate-100 dark:border-gray-700 text-slate-400 hover:text-indigo-600 transition-all shadow-sm">
                            <MoonIcon v-if="!isDarkMode" class="h-6 w-6" />
                            <SunIcon v-else class="h-6 w-6 text-yellow-500" />
                        </button>
                        
                        <Dropdown align="right" width="64">
                            <template #trigger>
                                <div class="flex items-center gap-4 bg-white dark:bg-gray-800 p-2 pr-6 rounded-[2rem] border border-slate-100 dark:border-gray-700 shadow-sm cursor-pointer hover:border-indigo-300 transition-all">
                                    <img :src="user.avatar_url" class="h-12 w-12 rounded-2xl object-cover border-2 border-slate-50 dark:border-gray-700 shadow-md shrink-0" />
                                    <div class="text-left">
                                        <p class="text-sm font-black text-slate-800 dark:text-white leading-none whitespace-nowrap">{{ user.name }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest border border-indigo-100 dark:border-indigo-800">{{ roleName }}</span>
                                        </div>
                                    </div>
                                    <ChevronDownIcon class="h-4 w-4 text-slate-300 ml-2" />
                                </div>
                            </template>

                        <template #content>
                            <div class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b dark:border-gray-700">Mi Panel</div>
                            <DropdownLink :href="route('dashboard')">🏠 Inicio Dashboard</DropdownLink>
                            
                            <div class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-t border-b dark:border-gray-700 mt-1">Configuración</div>
                            <DropdownLink :href="route('profile.edit')">👤 Mi Perfil</DropdownLink>
                            
                            <template v-if="can('users.manage')">
                                <div class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-t border-b dark:border-gray-700 mt-1">Administración</div>
                                <DropdownLink :href="route('users.index')">👥 Usuarios</DropdownLink>
                                <DropdownLink :href="route('areas.index')">🏢 Áreas</DropdownLink>
                                <DropdownLink :href="route('providers.index')">🚚 Proveedores</DropdownLink>
                            </template>

                            <template v-if="user.role === 'admin'">
                                <div class="block px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-t border-b dark:border-gray-700 mt-1">Sistema y Seguridad</div>
                                <DropdownLink :href="route('admin.settings.interface')">🎨 Interfaz y Logo</DropdownLink>
                                <DropdownLink :href="route('admin.settings.reports')">📄 Configurar Reportes</DropdownLink>
                                <DropdownLink :href="route('admin.settings.roles')">🔐 Roles y Permisos</DropdownLink>
                                <DropdownLink :href="route('admin.utilities.data')">🛠️ Mantenimiento BD</DropdownLink>
                                <DropdownLink :href="route('admin.sessions.logs')">📜 Auditoría de Logs</DropdownLink>
                            </template>

                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-rose-500 font-bold border-t dark:border-gray-700">🚪 Cerrar Sesión</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>

            <!-- FLASH MESSAGES -->
            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform opacity-0 -translate-y-2"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 -translate-y-2"
            >
                <div v-if="visibleFlash"
                     class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 px-6 py-4 rounded-3xl shadow-sm flex items-center gap-4">
                    <CheckBadgeIcon class="h-6 w-6" />
                    <span class="text-sm font-bold uppercase tracking-widest">{{ visibleFlash }}</span>
                </div>
            </transition>

            <!-- MAIN CONTENT -->
            <main class="relative">
                <slot />
            </main>

            <!-- BENTO FOOTER -->
            <footer class="pt-12 pb-8 border-t border-slate-200 dark:border-gray-800 transition-colors duration-300">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6 opacity-60">
                    <div class="flex items-center gap-4">
                        <ApplicationLogo class="h-8 w-auto grayscale" />
                        <div>
                            <p class="text-[11px] font-black text-slate-800 dark:text-gray-200 uppercase tracking-[0.2em]">{{ page.props.system?.settings?.footer_title || 'SICOA' }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ page.props.system?.settings?.footer_subtitle || 'Gestión de Alimentos' }}</p>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">UTICS ® Marca Registrada</p>
                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-2">© 2026 Todos los derechos reservados</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }

/* Responsive utility */
@media (max-width: 400px) {
    .xs\:block { display: block; }
    .xs\:hidden { display: none; }
}
</style>
