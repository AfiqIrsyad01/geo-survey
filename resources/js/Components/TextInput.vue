<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white focus:border-geo-teal dark:focus:border-geo-teal focus:ring-geo-teal dark:focus:ring-geo-teal rounded-xl shadow-sm transition-all"
        v-model="model"
        ref="input"
    />
</template>
