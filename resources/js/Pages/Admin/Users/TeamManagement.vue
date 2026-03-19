<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    UserPlusIcon, 
    PencilSquareIcon, 
    NoSymbolIcon, 
    CheckCircleIcon,
    ArrowLeftIcon,
    UserCircleIcon,
    BuildingOfficeIcon,
    HashtagIcon,
    PhotoIcon,
    CheckBadgeIcon,
    ArrowsPointingOutIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    team: Array,
    area: Object
});

const showMemberModal = ref(false);
const editingMember = ref(null);
const photoInput = ref(null);
const photoPreview = ref(null);
const rawImage = ref(null); 
const imageScale = ref(1.2);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const startPos = ref({ x: 0, y: 0 });

const form = useForm({
    first_name: '',
    last_name: '',
    second_last_name: '',
    avatar: null,
});

const openCreateModal = () => {
    editingMember.value = null;
    photoPreview.value = null;
    rawImage.value = null;
    form.reset();
    showMemberModal.value = true;
};

const openEditModal = (member) => {
    editingMember.value = member;
    photoPreview.value = member.avatar_url;
    rawImage.value = null; 
    form.first_name = member.first_name;
    form.last_name = member.last_name;
    form.second_last_name = member.second_last_name || '';
    form.avatar = null;
    showMemberModal.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];
    if (!photo) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
        rawImage.value = e.target.result;
        imageScale.value = 1.2; 
        imagePosition.value = { x: 0, y: 0 };
    };
    reader.readAsDataURL(photo);
};

// --- Natural Framing Logic (Drag & Scroll) ---
const startDrag = (e) => {
    if (!rawImage.value) return;
    isDragging.value = true;
    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
    startPos.value = { 
        x: clientX - imagePosition.value.x, 
        y: clientY - imagePosition.value.y 
    };
};

const onDrag = (e) => {
    if (!isDragging.value) return;
    e.preventDefault();
    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
    imagePosition.value = {
        x: clientX - startPos.value.x,
        y: clientY - startPos.value.y
    };
};

const stopDrag = () => {
    isDragging.value = false;
};

const handleWheel = (e) => {
    if (!rawImage.value) return;
    e.preventDefault();
    const delta = e.deltaY * -0.001;
    const newScale = Math.min(Math.max(0.5, imageScale.value + delta), 5);
    imageScale.value = newScale;
};

