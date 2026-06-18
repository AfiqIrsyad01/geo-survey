<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="GeoSurvey | Strategic Spatial Intelligence" />

    <div class="min-h-screen bg-[#f1f5f9] dark:bg-[#0f172a] text-slate-800 dark:text-white selection:bg-geo-teal selection:text-geo-navy overflow-x-hidden pt-12 transition-colors duration-500">
        <!-- THEME TOGGLE (Top Right) -->
        <div class="fixed top-6 right-6 z-[100]">
            <ThemeToggle />
        </div>

        <!-- TACTICAL GRID BACKGROUND -->
        <div class="fixed inset-0 pointer-events-none opacity-10 dark:opacity-20" 
            style="background-image: radial-gradient(#1e293b 1px, transparent 1px); background-size: 40px 40px;"></div>
        <div class="fixed inset-0 pointer-events-none bg-gradient-to-b from-transparent via-transparent to-slate-200/20 dark:to-slate-950/50"></div>

        <!-- HERO BRANDING AXIS -->
        <div class="relative z-10 max-w-7xl mx-auto px-6 h-full flex flex-col items-center">
            
            <!-- CENTRAL LOGO NODE -->
            <div class="flex flex-col items-center mb-16 group">
                <div class="p-6 bg-slate-50 dark:bg-white/[0.03] shadow-2xl backdrop-blur-xl rounded-[2.5rem] border border-slate-300 dark:border-white/10 transition-all duration-700 group-hover:shadow-[0_0_80px_rgba(16,185,129,0.2)] group-hover:border-geo-teal/30">
                    <ApplicationLogo class="w-20 h-20 fill-current text-geo-teal drop-shadow-[0_0_15px_rgba(16,185,129,0.3)]" />
                </div>
                <div class="mt-12 text-center">
                    <h1 class="text-5xl md:text-6xl font-black uppercase tracking-[0.4em] text-slate-900 dark:text-white leading-none drop-shadow-sm">
                        Geo<span class="text-geo-teal drop-shadow-[0_0_20px_rgba(16,185,129,0.4)] transition-all duration-500" style="-webkit-text-stroke: 1px rgba(0,0,0,0.8);">Survey</span>
                    </h1>
                    <div class="h-1.5 w-24 bg-gradient-to-r from-transparent via-geo-teal to-transparent mx-auto mt-6 rounded-full shadow-[0_0_20px_#10b981]"></div>
                    <p class="mt-8 text-sm md:text-base font-black text-slate-800/60 dark:text-geo-teal/80 uppercase tracking-[0.5em]">Strategic Spatial Intelligence Core</p>
                </div>
            </div>

            <!-- CORE UTILITY DESCRIPTION -->
            <div class="max-w-3xl text-center mb-24">
                <p class="text-xl md:text-2xl text-slate-500 dark:text-gray-400 font-medium leading-relaxed italic">
                    "Precision-engineered for high-stakes geographic workflows. Synchronizing field logistics with centralized administrative intelligence."
                </p>
            </div>

            <!-- ACCESS NODES -->
            <div class="flex flex-col sm:flex-row gap-6 mb-32">
                <Link v-if="canLogin" :href="route('login')" 
                    class="group relative px-14 py-6 bg-transparent border-[1.5px] border-slate-900 dark:border-geo-teal/30 rounded-2xl overflow-hidden transition-all duration-500 hover:border-slate-900 dark:hover:border-geo-teal">
                    <div class="absolute inset-0 bg-geo-teal translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                    <span class="relative z-10 text-[11px] font-black uppercase tracking-[0.4em] text-slate-900 dark:text-geo-teal group-hover:text-white dark:group-hover:text-geo-navy transition-colors">Initialize Session</span>
                </Link>
                
                <a href="#intelligence" 
                    class="px-14 py-6 bg-white dark:bg-white/5 border-[1.5px] border-slate-900 dark:border-white/5 rounded-2xl text-[11px] font-black uppercase tracking-[0.4em] text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/10 transition-all flex items-center justify-center gap-4">
                    Explore Capabilities
                    <i class="fa-solid fa-chevron-down text-[10px] animate-bounce text-geo-teal"></i>
                </a>
            </div>

            <!-- STRATEGIC CAPABILITIES GRID -->
            <div id="intelligence" class="grid grid-cols-1 md:grid-cols-3 gap-[1px] bg-slate-200 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-[3.5rem] overflow-hidden mb-32 shadow-2xl">
                
                <!-- CAPABILITY 01 -->
                <div class="bg-white/90 dark:bg-[#0c121e]/80 backdrop-blur-sm p-14 hover:bg-slate-50 dark:hover:bg-[#111927] transition-all group border-r border-slate-100 dark:border-white/5">
                    <div class="w-12 h-12 mb-10 text-geo-teal group-hover:scale-110 group-hover:drop-shadow-[0_0_15px_rgba(16,185,129,0.5)] transition-all">
                        <i class="fa-solid fa-satellite-dish fa-3x"></i>
                    </div>
                    <h3 class="text-[12px] font-black uppercase tracking-[0.3em] text-geo-teal mb-6">Spatial Integrity</h3>
                    <p class="text-sm text-slate-600 dark:text-gray-400 leading-relaxed font-medium">Automatic EXIF validation cross-references hardware GPS markers with reported map vectors to eliminate data falsification.</p>
                </div>

                <!-- CAPABILITY 02 -->
                <div class="bg-white/90 dark:bg-[#0c121e]/80 backdrop-blur-sm p-14 hover:bg-slate-50 dark:hover:bg-[#111927] transition-all group border-r border-slate-100 dark:border-white/5">
                    <div class="w-12 h-12 mb-10 text-geo-blue group-hover:scale-110 group-hover:drop-shadow-[0_0_15px_rgba(33,150,243,0.5)] transition-all">
                        <i class="fa-solid fa-microchip fa-3x"></i>
                    </div>
                    <h3 class="text-[12px] font-black uppercase tracking-[0.3em] text-geo-blue mb-6">GIS Intelligence</h3>
                    <p class="text-sm text-slate-600 dark:text-gray-400 leading-relaxed font-medium">Advanced vector operations powered by Turf.js for real-time area calculation and automated geocoding discovery.</p>
                </div>

                <!-- CAPABILITY 03 -->
                <div class="bg-white/90 dark:bg-[#0c121e]/80 backdrop-blur-sm p-14 hover:bg-slate-50 dark:hover:bg-[#111927] transition-all group">
                    <div class="w-12 h-12 mb-10 text-emerald-500 group-hover:scale-110 group-hover:drop-shadow-[0_0_15px_rgba(16,185,129,0.5)] transition-all">
                        <i class="fa-solid fa-file-shield fa-3x"></i>
                    </div>
                    <h3 class="text-[12px] font-black uppercase tracking-[0.3em] text-emerald-500 mb-6">Audit Transparency</h3>
                    <p class="text-sm text-slate-600 dark:text-gray-400 leading-relaxed font-medium">Immutable operational logs with automated PDF reporting and HOD-level verification workflows for maximum compliance.</p>
                </div>

            </div>

            <!-- FOOTER STAMP -->
            <div class="pb-16 text-center border-t border-slate-200 dark:border-white/5 pt-16 w-full">
                <div class="flex flex-col items-center gap-6">
                    <p class="text-[11px] font-bold text-slate-600 uppercase tracking-[0.5em] opacity-80">
                        &copy; 2026 GEOSURVEY CORE | PROTECTED BY CORPORATE CRYPTOGRAPHY
                    </p>
                    <div class="flex items-center gap-8 opacity-20">
                        <div class="h-px w-12 bg-slate-900 dark:bg-white"></div>
                        <div class="h-1.5 w-1.5 rounded-full bg-slate-900 dark:bg-white"></div>
                        <div class="h-px w-12 bg-slate-900 dark:bg-white"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap');

:deep(html), body {
    font-family: 'Space Grotesk', sans-serif !important;
}

.max-w-4xl {
    max-width: 64rem;
}
</style>
