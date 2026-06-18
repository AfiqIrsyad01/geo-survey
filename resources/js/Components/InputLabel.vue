<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block font-black text-[10px] uppercase text-geo-slate dark:text-gray-400 tracking-widest transition-colors">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
