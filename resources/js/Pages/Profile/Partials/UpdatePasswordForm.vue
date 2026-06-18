<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = async () => {
    if (!canSubmit.value) return; // Strict DOM bypass protection

    if (window.Swal) {
        const result = await window.Swal.fire({
            title: 'Authorize Credential Rotation?',
            text: 'You are about to enforce new security credentials across all active sessions.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d9488', // geo-teal
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Enforce Update',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#0f172a'
        });

        if (!result.isConfirmed) return;
    }

    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (window.Swal) {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Password Updated',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            }
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};

import { computed } from 'vue';

const hasLength = computed(() => form.password.length >= 8);
const hasNumber = computed(() => /[0-9]/.test(form.password));
const hasSymbol = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(form.password));
const passwordsMatch = computed(() => form.password === form.password_confirmation && form.password.length > 0);
const canSubmit = computed(() => {
    return form.current_password.length > 0 && 
           hasLength.value && 
           hasNumber.value && 
           hasSymbol.value && 
           passwordsMatch.value && 
           !form.processing;
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-black text-geo-navy dark:text-white transition-colors">Credential Rotation</h2>

            <p class="mt-1 text-sm text-geo-slate dark:text-gray-400 italic transition-colors">
                Ensure your account is using a long, random password to stay secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <InputLabel for="current_password" value="Current Password" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />

                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password" value="New Password" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password" class="mt-2" />

                <!-- Real-time Guidance -->
                <div class="mt-3 flex flex-wrap gap-2">
                    <span :class="hasLength ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10' : 'text-gray-400 bg-gray-50 dark:bg-white/5'" class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-widest transition-colors flex items-center gap-1">
                        <i :class="hasLength ? 'fa-solid fa-check' : 'fa-solid fa-circle-notch'"></i> 8+ Chars
                    </span>
                    <span :class="hasNumber ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10' : 'text-gray-400 bg-gray-50 dark:bg-white/5'" class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-widest transition-colors flex items-center gap-1">
                        <i :class="hasNumber ? 'fa-solid fa-check' : 'fa-solid fa-circle-notch'"></i> 1 Number
                    </span>
                    <span :class="hasSymbol ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10' : 'text-gray-400 bg-gray-50 dark:bg-white/5'" class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-widest transition-colors flex items-center gap-1">
                        <i :class="hasSymbol ? 'fa-solid fa-check' : 'fa-solid fa-circle-notch'"></i> 1 Symbol
                    </span>
                </div>
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password_confirmation" class="mt-2" />
                
                <p v-if="form.password_confirmation && !passwordsMatch" class="text-xs font-bold text-red-500 mt-2">Passwords do not match.</p>
                <p v-if="passwordsMatch" class="text-xs font-bold text-emerald-500 mt-2"><i class="fa-solid fa-check-double mr-1"></i> Passwords match.</p>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="!canSubmit" :class="{ 'opacity-50 cursor-not-allowed': !canSubmit }">Save</PrimaryButton>
            </div>
        </form>
    </section>
</template>
