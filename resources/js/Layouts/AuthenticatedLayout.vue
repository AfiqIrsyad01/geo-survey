<script setup>
import { ref, onBeforeMount } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

onBeforeMount(() => {
    // Client-side verification for bfcache/back-button consistency
    // This runs BEFORE the component is mounted to the DOM
    const page = usePage();
    if (!page.props.auth || !page.props.auth.user) {
        window.location.href = route('login');
    }
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')" class="flex items-center">
                                    <ApplicationLogo
                                        class="block h-7 w-auto fill-current text-geo-navy"
                                        style="height: 28px !important;"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="flex items-center gap-2">
                                    <i class="fa-solid fa-chart-line text-geo-teal"></i>
                                    Dashboard
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.role === 'admin'" :href="route('projects.index')" :active="route().current('projects.*')" class="flex items-center gap-2">
                                    <i class="fa-solid fa-map-location-dot text-geo-blue"></i>
                                    Project Zones
                                </NavLink>
                                <NavLink :href="route('surveys.index')" :active="route().current('surveys.*')" class="flex items-center gap-2">
                                    <i class="fa-solid fa-clipboard-check text-geo-slate"></i>
                                    Field Surveys
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.role === 'admin'" :href="route('register')" :active="route().current('register')" class="flex items-center gap-2">
                                    <i class="fa-solid fa-users-gear text-amber-500"></i>
                                    Manage Personnel
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.reports')" :active="route().current('admin.reports')" class="flex items-center gap-2">
                                    <i class="fa-solid fa-file-invoice-dollar text-emerald-500"></i>
                                    Corporate Reports
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm leading-4 font-bold rounded-xl text-geo-navy bg-gray-50 hover:bg-white hover:border-geo-teal transition ease-in-out duration-150"
                                            >
                                                <div class="flex flex-col items-end me-3">
                                                    <span class="text-xs font-black uppercase tracking-tighter opacity-50">{{ $page.props.auth.user.role }}</span>
                                                    <span>{{ $page.props.auth.user.name }}</span>
                                                </div>

                                                <i class="fa-solid fa-circle-user fa-lg text-geo-navy"></i>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="block px-4 py-2 text-xs text-gray-400 uppercase tracking-widest font-bold">
                                            Operational Access
                                        </div>
                                        <DropdownLink :href="route('profile.edit')"> 
                                            <i class="fa-solid fa-gears me-2"></i> Security Profile 
                                        </DropdownLink>
                                        <div class="border-t border-gray-100"></div>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 font-bold">
                                            <i class="fa-solid fa-door-open me-2"></i> Terminate Session
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                            >
                                <i :class="showingNavigationDropdown ? 'fa-solid fa-xmark' : 'fa-solid fa-bars-staggered'" class="fa-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden bg-gray-50 border-t border-gray-100 shadow-inner"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            <i class="fa-solid fa-chart-line me-2"></i> Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.role === 'admin'" :href="route('projects.index')" :active="route().current('projects.*')">
                            <i class="fa-solid fa-map-location-dot me-2"></i> Projects
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('surveys.index')" :active="route().current('surveys.*')">
                            <i class="fa-solid fa-clipboard-check me-2"></i> Surveys
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.role === 'admin'" :href="route('register')" :active="route().current('register')">
                            <i class="fa-solid fa-users-gear me-2"></i> Manage Personnel
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.reports')" :active="route().current('admin.reports')">
                            <i class="fa-solid fa-file-invoice-dollar me-2"></i> Corporate Reports
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="px-4 flex items-center">
                            <div class="bg-geo-navy text-white h-10 w-10 rounded-full flex items-center justify-center font-bold me-3">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-bold text-base text-geo-navy">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-xs text-gray-500 uppercase tracking-widest">{{ $page.props.auth.user.role }}</div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> <i class="fa-solid fa-gears me-2"></i> Profile </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="text-red-600">
                                <i class="fa-solid fa-door-open me-2"></i> Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <!-- Flash Notification System -->
                <transition name="toast">
                    <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="fixed top-4 right-4 z-[2000] flex flex-col gap-2">
                        <div v-if="$page.props.flash?.success" class="flex items-center p-4 bg-green-50 text-green-800 rounded-2xl shadow-xl border border-green-100 min-w-[300px]">
                            <div class="p-2 bg-green-500 text-white rounded-xl mr-3">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div class="flex-1 font-bold text-sm">{{ $page.props.flash.success }}</div>
                        </div>
                        <div v-if="$page.props.flash?.error" class="flex items-center p-4 bg-red-50 text-red-800 rounded-2xl shadow-xl border border-red-100 min-w-[300px]">
                            <div class="p-2 bg-red-500 text-white rounded-xl mr-3">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div class="flex-1 font-bold text-sm">{{ $page.props.flash.error }}</div>
                        </div>
                    </div>
                </transition>

                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.toast-enter-active, .toast-leave-active {
    transition: all 0.5s ease;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.toast-leave-to {
    opacity: 0;
    transform: scale(0.9);
}
</style>
