<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-geo-teal text-start text-base font-black text-geo-navy dark:text-geo-teal bg-teal-50 dark:bg-geo-teal/10 transition duration-500 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-bold text-gray-600 dark:text-gray-400 hover:text-geo-navy dark:hover:text-white hover:bg-gray-50 dark:hover:bg-white/5 transition duration-500 ease-in-out'
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
