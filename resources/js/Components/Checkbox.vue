<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded-lg border-slate-300 text-tinto-800 shadow-2xs focus:ring-tinto-700 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-oro-400 dark:focus:ring-offset-gray-900 cursor-pointer"
    />
</template>
