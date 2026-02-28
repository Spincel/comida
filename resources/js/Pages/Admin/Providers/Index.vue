<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ScanMenuModal from '@/Pages/Admin/Partials/ScanMenuModal.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    ClipboardDocumentListIcon, 
    PhotoIcon,
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    BuildingStorefrontIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    providers: Array,
});

const showScanModal = ref(false);
const selectedProviderForScan = ref(null);

const openScanModal = (provider) => {
    selectedProviderForScan.value = provider;
    showScanModal.value = true;
};

const deleteProvider = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar este proveedor? Esta acción no se puede deshacer y borrará sus menús asociados.')) {
        router.delete(route('providers.destroy', id), {
            preserveScroll: true,
        });
    }
};

const providerColors = [
    { border: 'border-indigo-200 dark:border-indigo-900/50', text: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-900/20' },
    { border: 'border-emerald-200 dark:border-emerald-900/50', text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
    { border: 'border-rose-200 dark:border-rose-900/50', text: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-50 dark:bg-rose-900/20' },
    { border: 'border-amber-200 dark:border-amber-900/50', text: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-900/20' },
];

const getProviderColor = (index) => providerColors[index % providerColors.length];
</script>

<template>
    <Head title="Proveedores y Catálogos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight uppercase tracking-tight">
                        Proveedores y Catálogos
                    </h2>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1">Gestión Central de Establecimientos</p>
                </div>
                
                <Link :href="route('providers.create')" 
                      class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center shadow-lg shadow-indigo-100 dark:shadow-none transition-all hover:scale-105">
                    <PlusIcon class="h-5 w-5 mr-2" stroke-width="3" /> Añadir Nuevo Proveedor
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div v-if="providers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="(provider, index) in providers" :key="provider.id" 
                         class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border-2 shadow-xl shadow-gray-100 dark:shadow-none transition-all hover:shadow-2xl flex flex-col group"
                         :class="getProviderColor(index).border">
                        
                        <div class="flex justify-between items-start mb-6">
                            <div class="h-16 w-16 rounded-3xl flex items-center justify-center shadow-xl transition-transform group-hover:rotate-6"
                                 :class="getProviderColor(index).bg + ' ' + getProviderColor(index).text">
                                <BuildingStorefrontIcon class="h-10 w-10" />
                            </div>
                            <button @click="deleteProvider(provider.id)" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <h3 class="font-black text-2xl text-gray-900 dark:text-white uppercase tracking-tight leading-tight mb-4 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                            {{ provider.name }}
                        </h3>

                        <!-- Datos de Contacto -->
                        <div class="space-y-3 mb-8 flex-1">
                            <div v-if="provider.address" class="flex items-start text-xs text-gray-500 dark:text-gray-400">
                                <MapPinIcon class="h-4 w-4 mr-2 shrink-0 text-gray-400" />
                                <span class="font-bold uppercase tracking-tighter">{{ provider.address }}</span>
                            </div>
                            <div v-if="provider.contact_phone" class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <PhoneIcon class="h-4 w-4 mr-2 shrink-0 text-gray-400" />
                                <span class="font-bold tracking-widest">{{ provider.contact_phone }}</span>
                            </div>
                            <div v-if="provider.contact_email" class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <EnvelopeIcon class="h-4 w-4 mr-2 shrink-0 text-gray-400" />
                                <span class="font-bold lowercase truncate">{{ provider.contact_email }}</span>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="grid grid-cols-1 gap-3">
                            <Link :href="route('daily-menus.index', { provider_id: provider.id })" 
                                  class="w-full py-4 bg-gray-900 dark:bg-gray-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-600 transition-all flex items-center justify-center shadow-lg group-hover:shadow-indigo-100 dark:group-hover:shadow-none">
                                <ClipboardDocumentListIcon class="h-4 w-4 mr-2" /> Catálogo de Menús
                            </Link>
                            
                            <button @click="openScanModal(provider)"
                                    class="w-full py-4 bg-white dark:bg-gray-800 border-2 border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-50 transition-all flex items-center justify-center">
                                <PhotoIcon class="h-4 w-4 mr-2" /> Escanear con IA
                            </button>

                            <Link :href="route('providers.edit', provider.id)" 
                                  class="w-full py-3 bg-gray-50 dark:bg-gray-900/30 text-gray-400 dark:text-gray-500 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:text-indigo-600 transition-all flex items-center justify-center">
                                <PencilSquareIcon class="h-4 w-4 mr-2" /> Editar Perfil
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="p-20 bg-white dark:bg-gray-800 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700 text-center">
                    <BuildingStorefrontIcon class="h-16 w-16 text-gray-200 mx-auto mb-4" />
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm">No hay proveedores registrados aún</p>
                </div>

            </div>
        </div>

        <ScanMenuModal 
            :show="showScanModal"
            :provider="selectedProviderForScan"
            :selectedDate="new Date().toISOString().split('T')[0]"
            @close="showScanModal = false"
        />
    </AuthenticatedLayout>
</template>
