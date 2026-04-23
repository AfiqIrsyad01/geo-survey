<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MapViewer from '@/Components/MapViewer.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recent_activities: { type: Array, default: () => [] },
    chart_data: { type: Object, default: () => ({ monthly: [], status: [] }) },
    users: { type: Array, default: () => [] },
    pending_details: { type: Array, default: () => [] },
    projects_spatial: { type: Array, default: () => [] },
    unread_notifications: { type: Array, default: () => [] }
});

import { router } from '@inertiajs/vue3';

const markAsRead = (surveyId) => {
    const notification = props.unread_notifications.find(n => n.message.includes(`id: ${surveyId}`) || n.message.includes(`for project: ${props.recent_activities.find(a => a.id === surveyId)?.project?.name}`));
};

const hasUnread = (activityId) => {
    return props.unread_notifications.some(n => n.message.toLowerCase().includes(activityId.toString()) || n.message.includes(props.recent_activities.find(a => a.id === activityId)?.project?.name));
};

const handleActivityClick = (activityId) => {
    const activity = props.recent_activities.find(a => a.id === activityId);
    const notification = props.unread_notifications.find(n => 
        n.message.includes(activity?.project?.name) || n.message.includes(activityId.toString())
    );

    if (notification) {
        router.patch(route('notifications.read', notification.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.get(route('surveys.show', activityId));
            }
        });
    } else {
        router.get(route('surveys.show', activityId));
    }
};

onMounted(() => {
    try {
        if (typeof Chart === 'undefined') {
            setTimeout(initializeCharts, 1000);
            return;
        }
        initializeCharts();
    } catch (e) {
        console.error('Dashboard Error:', e);
    }
});

const userRole = computed(() => props.stats.user_role || 'staff');

const rolePersona = computed(() => {
    switch (userRole.value) {
        case 'admin':
            return {
                title: 'Strategic Oversight Console',
                subtitle: 'Total system governance and asset lifecycle management.',
                banner: 'bg-gradient-to-r from-geo-navy to-slate-900',
                accent: 'text-geo-teal',
                trendLabel: 'System Volume Trends',
                statusLabel: 'Global Audit Integrity',
                feedTitle: 'Operational Event Feed'
            };
        case 'hod':
            return {
                title: 'Compliance Control Hub',
                subtitle: 'Operational integrity oversight and technical validation.',
                banner: 'bg-gradient-to-r from-teal-900 to-geo-navy',
                accent: 'text-teal-400',
                trendLabel: 'Operational Throughput',
                statusLabel: 'Technical Review Distribution',
                feedTitle: 'Validation Queue'
            };
        default:
            return {
                title: 'Personnel Performance Hub',
                subtitle: 'Field activity tracking and personal evidence repository.',
                banner: 'bg-gradient-to-r from-slate-700 via-geo-navy to-slate-800',
                accent: 'text-blue-400',
                trendLabel: 'Individual Productivity Index',
                statusLabel: 'Personal Compliance Standing',
                feedTitle: 'My Operational Log'
            };
    }
});

const initializeCharts = () => {
    if (typeof Chart === 'undefined') return;

    const trendCtx = document.getElementById('surveyTrendsChart')?.getContext('2d');
    if (trendCtx && props.chart_data?.monthly?.length > 0) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: props.chart_data.monthly.map(d => d.month || 'Unknown'),
                datasets: [{
                    label: 'Intelligence Logs',
                    data: props.chart_data.monthly.map(d => d.count || 0),
                    backgroundColor: '#0d9488',
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' } }, 
                    x: { grid: { display: false } } 
                }
            }
        });
    }

    const statusCtx = document.getElementById('statusDistChart')?.getContext('2d');
    if (statusCtx && props.chart_data?.status?.length > 0) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: props.chart_data.status.map(d => (d.status || 'N/A').toUpperCase()),
                datasets: [{
                    data: props.chart_data.status.map(d => d.count || 0),
                    backgroundColor: ['#0d9488', '#fbbf24', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            usePointStyle: true, 
                            boxWidth: 6, 
                            font: { weight: 'bold', size: 10 },
                            padding: 20
                        } 
                    } 
                },
                cutout: '72%'
            }
        });
    }
};

const statItems = computed(() => {
    const baseStats = [
        { name: 'Operational Zones', value: props.stats.active_projects, svg: '<path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />', color: 'text-blue-500' },
        { name: userRole.value === 'staff' ? 'Personal Logs' : 'Total Intelligence', value: props.stats.total_surveys, svg: '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />', color: 'text-indigo-500' },
    ];

    if (props.stats.user_role === 'admin') {
        baseStats.push({ name: 'Personnel Units', value: props.stats.staff_count, svg: '<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />', color: 'text-geo-teal' });
        baseStats.push({ name: 'System Integrity', value: '98%', svg: '<path d="M13 10V3L4 14h7v7l9-11h-7z" />', color: 'text-yellow-500' });
    } else if (props.stats.user_role === 'hod') {
        baseStats.push({ name: 'Pending Review', value: props.stats.pending_approvals, svg: '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />', color: 'text-red-500' });
        baseStats.push({ name: 'Operational QC', value: '8.4', svg: '<path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />', color: 'text-green-500' });
    } else {
        baseStats.push({ name: 'Awaiting Audit', value: props.stats.user_pending || 0, svg: '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />', color: 'text-orange-500' });
        baseStats.push({ name: 'Precision Match', value: '100%', svg: '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />', color: 'text-emerald-500' });
    }

    return baseStats;
});
</script>

