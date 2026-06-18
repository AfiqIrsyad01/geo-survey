<script setup>
import { ref, onBeforeMount, onMounted, onUnmounted, computed, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import ChatbotWidget from '@/Components/ChatbotWidget.vue';
import { useOfflineStore } from '@/Utils/offlineStore';

const showingNavigationDropdown = ref(false);
const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

onBeforeMount(() => {
    // Client-side verification for bfcache/back-button consistency
    // This runs BEFORE the component is mounted to the DOM
    if (!page.props.auth || !page.props.auth.user) {
        window.location.href = route('login');
    }
});

const pageProps = computed(() => page.props);

watch(() => pageProps.value.flash, (flash) => {
    if (flash?.success) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'OPERATIONAL SUCCESS',
            text: flash.success,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            background: '#ffffff',
            color: '#0a192f',
            iconColor: '#10b981',
        });
    }
    if (flash?.error) {
         Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'MISSION ANOMALY',
            text: flash.error,
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            background: '#ffffff',
            color: '#0a192f',
            iconColor: '#ef4444',
        });
    }
}, { deep: true });

const theme = computed(() => {
    switch (userRole.value) {
        case 'admin':
            return {
                accentBorder: 'border-b-4 border-amber-500',
                navIcon: 'text-amber-500',
                avatarBg: 'bg-amber-100 text-amber-600',
                roleLabel: 'bg-amber-100 text-amber-700'
            };
        case 'hod':
            return {
                accentBorder: 'border-b-4 border-emerald-500',
                navIcon: 'text-emerald-500',
                avatarBg: 'bg-emerald-100 text-emerald-600',
                roleLabel: 'bg-emerald-100 text-emerald-700'
            };
        default:
            return {
                accentBorder: 'border-b-4 border-geo-teal',
                navIcon: 'text-geo-teal',
                avatarBg: 'bg-geo-navy text-white',
                roleLabel: 'bg-blue-100 text-blue-700'
            };
    }
});

const unreadNotifications = ref([]);
const knownNotificationIds = ref(new Set());
let pollingInterval = null;

const fetchNotifications = async () => {
    try {
        const response = await fetch(route('notifications.unread'));
        const data = await response.json();
        
        // Identify brand new notifications
        const newNotifications = data.filter(n => !knownNotificationIds.value.has(n.id));
        
        if (newNotifications.length > 0) {
            newNotifications.forEach(notification => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'MISSION UPDATE',
                    text: notification.message,
                    showConfirmButton: false,
                    timer: 6000,
                    timerProgressBar: true,
                    background: '#0a192f',
                    color: '#ffffff',
                    iconColor: '#4fd1c5',
                });
                knownNotificationIds.value.add(notification.id);
            });
        }
        
        unreadNotifications.value = data;
        // Update the known IDs set
        data.forEach(n => knownNotificationIds.value.add(n.id));
    } catch (e) {
        // Silent fail
    }
};

const markAsRead = (id) => {
    router.patch(route('notifications.read', id), {}, {
        preserveScroll: true,
        onSuccess: () => fetchNotifications()
    });
};

const markAllAsRead = () => {
    if (unreadNotifications.value.length === 0) return;
    
    // Clear locally for instant feedback
    unreadNotifications.value = [];
    
    router.patch(route('notifications.markAll'), {}, {
        preserveScroll: true,
        onSuccess: () => fetchNotifications()
    });
};

const { isOnline, pendingCount, getPendingSurveys, removeSurveyOffline } = useOfflineStore();
const isSyncing = ref(false);

const syncOfflineSurveys = async () => {
    if (!isOnline.value || pendingCount.value === 0 || isSyncing.value) return;
    
    isSyncing.value = true;
    const surveys = await getPendingSurveys();
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        title: 'STAGED DATA DETECTED',
        text: `Regained connectivity. Synchronizing ${surveys.length} log(s) to core...`,
        icon: 'info',
        showConfirmButton: false,
        timer: 3000,
        background: '#0a192f',
        color: '#ffffff'
    });

    for (const survey of surveys) {
        try {
            // Reconstruct FormData from stored record
            const formData = new FormData();
            formData.append('project_id', survey.project_id);
            formData.append('lat', survey.lat);
            formData.append('lng', survey.lng);
            if (survey.asl) formData.append('asl', survey.asl);
            
            // Re-attach converted blobs/files
            survey.images.forEach((img) => {
                formData.append('images[]', img.blob, img.name);
            });

            // Use direct fetch for background sync to avoid Inertia page reload loops
            const response = await fetch(route('surveys.store'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (response.ok) {
                await removeSurveyOffline(survey.id);
            }
        } catch (e) {
            console.error('Background Sync Error:', e);
        }
    }

    isSyncing.value = false;
    
    if (pendingCount.value === 0) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'SYNCHRONIZATION COMPLETE',
            text: 'All offline telemetry has been secured by the core.',
            showConfirmButton: false,
            timer: 3000,
            background: '#0a192f',
            color: '#ffffff'
        });
    }
};

