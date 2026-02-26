<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
});

const defaultLayout = {
    name: { top: '45%', left: '50%', fontSize: '22px', color: '#000000', fontWeight: '700', textAlign: 'center' },
    department: { top: '58%', left: '50%', fontSize: '14px', color: '#374151', fontWeight: '400', textAlign: 'center' },
    employee_number: { top: '70%', left: '50%', fontSize: '16px', color: '#1d4ed8', fontWeight: '600', textAlign: 'center' },
    qr_code: { top: '85%', left: '50%', size: '80' },
    avatar: { top: '22%', left: '50%', size: '100' },
};

const getParsedLayout = () => {
    try {
        const savedLayout = JSON.parse(props.settings.credential_layout);
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
};

const form = useForm({
    credential_layout: getParsedLayout(),
});

const formJsonString = computed({
    get: () => JSON.stringify(form.credential_layout, null, 2),
    set: (value) => {
        try {
            const parsed = JSON.parse(value);
            if (parsed.name && parsed.department && parsed.employee_number && parsed.qr_code && parsed.avatar) {
                form.credential_layout = parsed;
            }
        } catch (e) {
            // Ignore invalid JSON
        }
    }
});

const submit = () => {
    const dataToSend = {
        credential_layout: JSON.stringify(form.credential_layout)
    };
    router.post(route('admin.settings.update'), dataToSend, {
        preserveScroll: true,
        onSuccess: () => {
            alert('¡Diseño guardado!');
        }
    });
};

// --- Drag and Drop Logic ---
const previewContainer = ref(null);
const currentlyDragging = ref(null);
const dragOffset = ref({ x: 0, y: 0 });

const startDrag = (event, elementName) => {
    event.preventDefault();
    currentlyDragging.value = elementName;
    
    const el = event.target;
    const rect = el.getBoundingClientRect();

    dragOffset.value = {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top
    };
    
    window.addEventListener('mousemove', handleDrag);
    window.addEventListener('mouseup', stopDrag);
};

const handleDrag = (event) => {
    if (!currentlyDragging.value || !previewContainer.value) return;
    
    const containerRect = previewContainer.value.getBoundingClientRect();
    
    const x = event.clientX - containerRect.left - dragOffset.value.x;
    const y = event.clientY - containerRect.top - dragOffset.value.y;

    const newLeft = (x / containerRect.width) * 100;
    const newTop = (y / containerRect.height) * 100;

    const targetElement = form.credential_layout[currentlyDragging.value];
    
    if (targetElement) {
        targetElement.left = `${Math.max(0, Math.min(100, newLeft))}%`;
        targetElement.top = `${Math.max(0, Math.min(100, newTop))}%`;
    }
};

const stopDrag = () => {
    currentlyDragging.value = null;
    window.removeEventListener('mousemove', handleDrag);
    window.removeEventListener('mouseup', stopDrag);
};

</script>

<template>
    <Head title="Diseño de Credencial" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editor de Diseño de Credencial
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="space-y-4">
                    <h3 class="font-bold text-lg text-gray-800 dark:text-gray-200">Vista Previa (Arrastre los elementos)</h3>
                    <div 
                        ref="previewContainer"
                        class="aspect-[5.4/8.6] w-full max-w-sm mx-auto bg-white dark:bg-gray-700 rounded-2xl shadow-lg relative overflow-hidden border bg-cover bg-center"
                        :style="{ backgroundImage: `url(${props.settings.credential_background})` }"
                    >
                        
                        <div 
                            @mousedown="startDrag($event, 'avatar')"
                            class="absolute -translate-x-1/2 -translate-y-1/2 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-white font-bold text-4xl overflow-hidden cursor-move"
                            :style="{ 
                                top: form.credential_layout.avatar.top, 
                                left: form.credential_layout.avatar.left,
                                width: form.credential_layout.avatar.size + 'px',
                                height: form.credential_layout.avatar.size + 'px',
                             }"
                        >
                            <span>J</span>
                        </div>

                        <div @mousedown="startDrag($event, 'name')" class="absolute -translate-x-1/2 p-2 cursor-move" :style="{...form.credential_layout.name, width: '90%'}">
                            Juan Empleado Ejemplo
                        </div>
                        
                         <div @mousedown="startDrag($event, 'department')" class="absolute -translate-x-1/2 p-2 cursor-move" :style="{...form.credential_layout.department, width: '90%'}">
                            Departamento de Sistemas
                        </div>

                        <div @mousedown="startDrag($event, 'employee_number')" class="absolute -translate-x-1/2 p-2 cursor-move" :style="{...form.credential_layout.employee_number, width: '90%'}">
                            NO. 12345
                        </div>

                        <div 
                            @mousedown="startDrag($event, 'qr_code')"
                            class="absolute -translate-x-1/2 -translate-y-1/2 bg-white p-1 rounded cursor-move"
                             :style="{ 
                                top: form.credential_layout.qr_code.top, 
                                left: form.credential_layout.qr_code.left,
                             }"
                        >
                            <div class="bg-gray-300" :style="{ width: form.credential_layout.qr_code.size + 'px', height: form.credential_layout.qr_code.size + 'px'}"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
                    <h3 class="font-bold text-lg mb-4 text-gray-800 dark:text-gray-200">Configuración de Diseño (JSON)</h3>
                    
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <p class="text-sm text-gray-500">
                                Arrastre los elementos en la vista previa para acomodarlos. Use este editor para ajustes finos.
                            </p>
                            <textarea v-model="formJsonString" rows="25" class="w-full font-mono text-xs rounded-md bg-gray-100 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            
                            <div>
                                <PrimaryButton type="submit" :disabled="form.processing">
                                    Guardar Diseño
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>