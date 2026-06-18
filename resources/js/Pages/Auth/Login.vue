<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Core Access | GeoSurvey Intelligence" />

    <div class="min-h-screen bg-[#f1f5f9] dark:bg-[#0f172a] flex flex-col items-center justify-center p-6 relative overflow-hidden selection:bg-geo-teal selection:text-geo-navy transition-colors duration-500">
        <!-- THEME TOGGLE (Top Right) -->
        <div class="fixed top-6 right-6 z-[100]">
            <ThemeToggle />
        </div>

        <!-- TACTICAL GRID BACKGROUND (Matches Welcome) -->
        <div class="fixed inset-0 pointer-events-none opacity-10 dark:opacity-20" 
            style="background-image: radial-gradient(#1e293b 1px, transparent 1px); background-size: 40px 40px;"></div>
        <div class="fixed inset-0 pointer-events-none bg-gradient-to-b from-transparent via-transparent to-slate-200/40 dark:to-slate-950/50"></div>

        <div class="relative z-10 w-full max-w-[440px] animate-fade-in">
            
            <!-- BRANDING NODE -->
            <div class="flex flex-col items-center mb-10 group">
                <Link href="/" class="p-5 bg-slate-50 dark:bg-white/[0.03] shadow-2xl backdrop-blur-2xl rounded-3xl border border-slate-300 dark:border-white/10 transition-all duration-500 hover:border-geo-teal/30 hover:shadow-[0_0_60px_rgba(16,185,129,0.1)]">
                    <ApplicationLogo class="w-10 h-10 fill-current text-geo-teal drop-shadow-[0_0_10px_rgba(16,185,129,0.2)]" />
                </Link>
                <div class="mt-6 text-center">
                    <h2 class="text-3xl font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Core Access</h2>
                    <p class="mt-2 text-[10px] font-bold text-slate-800/40 dark:text-geo-teal/60 uppercase tracking-[0.5em]">Personnel Authentication Required</p>
                </div>
            </div>

            <!-- LOGIN CONSOLE -->
            <div class="bg-white/95 dark:bg-white/[0.02] shadow-2xl backdrop-blur-3xl border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-10 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-geo-teal/50 to-transparent"></div>
                
                <div v-if="status" class="mb-6 font-bold text-[10px] text-geo-teal bg-geo-teal/10 p-4 rounded-xl border border-geo-teal/20 uppercase tracking-widest text-center animate-pulse">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <div>
                        <InputLabel for="email" value="Authorized Email" class="text-slate-900 dark:text-white font-black uppercase text-[10px] tracking-[0.2em] mb-2 ml-1" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full bg-slate-100 dark:bg-white/[0.03] border-slate-200 dark:border-white/10 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-white/5 focus:border-geo-teal focus:ring-geo-teal rounded-2xl py-4 px-6 text-sm font-medium transition-all"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="operator@geosurvey.core"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2 px-1">
                            <InputLabel for="password" value="System Key" class="text-slate-900 dark:text-white font-black uppercase text-[10px] tracking-[0.2em]" />
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-[9px] font-black text-slate-900 dark:text-geo-teal/50 hover:text-geo-teal uppercase tracking-widest transition-colors">
                                Recovery Needed?
                            </Link>
                        </div>
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full bg-slate-100 dark:bg-white/[0.03] border-slate-200 dark:border-white/10 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-white/5 focus:border-geo-teal focus:ring-geo-teal rounded-2xl py-4 px-6 text-sm font-medium transition-all"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between px-1">
                        <label class="flex items-center cursor-pointer group">
                            <Checkbox name="remember" v-model:checked="form.remember" class="bg-slate-100 dark:bg-white/5 border-slate-200 dark:border-white/10 text-geo-teal focus:ring-geo-teal rounded-md" />
                            <span class="ms-3 text-[10px] font-black text-slate-900 dark:text-gray-400 uppercase tracking-widest transition-colors">Maintain Connectivity</span>
                        </label>
                    </div>

                    <div class="pt-2">
                        <button 
                            type="submit"
                            class="group relative w-full py-5 bg-transparent border-[1.5px] border-slate-900 dark:border-geo-teal/50 rounded-2xl overflow-hidden transition-all duration-500 hover:border-slate-900 dark:hover:border-geo-teal disabled:opacity-30 disabled:pointer-events-none"
                            :class="{ 'opacity-25': form.processing }" 
                            :disabled="form.processing">
                            <div class="absolute inset-0 bg-geo-teal translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                            <span class="relative z-10 text-[11px] font-black uppercase tracking-[0.4em] text-slate-900 dark:text-geo-teal group-hover:text-white dark:group-hover:text-geo-navy transition-colors flex items-center justify-center gap-3">
                                Initialize Session
                                <i class="fa-solid fa-arrow-right-to-bracket text-[10px]"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- FOOTER INFO -->
            <div class="mt-12 text-center">
                <Link href="/" class="group inline-flex items-center gap-3 text-[10px] font-black text-slate-700 dark:text-gray-500 uppercase tracking-[0.3em] hover:text-slate-900 dark:hover:text-white transition-all">
                    <i class="fa-solid fa-chevron-left group-hover:-translate-x-1 transition-transform"></i>
                    Return to Intelligence Briefing
                </Link>
                <p class="mt-6 text-[8px] font-bold text-slate-500 dark:text-gray-700 uppercase tracking-[0.5em]">Validated SAD Architecture v1.0.42</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap');

:deep(html), body {
    font-family: 'Space Grotesk', sans-serif !important;
}

@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