const getCroppedImage = () => {
    return new Promise((resolve) => {
        const img = new Image();
        img.src = photoPreview.value;
        img.onload = () => {
            const canvas = document.createElement('canvas');
            const size = 400; 
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');

            const aspect = img.width / img.height;
            let drawW, drawH;

            if (aspect > 1) { 
                drawH = size * imageScale.value;
                drawW = drawH * aspect;
            } else { 
                drawW = size * imageScale.value;
                drawH = drawW / aspect;
            }

            const drawX = ((size - drawW) / 2) + imagePosition.value.x;
            const drawY = ((size - drawH) / 2) + imagePosition.value.y;

            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, size, size);
            ctx.drawImage(img, drawX, drawY, drawW, drawH);

            canvas.toBlob((blob) => {
                resolve(new File([blob], 'avatar.jpg', { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.9);
        };
    });
};

const submitForm = async () => {
    if (rawImage.value) {
        form.avatar = await getCroppedImage();
    }

    if (editingMember.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('team.update', editingMember.value.id), {
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('team.store'), {
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showMemberModal.value = false;
    photoPreview.value = null;
    rawImage.value = null;
    if (photoInput.value) photoInput.value.value = null;
    form.reset();
};

const toggleStatus = (member) => {
    if (confirm(`¿Deseas ${member.status === 'active' ? 'deshabilitar' : 'habilitar'} a este comensal?`)) {
        router.patch(route('team.toggleStatus', member.id));
    }
};

const activeCount = computed(() => props.team.filter(m => m.status === 'active').length);
</script>

<template>
    <Head title="Mi Plantilla" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <Link :href="route('dashboard')" class="p-3 bg-white dark:bg-gray-800 rounded-2xl border shadow-sm hover:bg-gray-50 transition-all">
                        <ArrowLeftIcon class="h-6 w-6 text-gray-500" />
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 uppercase tracking-tighter leading-none">Mi Plantilla de Área</h2>
                        <p class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em] mt-2 leading-none">{{ area.name }}</p>
                    </div>
                </div>
                <button @click="openCreateModal" class="px-8 py-4 bg-gray-900 text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl flex items-center gap-3 active:scale-95 text-center">
                    <UserPlusIcon class="h-5 w-5" /> Agregar Comensal
                </button>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="max-w-[85%] mx-auto space-y-10">
                
                <!-- STATS BAR -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[3.5rem] shadow-2xl border-2 border-indigo-50 flex items-center gap-12">
                    <div class="flex items-center gap-6 pr-12 border-r">
                        <div class="h-16 w-16 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 shadow-inner">
                            <UserCircleIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-2">Total Personal</p>
                            <p class="text-3xl font-black text-gray-800 dark:text-white leading-none">{{ team.length }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="h-16 w-16 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 shadow-inner">
                            <CheckBadgeIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-2">Activos</p>
                            <p class="text-3xl font-black text-emerald-600 leading-none">{{ activeCount }}</p>
                        </div>
                    </div>
                </div>

                <!-- TABLE SECTION -->
                <div class="bg-white dark:bg-gray-800 rounded-[4rem] shadow-2xl border-2 border-gray-50 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 border-b">
                                <th class="px-10 py-6 text-center">ID</th>
                                <th class="px-10 py-6">Personal</th>
                                <th class="px-10 py-6 text-center">Estatus</th>
                                <th class="px-10 py-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <tr v-for="member in team" :key="member.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all group">
                                <td class="px-10 py-6 text-center">
                                    <span class="text-[10px] font-black text-gray-300 group-hover:text-indigo-300 transition-colors">#{{ member.employee_number }}</span>
                                </td>
                                <td class="px-10 py-6">
                                    <div class="flex items-center gap-5">
                                        <img :src="member.avatar_url" class="h-12 w-12 rounded-full border-2 border-white shadow-lg object-cover" />
                                        <div>
                                            <p class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-tight">{{ member.name }}</p>
                                            <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest mt-1">{{ member.username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-6 text-center">
                                    <span @click="toggleStatus(member)" 
                                          class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest cursor-pointer transition-all border-2"
                                          :class="member.status === 'active' 
                                            ? 'bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-600 hover:text-white shadow-sm' 
                                            : 'bg-red-50 text-red-600 border-red-100 hover:bg-red-600 hover:text-white opacity-50'">
                                        {{ member.status === 'active' ? 'Habilitado' : 'Deshabilitado' }}
                                    </span>
                                </td>
                                <td class="px-10 py-6 text-right">
                                    <button @click="openEditModal(member)" class="p-3 bg-gray-50 dark:bg-gray-700 text-indigo-600 rounded-2xl border border-transparent hover:border-indigo-200 transition-all shadow-sm">
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="team.length === 0">
                                <td colspan="4" class="px-10 py-20 text-center text-gray-400 font-bold uppercase italic text-sm">No hay personal registrado en tu plantilla todavía.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- MODAL: CREAR/EDITAR MIEMBRO -->
        <Modal :show="showMemberModal" @close="closeModal" max-width="md">
            <div class="p-10">
                <div class="flex items-center gap-6 mb-10">
                    <div class="h-16 w-16 bg-indigo-600 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl">
                        <UserPlusIcon class="h-8 w-8" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">{{ editingMember ? 'Editar Datos' : 'Nuevo Comensal' }}</h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Plantilla: {{ area.name }}</p>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="space-y-8">
                    <!-- Photo Upload & NATURAL Framing -->
                    <div class="flex flex-col items-center mb-10">
                        <input type="file" class="hidden" ref="photoInput" @change="updatePhotoPreview" accept="image/*" capture="user">
                        
                        <div class="relative group">
                            <div class="absolute -inset-2 bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000 animate-pulse"></div>
                            
                            <!-- Circle Container with Natural Interaction -->
                            <div class="relative h-48 w-48 rounded-full border-4 border-white shadow-2xl overflow-hidden bg-gray-100 cursor-move touch-none group/circle"
                                 @mousedown="startDrag"
                                 @mousemove="onDrag"
                                 @mouseup="stopDrag"
                                 @mouseleave="stopDrag"
                                 @touchstart="startDrag"
                                 @touchmove="onDrag"
                                 @touchend="stopDrag"
                                 @wheel="handleWheel">
                                
                                <img :src="photoPreview || 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'" 
                                     :style="{ 
                                        transform: rawImage ? `translate(${imagePosition.x}px, ${imagePosition.y}px) scale(${imageScale})` : 'none',
                                        objectFit: rawImage ? 'contain' : 'cover'
                                     }"
                                     class="h-full w-full pointer-events-none select-none" />
                                
                                <!-- Natural interaction hints -->
                                <div v-if="rawImage" class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 group-hover/circle:opacity-100 transition-opacity pointer-events-none">
                                    <div class="text-center text-white p-4">
                                        <ArrowsPointingOutIcon class="h-8 w-8 mx-auto mb-2" />
                                        <p class="text-[8px] font-black uppercase tracking-[0.2em]">Arrastra para mover<br/>Scroll para Zoom</p>
                                    </div>
                                </div>

                                <div v-if="!rawImage" class="absolute inset-0 bg-black/5 flex items-center justify-center pointer-events-none">
                                    <PhotoIcon class="h-10 w-10 text-gray-300" />
                                </div>
                            </div>

                            <button type="button" @click="selectNewPhoto" 
                                    class="absolute -bottom-2 -right-2 h-14 w-14 bg-indigo-600 rounded-2xl border-4 border-white flex items-center justify-center text-white shadow-xl hover:scale-110 transition-transform">
                                <PhotoIcon class="h-7 w-7" />
                            </button>
                        </div>

                        <div class="mt-8 text-center">
                            <p class="text-[10px] font-black uppercase text-gray-800 dark:text-white tracking-widest leading-none">Ajuste Natural</p>
                            <p v-if="rawImage" class="text-[8px] font-bold text-indigo-500 uppercase mt-2 tracking-tighter italic">Usa el mouse o los dedos sobre la foto</p>
                            <p v-else class="text-[8px] font-bold text-gray-400 uppercase mt-2 tracking-tighter italic">Haz clic en la cámara para empezar</p>
                        </div>
                        <InputError :message="form.errors.avatar" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="first_name" value="Nombre(s)" class="text-[10px] font-black uppercase text-gray-400 mb-2 ml-2" />
                        <TextInput id="first_name" type="text" class="w-full !rounded-2xl" v-model="form.first_name" required placeholder="Ej. Juan" />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="last_name" value="Apellido Paterno" class="text-[10px] font-black uppercase text-gray-400 mb-2 ml-2" />
                            <TextInput id="last_name" type="text" class="w-full !rounded-2xl" v-model="form.last_name" required placeholder="Ej. Pérez" />
                            <InputError :message="form.errors.last_name" />
                        </div>
                        <div>
                            <InputLabel for="second_last_name" value="Apellido Materno" class="text-[10px] font-black uppercase text-gray-400 mb-2 ml-2" />
                            <TextInput id="second_last_name" type="text" class="w-full !rounded-2xl" v-model="form.second_last_name" placeholder="Ej. García" />
                            <InputError :message="form.errors.second_last_name" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 pt-4">
                        <PrimaryButton class="w-full justify-center py-5 text-sm font-black uppercase tracking-widest shadow-2xl active:scale-95" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ editingMember ? 'Guardar Cambios' : 'Registrar en Plantilla' }}
                        </PrimaryButton>
                        <SecondaryButton @click="closeModal" class="w-full justify-center !rounded-2xl !py-4 text-[10px] font-black uppercase tracking-widest border-none text-gray-400 hover:text-gray-600">
                            Cancelar
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
</style>