watch(isOnline, (newVal) => {
    if (newVal) {
        syncOfflineSurveys();
    }
});

const showTimeoutWarning = ref(false);
const countdownSeconds = ref(120);

let idleTime = 0;
let idleInterval = null;
const maxIdleTime = 30 * 60 * 1000; // 30 minutes in milliseconds
const warningThreshold = 28 * 60 * 1000; // 28 minutes

const resetIdleTime = () => {
    idleTime = 0;
    if (showTimeoutWarning.value) {
        showTimeoutWarning.value = false;
        countdownSeconds.value = 120;
    }
};

onMounted(() => {
    fetchNotifications();
    
    // Poll notifications every 45 secs, but only if user is still active
    pollingInterval = setInterval(() => {
        if (idleTime < maxIdleTime) {
            fetchNotifications();
        }
    }, 45000);
    
    // Timer to increment idleTime every 1 second for accurate countdown
    idleInterval = setInterval(() => {
        idleTime += 1000;
        
        if (idleTime >= warningThreshold && idleTime < maxIdleTime) {
            showTimeoutWarning.value = true;
            countdownSeconds.value = Math.floor((maxIdleTime - idleTime) / 1000);
        }

        if (idleTime >= maxIdleTime) {
            // Once limit is reached, clear intervals and enforce logout
            clearInterval(idleInterval);
            clearInterval(pollingInterval);
            
            if (window.Swal) {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'SESSION TERMINATED',
                    text: 'Disconnected automatically due to 30 minutes of inactivity.',
                    showConfirmButton: false,
                    timer: 5000,
                    background: '#0a192f',
                    color: '#ffffff',
                });
            }
            router.post(route('logout'));
        }
    }, 1000);

    
    // Initial sync check if already online
    if (isOnline.value) {
        setTimeout(syncOfflineSurveys, 2000);
    }

    // Attach listeners for user activity
    window.addEventListener('mousemove', resetIdleTime);
    window.addEventListener('keypress', resetIdleTime);
    window.addEventListener('mousedown', resetIdleTime);
    window.addEventListener('touchstart', resetIdleTime);
    window.addEventListener('scroll', resetIdleTime);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
    if (idleInterval) clearInterval(idleInterval);
    
    // Clean up event listeners to prevent memory leaks
    window.removeEventListener('mousemove', resetIdleTime);
    window.removeEventListener('keypress', resetIdleTime);
    window.removeEventListener('mousedown', resetIdleTime);
    window.removeEventListener('touchstart', resetIdleTime);
    window.removeEventListener('scroll', resetIdleTime);
});
</script>

