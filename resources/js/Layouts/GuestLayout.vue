<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50">
        <div class="animate-fade-in">
            <Link href="/">
                <div class="p-3 bg-geo-navy rounded-2xl shadow-glass border border-white/5">
                    <ApplicationLogo class="w-9 h-9 fill-current text-geo-teal" />
                </div>
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md mt-8 px-8 py-10 bg-white shadow-xl shadow-geo-navy/5 overflow-hidden sm:rounded-3xl border border-gray-100 relative"
        >
            <!-- Decorative accent line -->
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-geo-teal to-geo-blue"></div>
            
            <slot />
        </div>
        
        <div class="mt-8">
            <Link v-if="$page.props.auth && $page.props.auth.user" :href="route('dashboard')" class="text-geo-slate hover:text-geo-navy text-xs font-bold uppercase tracking-widest transition-colors flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Dashboard
            </Link>
            <Link v-else href="/" class="text-geo-slate hover:text-geo-navy text-xs font-bold uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Return to Intelligence Briefing
            </Link>
        </div>
    </div>
</template>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fade-in 0.6s ease-out forwards;
}
</style>
