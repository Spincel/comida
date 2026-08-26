<script setup>
import { ref, watch, onUnmounted, onMounted, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage, Head, router } from '@inertiajs/vue3';
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
    CheckBadgeIcon,
    ArrowLeftIcon,
    Squares2X2Icon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    hideNav: { type: Boolean, default: false },
    bentoTitle: { type: String, default: 'SICOA.' },
    bentoSubtitle: { type: String, default: 'Sistema de Control de Alimentación' },
    bentoTag: { type: String, default: 'V2.1' }
});

const page = usePage();
const visibleFlash = ref(null);
let flashTimeout = null;

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('dashboard'));
    }
};

const logout = () => {
    router.post(route('logout'));
};

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
    <div class="relative min-h-screen bg-[#faf9f6] dark:bg-[#0b0f19] transition-colors duration-500 overflow-x-hidden font-sans">
        <Head :title="dynamicAppName" />

        <div class="max-w-[95%] mx-auto py-6 space-y-8">
            
            <!-- BENTO TOP BAR (GLOBAL REUSABLE) -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white/80 dark:bg-gray-900/60 backdrop-blur-md p-4 rounded-[2rem] border border-slate-200/80 dark:border-white/10 shadow-[0_4px_25px_-5px_rgba(120,24,42,0.06)] dark:shadow-[0_10px_30px_-5px_rgba(0,0,0,0.6)] transition-all z-50 sticky top-4">
                <div class="flex items-center gap-4">
                    <button v-if="!route().current('dashboard')" @click="goBack" class="p-4 bg-white/90 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-700 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm transition-all group shrink-0 flex items-center gap-3 cursor-pointer">
                        <ArrowLeftIcon class="h-6 w-6 text-slate-400 group-hover:text-tinto-700 dark:group-hover:text-oro-400 transition-colors" />
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover:text-tinto-700 dark:group-hover:text-oro-400 transition-colors hidden sm:block">Atrás</span>
                    </button>
                    <Link :href="route('dashboard')" class="bg-gradient-to-tr from-tinto-900 via-tinto-800 to-tinto-700 p-3 rounded-2xl shadow-lg shadow-tinto-950/20 border border-oro-500/40 hover:scale-105 transition-transform shrink-0" title="Congreso del Estado de Nayarit">
                        <BuildingStorefrontIcon class="h-8 w-8 text-oro-200" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter">{{ bentoTitle }}</h1>
                            <span class="bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest border border-tinto-200/60 dark:border-tinto-800/60">{{ bentoTag }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ bentoSubtitle }}</p>
                    </div>
                </div>

                <!-- CLOCK & DATE -->
                <div class="flex items-center gap-4 md:gap-8 overflow-hidden">
                    <div class="bg-white/90 dark:bg-gray-800/80 border border-slate-200 dark:border-gray-700 p-3 px-6 md:px-8 rounded-3xl shadow-sm flex flex-col items-center min-w-[140px] md:min-w-[180px]">
                        <p class="text-[8px] font-black text-tinto-700 dark:text-oro-400 uppercase tracking-[0.3em] mb-1">Hora Local Nayarit</p>
                        <p class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white tabular-nums tracking-tighter leading-none">{{ currentTime }}</p>
                    </div>
                    <div class="hidden sm:flex bg-white/90 dark:bg-gray-800/80 border border-slate-200 dark:border-gray-700 p-3 px-6 md:px-8 rounded-3xl shadow-sm flex flex-col items-center">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1">Fecha Sistema</p>
                        <div class="flex items-center gap-2">
                            <CalendarDaysIcon class="h-4 w-4 text-oro-500 dark:text-oro-400" />
                            <p class="text-xs md:text-sm font-black text-slate-700 dark:text-gray-300 uppercase whitespace-nowrap">{{ formattedToday }}</p>
                        </div>
                    </div>
                </div>

                <!-- USER PROFILE & THEME -->
                <div class="flex items-center gap-4">
                    <!-- BOTÓN / MENÚ HERRAMIENTAS DEL SISTEMA -->
                    <Dropdown v-if="user.role === 'admin' || user.role === 'acquisitions_manager' || user.role === 'area_manager'" align="right" width="72">
                        <template #trigger>
                            <button class="p-3 rounded-2xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-slate-600 dark:text-gray-300 hover:text-tinto-800 dark:hover:text-oro-400 hover:border-tinto-300 dark:hover:border-oro-600 transition-all shadow-sm flex items-center gap-2 group cursor-pointer" title="Herramientas del Sistema">
                                <Squares2X2Icon class="h-6 w-6 text-tinto-700 dark:text-oro-400 group-hover:scale-110 transition-transform" />
                                <span class="text-[10px] font-black uppercase tracking-widest hidden xl:inline-block">Herramientas</span>
                                <ChevronDownIcon class="h-3.5 w-3.5 text-slate-400 ml-0.5" />
                            </button>
                        </template>
                        <template #content>
                            <!-- CABECERA DEL MENÚ HERRAMIENTAS -->
                            <div class="p-3.5 bg-gradient-to-r from-tinto-950 via-tinto-900 to-tinto-950 text-white rounded-2xl mx-1.5 mb-2 shadow-tinto-sm border border-oro-400/40 flex items-center gap-3">
                                <div class="h-8 w-8 rounded-xl bg-white/10 flex items-center justify-center text-oro-300 border border-oro-400/30 shrink-0">
                                    <Squares2X2Icon class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-tight text-white">Herramientas</p>
                                    <p class="text-[8px] text-oro-300 font-bold uppercase tracking-widest">SICOA • Módulos Operativos</p>
                                </div>
                            </div>

                            <div class="px-3.5 py-1 text-[9px] font-black text-tinto-800 dark:text-oro-400 uppercase tracking-[0.2em]">Operaciones y Reportes</div>
                            <DropdownLink :href="route('admin.history')">
                                <div class="h-7 w-7 rounded-xl bg-tinto-50 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">📖</div>
                                <span class="truncate">Historial Global</span>
                            </DropdownLink>
                            <DropdownLink :href="user.role === 'area_manager' ? route('area.reports') : route('admin.reports')">
                                <div class="h-7 w-7 rounded-xl bg-oro-50 dark:bg-oro-950/80 text-oro-800 dark:text-oro-300 border border-oro-200 dark:border-oro-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">📊</div>
                                <span class="truncate">Reportes del Sistema</span>
                            </DropdownLink>
                            <DropdownLink :href="route('daily.summary')">
                                <div class="h-7 w-7 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">📈</div>
                                <span class="truncate">Estadísticas Diarias</span>
                            </DropdownLink>

                            <div class="px-3.5 py-1 text-[9px] font-black text-tinto-800 dark:text-oro-400 uppercase tracking-[0.2em] mt-2 border-t border-slate-100 dark:border-gray-800/80 pt-2">Gestión del Catálogo</div>
                            <DropdownLink :href="route('providers.index')">
                                <div class="h-7 w-7 rounded-xl bg-tinto-50 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🚚</div>
                                <span class="truncate">Proveedores</span>
                            </DropdownLink>
                            <DropdownLink :href="route('areas.index')">
                                <div class="h-7 w-7 rounded-xl bg-oro-50 dark:bg-oro-950/80 text-oro-800 dark:text-oro-300 border border-oro-200 dark:border-oro-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🏢</div>
                                <span class="truncate">Áreas</span>
                            </DropdownLink>
                            <DropdownLink :href="route('users.index')">
                                <div class="h-7 w-7 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">👥</div>
                                <span class="truncate">Usuarios</span>
                            </DropdownLink>

                            <template v-if="user.role === 'admin'">
                                <div class="px-3.5 py-1 text-[9px] font-black text-tinto-800 dark:text-oro-400 uppercase tracking-[0.2em] mt-2 border-t border-slate-100 dark:border-gray-800/80 pt-2">Configuración Institucional</div>
                                <DropdownLink :href="route('admin.settings.interface')">
                                    <div class="h-7 w-7 rounded-xl bg-tinto-50 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🎨</div>
                                    <span class="truncate">Interfaz y Logo</span>
                                </DropdownLink>
                                <DropdownLink :href="route('admin.settings.reports')">
                                    <div class="h-7 w-7 rounded-xl bg-oro-50 dark:bg-oro-950/80 text-oro-800 dark:text-oro-300 border border-oro-200 dark:border-oro-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">📄</div>
                                    <span class="truncate">Configurar Reportes</span>
                                </DropdownLink>
                                <DropdownLink :href="route('admin.settings.roles')">
                                    <div class="h-7 w-7 rounded-xl bg-purple-50 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🔐</div>
                                    <span class="truncate">Roles y Permisos</span>
                                </DropdownLink>
                                <DropdownLink :href="route('admin.utilities.data')">
                                    <div class="h-7 w-7 rounded-xl bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border border-slate-200 dark:border-gray-700 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🛠️</div>
                                    <span class="truncate">Mantenimiento BD</span>
                                </DropdownLink>
                                <DropdownLink :href="route('admin.sessions.logs')">
                                    <div class="h-7 w-7 rounded-xl bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border border-slate-200 dark:border-gray-700 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">📜</div>
                                    <span class="truncate">Auditoría de Logs</span>
                                </DropdownLink>
                            </template>
                        </template>
                    </Dropdown>

                    <button @click="toggleDarkMode" class="p-3 rounded-2xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-slate-400 hover:text-oro-500 transition-all shadow-sm cursor-pointer" title="Cambiar Tema">
                        <MoonIcon v-if="!isDarkMode" class="h-6 w-6" />
                        <SunIcon v-else class="h-6 w-6 text-oro-400" />
                    </button>
                    
                    <!-- BOTÓN / MENÚ USUARIO -->
                    <Dropdown align="right" width="72">
                        <template #trigger>
                            <div class="flex items-center gap-3.5 bg-white dark:bg-gray-800 p-2 pr-5 rounded-[2rem] border border-slate-200 dark:border-gray-700 shadow-sm cursor-pointer hover:border-tinto-300 dark:hover:border-oro-600 transition-all group">
                                <img :src="user.avatar_url" class="h-11 w-11 rounded-2xl object-cover border-2 border-oro-400/50 dark:border-oro-600/60 shadow-md shrink-0 group-hover:scale-105 transition-transform" />
                                <div class="text-left">
                                    <p class="text-xs font-black text-slate-800 dark:text-white leading-tight whitespace-nowrap">{{ user.name }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="bg-tinto-50 dark:bg-tinto-950/60 text-tinto-800 dark:text-oro-300 text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest border border-tinto-200/70 dark:border-tinto-800/70">{{ roleName }}</span>
                                    </div>
                                </div>
                                <ChevronDownIcon class="h-4 w-4 text-slate-300 ml-1 group-hover:text-tinto-700 transition-colors" />
                            </div>
                        </template>

                        <template #content>
                            <!-- CABECERA DEL PERFIL DE USUARIO -->
                            <div class="p-3.5 bg-gradient-to-r from-tinto-950 via-tinto-900 to-tinto-950 text-white rounded-2xl mx-1.5 mb-2 shadow-tinto-sm border border-oro-400/40 flex items-center gap-3">
                                <img :src="user.avatar_url" class="h-10 w-10 rounded-xl object-cover border border-oro-400/40 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black uppercase truncate text-white leading-tight">{{ user.name }}</p>
                                    <p class="text-[9px] text-oro-300 font-bold uppercase truncate mt-0.5">@{{ user.username }} • {{ roleName }}</p>
                                </div>
                            </div>

                            <div class="px-3.5 py-1 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Mi Espacio</div>
                            <DropdownLink :href="route('dashboard')">
                                <div class="h-7 w-7 rounded-xl bg-tinto-50 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🏠</div>
                                <span class="truncate">Inicio Dashboard</span>
                            </DropdownLink>
                            <DropdownLink :href="route('profile.edit')">
                                <div class="h-7 w-7 rounded-xl bg-oro-50 dark:bg-oro-950/80 text-oro-800 dark:text-oro-300 border border-oro-200 dark:border-oro-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">👤</div>
                                <span class="truncate">Mi Perfil y Clave</span>
                            </DropdownLink>
                            
                            <template v-if="can('users.manage')">
                                <div class="px-3.5 py-1 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2 border-t border-slate-100 dark:border-gray-800/80 pt-2">Administración</div>
                                <DropdownLink :href="route('users.index')">
                                    <div class="h-7 w-7 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">👥</div>
                                    <span class="truncate">Usuarios y Personal</span>
                                </DropdownLink>
                                <DropdownLink :href="route('areas.index')">
                                    <div class="h-7 w-7 rounded-xl bg-oro-50 dark:bg-oro-950/80 text-oro-800 dark:text-oro-300 border border-oro-200 dark:border-oro-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🏢</div>
                                    <span class="truncate">Estructura de Áreas</span>
                                </DropdownLink>
                                <DropdownLink :href="route('providers.index')">
                                    <div class="h-7 w-7 rounded-xl bg-tinto-50 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🚚</div>
                                    <span class="truncate">Proveedores / Banqueteros</span>
                                </DropdownLink>
                            </template>

                            <template v-if="user.role === 'admin'">
                                <div class="px-3.5 py-1 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2 border-t border-slate-100 dark:border-gray-800/80 pt-2">Sistema y Seguridad</div>
                                <DropdownLink :href="route('admin.settings.interface')">
                                    <div class="h-7 w-7 rounded-xl bg-tinto-50 dark:bg-tinto-950/80 text-tinto-800 dark:text-oro-300 border border-tinto-200 dark:border-tinto-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🎨</div>
                                    <span class="truncate">Interfaz y Logo</span>
                                </DropdownLink>
                                <DropdownLink :href="route('admin.settings.reports')">
                                    <div class="h-7 w-7 rounded-xl bg-oro-50 dark:bg-oro-950/80 text-oro-800 dark:text-oro-300 border border-oro-200 dark:border-oro-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">📄</div>
                                    <span class="truncate">Configurar Reportes</span>
                                </DropdownLink>
                            </template>

                            <div class="mt-2 border-t border-slate-100 dark:border-gray-800/80 pt-1">
                                <DropdownLink :href="route('logout')" method="post" as="button" @click="logout" class="text-rose-600 dark:text-rose-400 hover:bg-rose-50/90 dark:hover:bg-rose-950/50 hover:text-rose-700">
                                    <div class="h-7 w-7 rounded-xl bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 flex items-center justify-center text-xs shrink-0 group-hover:scale-110 transition-transform">🚪</div>
                                    <span class="font-black">Cerrar Sesión</span>
                                </DropdownLink>
                            </div>
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
