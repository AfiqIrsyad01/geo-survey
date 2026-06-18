<script setup>
import { ref, onMounted } from 'vue';

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('gss-theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('gss-theme', 'light');
    }
    // Dispatch a custom event so other components (like MapViewer) can listen
    window.dispatchEvent(new CustomEvent('gss-theme-changed', { detail: { isDark: isDark.value } }));
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});
</script>

<template>
    <button 
        @click="toggleTheme" 
        class="relative w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300 group overflow-hidden border border-transparent hover:border-geo-teal/30 bg-gray-50 dark:bg-white/5 shadow-sm hover:shadow-md"
        aria-label="Toggle Operational Mode"
    >
        <div class="relative w-5 h-5 transition-transform duration-500" :class="isDark ? 'rotate-0' : 'rotate-90'">
            <!-- Sun Icon (Visible in Light Mode) -->
            <transition name="fade-icon">
                <i v-if="!isDark" class="fa-solid fa-sun text-amber-500 text-lg absolute inset-0 flex items-center justify-center"></i>
            </transition>
            <!-- Moon Icon (Visible in Dark Mode) -->
            <transition name="fade-icon">
                <i v-if="isDark" class="fa-solid fa-moon text-geo-teal text-lg absolute inset-0 flex items-center justify-center"></i>
            </transition>
        </div>
        
        <!-- Subtle Glow Layer -->
        <div class="absolute inset-0 bg-geo-teal/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
    </button>
</template>

<style scoped>
.fade-icon-enter-active,
.fade-icon-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-icon-enter-from {
    opacity: 0;
    transform: scale(0.5) rotate(-45deg);
}

.fade-icon-leave-to {
    opacity: 0;
    transform: scale(0.5) rotate(45deg);
}
</style>