<template>
    <Head :title="rolePersona.title" />

    <AuthenticatedLayout>
        <template #header>
            <div :class="rolePersona.banner" class="p-8 rounded-3xl text-white shadow-2xl relative overflow-hidden transition-all duration-700">
                <!-- Branding Decoration -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-geo-teal/20 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-center md:text-left">
                        <div class="flex items-center gap-2 justify-center md:justify-start mb-2">
                            <span class="px-3 py-1 rounded-full bg-white/20 text-[10px] font-black uppercase tracking-widest border border-white/20 backdrop-blur-md">
                                {{ userRole }} MODE
                            </span>
                            <span class="w-1 h-1 rounded-full bg-white/50"></span>
                            <span class="text-[10px] font-bold text-white/50 tracking-widest uppercase">System v2.4</span>
                        </div>
                        <h2 class="font-black text-3xl md:text-4xl tracking-tighter mb-1">
                            {{ rolePersona.title }}
                        </h2>
                        <p class="text-white/70 font-medium italic text-sm md:text-base">
                            Welcome, <span :class="rolePersona.accent" class="font-bold border-b-2 border-current pb-0.5">{{ $page.props.auth.user.name }}</span>. {{ rolePersona.subtitle }}
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap justify-center gap-3">
                        <Link v-if="userRole === 'admin'" :href="route('projects.index')" 
                            class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white border border-white/20 px-6 py-3 rounded-2xl text-sm font-black tracking-widest transition-all uppercase">
                            Zone Oversight
                        </Link>
                        <Link :href="route('surveys.create')" 
                            class="bg-geo-teal text-geo-navy px-8 py-3 rounded-2xl text-sm font-black shadow-xl hover:scale-105 active:scale-95 transition-all uppercase tracking-widest">
                            + New Intelligence Log
                        </Link>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Role Specific Alert/Action -->
                <div v-if="$page.props.auth.user.role === 'hod' && stats.pending_approvals > 0" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-red-800 font-bold">You have {{ stats.pending_approvals }} site surveys awaiting technical approval.</span>
                    </div>
                    <Link :href="route('surveys.index', { status: 'pending' })" class="text-red-700 font-bold text-sm underline hover:text-red-800">Clear Queue →</Link>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div v-for="stat in statItems" :key="stat.name" 
                        class="bg-white p-6 rounded-2xl shadow-glass border border-gray-100 hover:scale-105 transition-all duration-300 group">
                        <div class="flex items-center justify-between mb-2">
                            <div :class="stat.color" class="p-3 bg-opacity-10 rounded-xl bg-current transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="stat.svg"></svg>
                            </div>
                        </div>
                        <p class="text-3xl font-extrabold text-geo-navy group-hover:text-geo-teal transition-colors">{{ stat.value }}</p>
                        <p class="text-xs font-bold text-geo-slate uppercase tracking-widest mt-1">{{ stat.name }}</p>
                    </div>
                </div>

                <!-- NEW: Operational Analytics (Charts) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Survey Trends (Bar) -->
                    <div class="md:col-span-2 bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-geo-navy">{{ rolePersona.trendLabel }}</h3>
                            <span class="text-[10px] font-bold text-geo-teal uppercase tracking-widest bg-teal-50 px-3 py-1 rounded-full">Intelligence Velocity</span>
                        </div>
                        <div class="h-64">
                            <canvas id="surveyTrendsChart"></canvas>
                        </div>
                    </div>

                    <!-- Survey Status (Doughnut) -->
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-geo-navy">{{ rolePersona.statusLabel }}</h3>
                            <p class="text-xs text-geo-slate font-medium italic">Operational clearance profile</p>
                        </div>
                        <div class="h-64">
                            <canvas id="statusDistChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 text-sm">
                    
                    <!-- Map Preview -->
                    <div class="lg:col-span-2 bg-white p-2 rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-geo-navy">Geospatial Intelligence Map</h3>
                            <span class="text-[10px] text-geo-slate font-bold uppercase tracking-tighter">Live Spatial Telemetry</span>
                        </div>
                        <div class="h-[450px]">
                            <MapViewer readOnly :projects="projects_spatial" />
                        </div>
                    </div>

                    <!-- Side Panel: Activity & Quick Actions -->
                    <div class="space-y-8">
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-geo-navy mb-5 flex items-center">
                                <span class="w-2 h-2 rounded-full bg-geo-teal mr-2"></span>
                                {{ rolePersona.feedTitle }}
                            </h3>
                            <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="activity in recent_activities" :key="activity.id" 
                                    @click="handleActivityClick(activity.id)"
                                    class="flex items-start space-x-3 p-4 hover:bg-gray-50 rounded-2xl transition border border-transparent hover:border-gray-200 cursor-pointer relative group">
                                    
                                    <!-- Red Dot Badge -->
                                    <div v-if="hasUnread(activity.id)" class="absolute top-3 right-3 w-3 h-3 bg-red-500 rounded-full border-2 border-white shadow-sm z-10 animate-pulse"></div>

                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-geo-navy flex items-center justify-center text-white text-xs font-bold">
                                        #{{ String(activity.id).padStart(2, '0') }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <p class="font-bold text-geo-navy truncate w-32">{{ activity.project?.name || 'Unknown Project' }}</p>
                                            <span :class="activity.status === 'approved' ? 'text-green-600' : 'text-yellow-600'" class="text-[9px] font-black uppercase tracking-tighter">{{ activity.status }}</span>
                                        </div>
                                        <p class="text-[11px] text-geo-slate leading-tight mt-0.5">{{ activity.user?.name || 'Unknown User' }} • {{ $formatDate(activity.created_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="recent_activities.length === 0" class="py-12 text-center text-geo-slate italic">
                                    No field movements detected.
                                </div>
                            </div>
                            <Link :href="route('surveys.index')" class="block text-center mt-6 py-2 rounded-xl text-xs font-bold text-geo-blue border border-geo-blue/20 hover:bg-geo-blue hover:text-white transition-all">
                                ACCESS FULL LOGS
                            </Link>
                        </div>

                        <!-- Role Specific Card -->
                        <div v-if="$page.props.auth.user.role === 'staff'" class="bg-geo-navy p-6 rounded-3xl shadow-xl text-white">
                            <h3 class="text-sm font-bold text-geo-teal mb-2 uppercase tracking-widest">Field Guidance</h3>
                            <p class="text-xs text-gray-300 leading-relaxed mb-4">Ensure your GPS marker turns <span class="text-geo-teal font-bold underline">Teal</span> before submitting evidence to guarantee survey validity.</p>
                            <Link :href="route('surveys.create')" class="inline-block text-xs font-bold text-geo-navy bg-geo-teal px-4 py-2 rounded-lg">START SURVEY NOW</Link>
                        </div>
                    </div>
                </div>

                <!-- NEW: Role Specific Bottom Widgets -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Admin: User Management Overview -->
                    <div v-if="$page.props.auth.user.role === 'admin'" class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-geo-navy">Active Personnel</h3>
                            <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">Fleet View</span>
                        </div>
                        <div class="space-y-4">
                            <div v-for="user in users" :key="user.id" class="flex items-center justify-between p-3 border border-gray-50 rounded-2xl hover:bg-gray-50 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs uppercase">
                                        {{ user.name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-geo-navy">{{ user.name }}</p>
                                        <p class="text-[10px] text-geo-slate">{{ user.email }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-geo-slate uppercase bg-gray-100 px-2 py-0.5 rounded">{{ user.role }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- HOD: Pending Decisions -->
                    <div v-if="$page.props.auth.user.role === 'hod'" class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-geo-navy">Strategic Approval Queue</h3>
                            <span class="bg-red-50 text-red-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">Critical Path</span>
                        </div>
                        <div class="space-y-4">
                            <Link v-for="item in pending_details" :key="item.id" :href="route('surveys.show', item.id)" class="block p-4 border-2 border-red-50 rounded-2xl hover:border-red-200 transition bg-red-50/10">
                                <div class="flex justify-between items-center">
                                    <p class="font-bold text-geo-navy uppercase text-xs">{{ item.project?.name || 'Audit Required' }}</p>
                                    <span class="text-[9px] font-black text-red-600">PENDING ACTION</span>
                                </div>
                                <p class="text-[11px] text-geo-slate mt-1">Submitted by: <span class="font-bold">{{ item.user?.name || 'Field Unit' }}</span></p>
                            </Link>
                            <div v-if="pending_details.length === 0" class="py-8 text-center text-geo-slate italic text-xs">
                                No critical decisions pending at this time.
                            </div>
                        </div>
                    </div>

                    <!-- Staff: Performance Metrics -->
                    <div v-if="$page.props.auth.user.role === 'staff'" class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-geo-navy mb-6">Field Performance Metrics</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                                <p class="text-[10px] font-bold text-emerald-600 uppercase">GPS Precision</p>
                                <p class="text-2xl font-black text-emerald-800">0.8m</p>
                            </div>
                            <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                                <p class="text-[10px] font-bold text-blue-600 uppercase">In-Zone Rate</p>
                                <p class="text-2xl font-black text-blue-800">100%</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #0a192f;
    border-radius: 10px;
}
</style>

