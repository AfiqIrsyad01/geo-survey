<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
    <GuestLayout>
        <Head title="Portal Access" />

        <div class="mb-8 text-center">
            <h2 class="text-2xl font-bold text-geo-navy">Secure Portal Access</h2>
            <p class="text-sm text-geo-slate mt-2">Enter credentials to initialize operational session.</p>
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-100 uppercase tracking-wider text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Authorized Email" class="text-geo-navy font-bold uppercase text-xs tracking-widest" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full border-gray-200 focus:border-geo-teal focus:ring-geo-teal rounded-xl"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="official@geosurvey.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Session Password" class="text-geo-navy font-bold uppercase text-xs tracking-widest" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full border-gray-200 focus:border-geo-teal focus:ring-geo-teal rounded-xl"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-geo-navy focus:ring-geo-teal" />
                    <span class="ms-2 text-sm text-geo-slate">Maintain Session</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs font-bold text-geo-blue hover:underline uppercase tracking-wider"
                >
                    Lost Access?
                </Link>
            </div>

            <div class="pt-4">
                <PrimaryButton 
                    class="w-full justify-center py-4 bg-geo-navy hover:bg-geo-light-navy text-white font-bold rounded-xl shadow-lg transition-all active:scale-[0.98]" 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing">
                    Initialize Session
                </PrimaryButton>
            </div>
        </form>
        
        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-xs text-geo-slate uppercase tracking-widest">SAD Flow Validation Enabled</p>
        </div>
    </GuestLayout>
</template>
