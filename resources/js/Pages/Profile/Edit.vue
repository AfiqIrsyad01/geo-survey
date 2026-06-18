<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl text-geo-navy dark:text-white leading-tight transition-colors">Personal Identity Configuration</h2>
        </template>

        <div class="py-12 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-[var(--geo-surface)] shadow-sm border border-[var(--geo-border)] sm:rounded-[2rem] transition-colors duration-500">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="p-4 sm:p-8 bg-[var(--geo-surface)] shadow-sm border border-[var(--geo-border)] sm:rounded-[2rem] transition-colors duration-500">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div v-if="$page.props.auth.user.role === 'staff'" class="p-4 sm:p-8 bg-[var(--geo-surface)] shadow-sm border border-[var(--geo-border)] sm:rounded-[2rem] transition-colors duration-500">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
