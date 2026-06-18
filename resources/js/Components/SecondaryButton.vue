<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center px-4 py-2 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl font-black text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-white/10 focus:ring-2 focus:ring-geo-teal focus:ring-offset-2 dark:focus:ring-offset-geo-bg disabled:opacity-25 transition-all duration-150 active:scale-95"
    >
        <slot />
    </button>
</template>