<template>
    <div>
        <!-- SECURITY COMPLIANCE: SESSION TIMEOUT BANNER -->
        <Transition
            enter-active-class="transition ease-out duration-500"
            enter-from-class="transform -translate-y-full opacity-0"
            leave-active-class="transition ease-in duration-300"
            leave-to-class="transform -translate-y-full opacity-0"
        >
            <div v-if="showTimeoutWarning" class="fixed top-0 left-0 right-0 z-[9999] flex justify-center p-6 pointer-events-none">
                <div class="bg-red-600/95 backdrop-blur-md border border-red-400 shadow-[0_10px_40px_rgba(220,38,38,0.4)] rounded-2xl px-6 py-4 flex items-center gap-5 translate-y-2 pointer-events-auto">
                    <div class="bg-white/20 p-3 rounded-full animate-pulse shadow-inner">
                        <i class="fa-solid fa-user-shield text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-black uppercase tracking-widest text-sm leading-tight">Security Compliance Check</h3>
                        <p class="text-red-100 text-xs font-medium mt-0.5">Session expiring due to inactivity. Move mouse to sustain access.</p>
                    </div>
                    <div class="ml-2 pl-6 border-l border-red-500/50 flex flex-col items-center min-w-[70px]">
                        <span class="text-3xl font-black text-white font-mono leading-none tracking-tighter">{{ countdownSeconds }}</span>
                        <span class="text-[9px] text-red-200 uppercase tracking-widest font-black mt-1">Seconds</span>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="min-h-screen bg-[var(--geo-bg)] transition-colors duration-500">
            <nav :class="theme.accentBorder" class="sticky top-0 z-[100] bg-white/80 dark:bg-geo-navy/80 backdrop-blur-md shadow-sm border-b border-gray-100 dark:border-white/5 transition-all duration-500">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')" class="flex items-center">
                                    <ApplicationLogo
                                        class="block h-7 w-auto fill-current text-geo-navy dark:text-geo-teal"
                                        style="height: 28px !important;"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="flex items-center gap-2">
                                    <i :class="theme.navIcon" class="fa-solid fa-chart-line transition-colors duration-500"></i>
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

                        <!-- OFFLINE VAULT INDICATOR (Phase 6 Final) -->
                        <div class="hidden lg:flex items-center gap-6">
                            <div v-if="pendingCount > 0" class="flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-500 animate-pulse">
                                <i class="fa-solid fa-vault text-xs"></i>
                                <span class="text-[9px] font-black uppercase tracking-[0.2em] whitespace-nowrap">{{ pendingCount }} LOGS STAGED OFFLINE</span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5">
                                    <div :class="isOnline ? 'bg-geo-teal shadow-geo-teal/50' : 'bg-red-500 shadow-red-500/50'" class="w-1.5 h-1.5 rounded-full shadow-[0_0_8px_rgba(0,0,0,0.5)]"></div>
                                    <span class="text-[8px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ isOnline ? 'ACTIVE' : 'OFFLINE' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative flex items-center gap-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-4 py-2 border border-gray-200 dark:border-white/10 text-sm leading-4 font-bold rounded-xl text-geo-navy dark:text-gray-300 bg-gray-50 dark:bg-white/5 hover:bg-white hover:border-geo-teal transition ease-in-out duration-150"
                                            >
                                                <div class="flex flex-col items-end me-3 text-right">
                                                    <span :class="theme.roleLabel" class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest mb-0.5 transition-colors duration-500">{{ $page.props.auth.user.role }}</span>
                                                    <span class="text-xs font-bold">{{ $page.props.auth.user.name }}</span>
                                                </div>

                                                <i class="fa-solid fa-circle-user fa-lg text-geo-navy dark:text-geo-teal"></i>
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
                                        <div class="border-t border-gray-100 dark:border-white/5"></div>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 font-bold">
                                            <i class="fa-solid fa-door-open me-2"></i> Terminate Session
                                        </DropdownLink>
                                    </template>
                                </Dropdown>

                                <!-- NOTIFICATION BEACON -->
                                <div class="relative">
                                    <Dropdown align="right" width="80">
                                        <template #trigger>
                                            <button 
                                                type="button" 
                                                @click="markAllAsRead"
                                                class="p-2 text-geo-slate dark:text-gray-400 hover:text-geo-teal transition-colors relative group"
                                            >
                                                <i class="fa-solid fa-bell fa-lg"></i>
                                                <span v-if="unreadNotifications.length > 0" class="absolute top-1.5 right-1.5 flex h-3.5 w-3.5">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-geo-teal opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-geo-teal text-[8px] items-center justify-center text-geo-navy font-black">
                                                        {{ unreadNotifications.length }}
                                                    </span>
                                                </span>
                                            </button>
                                        </template>

                                        <template #content>
                                            <div class="block px-4 py-2 text-xs font-black text-geo-navy dark:text-gray-400 uppercase tracking-widest bg-gray-50/50 dark:bg-white/5 border-b border-gray-100 dark:border-white/5">
                                                Operational Intel
                                            </div>
                                            
                                            <div class="max-h-96 overflow-y-auto">
                                                <div v-if="unreadNotifications.length === 0" class="px-8 py-10 text-center">
                                                    <i class="fa-solid fa-satellite text-gray-200 dark:text-gray-700 text-3xl mb-4"></i>
                                                    <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">No active alerts</p>
                                                </div>
                                                
                                                <button 
                                                    v-for="n in unreadNotifications" 
                                                    :key="n.id" 
                                                    @click="markAsRead(n.id)"
                                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 border-b border-gray-50 dark:border-white/5 transition-colors group"
                                                >
                                                    <div class="flex gap-3">
                                                        <div class="mt-1">
                                                            <div class="w-2 h-2 rounded-full bg-geo-teal shadow-[0_0_8px_#4fd1c5]"></div>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-xs font-bold text-geo-navy dark:text-white leading-tight mb-1">{{ n.message }}</p>
                                                            <p class="text-[9px] text-gray-400 font-medium uppercase tracking-tighter">Signal Received: {{ new Date(n.created_at).toLocaleTimeString() }}</p>
                                                        </div>
                                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <i class="fa-solid fa-circle-check text-geo-teal"></i>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>

                                            <div v-if="unreadNotifications.length > 0" class="p-2 bg-gray-50 dark:bg-white/5">
                                                <p class="text-[8px] text-center text-gray-400 uppercase font-black tracking-widest">Click alert to dismiss from hub</p>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- THEME TOGGLE -->
                                <ThemeToggle />
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
                    class="sm:hidden bg-white dark:bg-geo-navy border-t border-gray-100 dark:border-white/5 shadow-inner transition-colors duration-500"
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
                            <div :class="theme.avatarBg" class="h-10 w-10 rounded-full flex items-center justify-center font-bold me-3 transition-colors duration-500 shadow-sm border border-white/20">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-bold text-base text-geo-navy dark:text-white transition-colors duration-500">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ $page.props.auth.user.role }}</div>
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
            <header class="bg-white dark:bg-geo-light-navy shadow-sm border-b border-gray-100 dark:border-white/5 transition-colors duration-500" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
        
        <!-- GLOBAL CHATBOT WIDGET -->
        <ChatbotWidget />
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
