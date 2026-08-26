<script>
export default {
    inheritAttrs: false,
};
</script>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    type: {
        type: String,
        default: 'text',
    },
});

const model = defineModel({
    required: true,
});

const input = ref(null);
const showPassword = ref(false);

const inputType = computed(() => {
    if (props.type === 'password') {
        return showPassword.value ? 'text' : 'password';
    }
    return props.type;
});

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div class="relative w-full">
        <input
            :type="inputType"
            class="rounded-xl border-slate-300 shadow-xs focus:border-tinto-700 focus:ring-tinto-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-oro-400 dark:focus:ring-oro-400 w-full transition-all"
            :class="{ 'pr-10': props.type === 'password' }"
            v-model="model"
            ref="input"
            v-bind="$attrs"
        />
        
        <button
            v-if="props.type === 'password'"
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-tinto-700 dark:hover:text-oro-400 transition-colors cursor-pointer"
        >
            <EyeIcon v-if="!showPassword" class="h-5 w-5" />
            <EyeSlashIcon v-else class="h-5 w-5" />
        </button>
    </div>
</template>
