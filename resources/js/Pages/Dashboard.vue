<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MapViewer from '@/Components/MapViewer.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import * as turf from '@turf/turf';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recent_activities: { type: Array, default: () => [] },
    chart_data: { type: Object, default: () => ({ monthly: [], status: [] }) },
    users: { type: Array, default: () => [] },
    pending_details: { type: Array, default: () => [] },
    projects_spatial: { type: Array, default: () => [] },
    unread_notifications: { type: Array, default: () => [] }
});

const focusedProjectId = ref(null);

const isHeatmapActive = ref(false);
const spatialIntelligence = ref(null);
const isLoadingHeatmap = ref(false);

const toggleHeatmap = async () => {
    isHeatmapActive.value = !isHeatmapActive.value;
    
    if (isHeatmapActive.value && !spatialIntelligence.value) {
        isLoadingHeatmap.value = true;
        try {
            const response = await fetch(route('api.analytics.spatial'));
            spatialIntelligence.value = await response.json();
        } catch (e) {
            console.error("Spatial Intelligence Error:", e);
        } finally {
            isLoadingHeatmap.value = false;
        }
    }
};

const markAsRead = (surveyId) => {
    if (!props.unread_notifications) return;
    const activity = props.recent_activities.find(a => a.id === surveyId);
    const notification = props.unread_notifications.find(n => 
        n.message.includes(`id: ${surveyId}`) || 
        (activity?.project?.name && n.message.includes(`for project: ${activity.project.name}`))
    );
};

const hasUnread = (activityId) => {
    if (!props.unread_notifications) return false;
    const activity = props.recent_activities.find(a => a.id === activityId);
    return props.unread_notifications.some(n => 
        n.message.toLowerCase().includes(activityId.toString()) || 
        (activity?.project?.name && n.message.includes(activity.project.name))
    );
};

const handleActivityClick = (activityId) => {
    const activity = props.recent_activities.find(a => a.id === activityId);
    const notification = props.unread_notifications?.find(n => 
        (activity?.project?.name && n.message.includes(activity.project.name)) || 
        n.message.includes(activityId.toString())
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

const userRole = computed(() => props.stats?.user_role || 'staff');

const focusedProjectLocation = computed(() => {
    const defaultLoc = { lat: 3.1390, lng: 101.6869 };
    if (!focusedProjectId.value || !props.projects_spatial) return defaultLoc;
    
    const project = props.projects_spatial.find(p => p.id === focusedProjectId.value);
    if (!project || !project.boundary) return defaultLoc;
    
    try {
        let geom = project.boundary;
        if (typeof geom === 'string') geom = JSON.parse(geom);

        // Very safe coordinate extraction
        if (geom.type === 'Polygon' && geom.coordinates && geom.coordinates[0] && geom.coordinates[0][0]) {
            return { lat: geom.coordinates[0][0][1], lng: geom.coordinates[0][0][0] };
        } else if (geom.type === 'Point' && geom.coordinates) {
            return { lat: geom.coordinates[1], lng: geom.coordinates[0] };
        }
        
        if (turf && turf.centroid) {
            const centroid = turf.centroid(geom);
            if (centroid?.geometry?.coordinates) {
                return { lat: centroid.geometry.coordinates[1], lng: centroid.geometry.coordinates[0] };
            }
        }
    } catch (e) {
        console.warn("Spatial extraction failed:", e);
    }
    return defaultLoc;
});

const rolePersona = computed(() => {
    const role = userRole.value;
    if (role === 'admin') {
        return {
            title: 'Strategic Oversight Console',
            subtitle: 'Full Access System',
            banner: 'bg-gradient-to-br from-slate-900 via-geo-navy to-amber-950/40',
            accent: 'text-amber-400',
            glow: 'after:bg-amber-500/10',
            trendLabel: 'Global Submission Survey',
            statusLabel: 'Global Reports Count',
            feedTitle: 'Operational Command Feed',
            cardAccent: 'border-amber-500/10 hover:border-amber-500/40 hover:shadow-amber-500/5',
            metricColor: 'text-amber-500',
            btnClass: 'bg-amber-500 text-slate-900 hover:bg-amber-400 shadow-amber-500/20'
        };
    } else if (role === 'hod') {
        return {
            title: 'Compliance Regulation Hub',
            subtitle: 'Validation of Surveys.',
            banner: 'bg-gradient-to-br from-emerald-950 via-geo-navy to-slate-900',
            accent: 'text-emerald-400',
            glow: 'after:bg-emerald-500/10',
            trendLabel: 'Submission Analytics',
            statusLabel: 'Surveys Count',
            feedTitle: 'Technical Validation Queue',
            cardAccent: 'border-emerald-500/10 hover:border-emerald-500/40 hover:shadow-emerald-500/5',
            metricColor: 'text-emerald-500',
            btnClass: 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-emerald-500/20'
        };
    } else {
        return {
            title: 'Personnel Operational Hub',
            subtitle: 'Submission of Surveys',
            banner: 'bg-gradient-to-br from-slate-900 via-geo-navy to-geo-teal/30',
            accent: 'text-geo-teal',
            glow: 'after:bg-geo-teal/10',
            trendLabel: 'Individual Performance Index',
            statusLabel: 'Personal Compliance Standing',
            feedTitle: 'My Operational Pulse',
            cardAccent: 'border-geo-teal/10 hover:border-geo-teal/40 hover:shadow-geo-teal/5',
            metricColor: 'text-geo-teal',
            btnClass: 'bg-geo-teal text-geo-navy hover:brightness-110 shadow-geo-teal/20'
        };
    }
});

let charts = [];

const initializeCharts = () => {
    if (typeof Chart === 'undefined') return;
    charts.forEach(c => c.destroy());
    charts = [];

    const isDark = document.documentElement.classList.contains('dark');
    const axisColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

    const trendCtx = document.getElementById('surveyTrendsChart')?.getContext('2d');
    if (trendCtx && props.chart_data?.monthly?.length > 0) {
        charts.push(new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: props.chart_data.monthly.map(d => d.month || 'Unknown'),
                datasets: [{
                    label: 'Survey Count',
                    data: props.chart_data.monthly.map(d => d.count || 0),
                    backgroundColor: isDark ? (userRole.value === 'admin' ? '#fbbf24' : (userRole.value === 'hod' ? '#10b981' : '#14b8a6')) : '#0d9488',
                    borderRadius: 12,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [5, 5], color: gridColor },
                        ticks: { color: axisColor, font: { weight: 'bold', size: 10 } }
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { color: axisColor, font: { weight: 'bold', size: 10 } }
                    } 
                }
            }
        }));
    }

    const statusCtx = document.getElementById('statusDistChart')?.getContext('2d');
    if (statusCtx && props.chart_data?.status?.length > 0) {
        charts.push(new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: props.chart_data.status.map(d => (d.status || 'N/A').toUpperCase()),
                datasets: [{
                    data: props.chart_data.status.map(d => d.count || 0),
                    backgroundColor: props.chart_data.status.map(d => {
                        const status = (d.status || '').toLowerCase();
                        if (status === 'approved') return '#10b981';
                        if (status === 'pending') return '#fbbf24';
                        if (status === 'rejected') return '#ef4444';
                        return '#64748b';
                    }),
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            color: isDark ? '#cbd5e1' : '#475569',
                            usePointStyle: true, 
                            boxWidth: 8, 
                            font: { weight: 'black', size: 10 }, 
                            padding: 25 
                        } 
                    } 
                },
                cutout: '75%'
            }
        }));
    }
};

onMounted(() => {
    initializeCharts();
    window.addEventListener('gss-theme-changed', initializeCharts);
});

onBeforeUnmount(() => {
    window.removeEventListener('gss-theme-changed', initializeCharts);
});

const statItems = computed(() => {
    const stats = props.stats || {};
    const baseStats = [
        { name: 'Operational Zones', value: stats.active_projects || 0, svg: '<path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />', color: 'text-blue-500' },
        { name: userRole.value === 'staff' ? 'Personal Surveys' : 'Total Surveys', value: stats.total_surveys || 0, svg: '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />', color: 'text-indigo-500' },
    ];

    if (userRole.value === 'admin') {
        baseStats.push({ name: 'Staff Units', value: stats.staff_count || 0, svg: '<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />', color: 'text-geo-teal' });
        baseStats.push({ name: 'Most Active Zone', value: stats.top_zone || 'N/A', svg: '<path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />', color: 'text-yellow-500' });
    } else if (userRole.value === 'hod') {
        baseStats.push({ name: 'Pending Review', value: stats.pending_approvals || 0, svg: '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />', color: 'text-red-500' });
        baseStats.push({ name: 'Most Active Zone', value: stats.top_zone || 'N/A', svg: '<path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />', color: 'text-green-500' });
    } else {
        baseStats.push({ name: 'Awaiting Review', value: stats.user_pending || 0, svg: '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />', color: 'text-orange-500' });
        baseStats.push({ name: 'Primary Operation Zone', value: stats.top_zone || 'N/A', svg: '<path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />', color: 'text-emerald-500' });
    }

    return baseStats;
});

const topThreeStaff = computed(() => {
    return [...props.users]
        .filter(u => u.role === 'staff')
        .sort((a, b) => (b.surveys_count || 0) - (a.surveys_count || 0))
        .slice(0, 3);
});

const hodCuratedFeed = computed(() => {
    const activities = props.recent_activities || [];
    const approved = activities.find(a => a.status === 'approved');
    const pending = activities.find(a => a.status === 'pending');
    const rejected = activities.find(a => a.status === 'rejected');
    return [approved, pending, rejected].filter(Boolean);
});
</script>

<template>
    <Head :title="rolePersona.title" />

    <AuthenticatedLayout>
        <template #header>
            <div :class="[rolePersona.banner, rolePersona.glow]" class="p-10 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden transition-all duration-700 after:absolute after:inset-0 after:pointer-events-none">
                <!-- Branding Decoration -->
                <div class="absolute -top-24 -right-24 w-80 h-80 bg-white/5 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-geo-teal/10 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col lg:flex-row justify-between items-center gap-8">
                    <div class="text-center lg:text-left">
                        <div class="flex items-center gap-3 justify-center lg:justify-start mb-4">
                            <span class="px-4 py-1.5 rounded-full bg-white/10 text-[10px] font-black uppercase tracking-[0.2em] border border-white/10 backdrop-blur-xl">
                                {{ userRole }} AUTHORITY
                            </span>
                           
                        </div>
                        <h2 class="relative -top-2 font-black text-4xl lg:text-5xl tracking-tight mb-3 bg-clip-text text-transparent bg-gradient-to-b from-white to-white/70">
                            {{ rolePersona.title }}
                        </h2>
                        <p class="text-white/60 font-medium italic text-sm lg:text-lg max-w-2xl">
                            Authorization by : <span :class="rolePersona.accent" class="font-black px-2 py-0.5 bg-white/5 rounded-lg border border-white/5 mx-1">{{ $page.props.auth.user.name }}</span>. {{ rolePersona.subtitle }}
                        </p>
                    </div>
                    
                    
                </div>
            </div>
        </template>

        <div class="py-8 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Role Specific Alert/Action -->
                <div v-if="$page.props.auth.user.role === 'hod' && stats.pending_approvals > 0" class="bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 p-4 rounded-xl flex items-center justify-between transition-colors duration-500">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-red-800 dark:text-red-300 font-bold">You have {{ stats.pending_approvals }} site surveys awaiting approval.</span>
                    </div>
                    <Link :href="route('surveys.index', { status: 'pending' })" class="text-red-700 dark:text-red-400 font-bold text-sm underline hover:text-red-800">Clear Queue →</Link>
                </div>

                <!-- Stats Matrix -->
                <transition-group 
                    appear
                    tag="div" 
                    name="staggered-fade"
                    class="grid grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <div v-for="(stat, index) in statItems" :key="stat.name" 
                        :style="{ transitionDelay: `${index * 100}ms` }"
                        :class="rolePersona.cardAccent"
                        class="bg-[var(--geo-surface)] p-5 rounded-[1.5rem] shadow-glass border border-[var(--geo-border)] transition-all duration-500 group hover:-translate-y-1 relative overflow-hidden"
                    >
                        <div class="absolute top-0 right-0 p-2 opacity-5 group-hover:scale-125 transition-transform duration-700">
                             <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="stat.svg"></svg>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <div :class="stat.color" class="p-2.5 bg-opacity-10 rounded-xl bg-current transition-all group-hover:rotate-6">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="stat.svg"></svg>
                            </div>
                        </div>
                        <p class="text-[9px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-1 transition-colors">{{ stat.name }}</p>
                        <div class="flex items-baseline gap-1.5">
                             <p :class="rolePersona.metricColor" class="text-2xl font-black transition-colors">{{ stat.value }}</p>
                             
                        </div>
                    </div>
                </transition-group>

                <!-- Analytical Intelligence Rows -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Dynamic Insight Panel (Charts) -->
                    <div class="lg:col-span-2 bg-[var(--geo-surface)] p-8 rounded-[2.5rem] shadow-sm border border-[var(--geo-border)] transition-colors duration-500 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-geo-teal/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h3 class="text-xl font-black text-geo-navy dark:text-white transition-colors">{{ rolePersona.trendLabel }}</h3>
                                <p class="text-[10px] text-geo-slate dark:text-gray-400 font-bold uppercase tracking-widest mt-1">By Month</p>
                            </div>
                            
                        </div>
                        <div class="h-72">
                            <canvas id="surveyTrendsChart"></canvas>
                        </div>
                    </div>

                    <!-- Integrity Profile (Doughnut) -->
                    <div class="bg-[var(--geo-surface)] p-8 rounded-[2.5rem] shadow-sm border border-[var(--geo-border)] transition-colors duration-500">
                        <div class="mb-8">
                            <h3 class="text-xl font-black text-geo-navy dark:text-white transition-colors">{{ rolePersona.statusLabel }}</h3>
                            <p class="text-[10px] text-geo-slate dark:text-gray-400 font-bold uppercase tracking-widest mt-1 italic">By Status</p>
                        </div>
                        <div class="h-72 relative">
                            <canvas id="statusDistChart"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="text-center">
                                    <p class="text-[9px] font-black text-geo-slate uppercase tracking-widest">Total Reports</p>
                                    <p class="text-2xl font-black text-geo-navy dark:text-white">{{ stats.total_surveys || 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spatial Intelligence Core -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Expanded Map View -->
                    <div class="lg:col-span-8 xl:col-span-9 bg-[var(--geo-surface)] p-1 rounded-[2rem] shadow-sm border border-[var(--geo-border)] overflow-hidden transition-colors duration-500 relative group flex flex-col">
                        <div class="absolute inset-0 border-[8px] border-[var(--geo-surface)] rounded-[2rem] pointer-events-none z-10"></div>
                        <div class="p-5 flex justify-between items-center relative z-20">
                            <div>
                                <h3 class="text-lg font-black text-geo-navy dark:text-white transition-colors">Global Map View</h3>
                                <p class="text-[9px] text-geo-slate dark:text-gray-400 font-bold uppercase tracking-widest mt-0.5">Live Geospatial Coordinate</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <!-- Heatmap Toggle -->
                                <button 
                                    @click="toggleHeatmap"
                                    :class="isHeatmapActive ? 'bg-geo-teal text-white shadow-lg shadow-geo-teal/20' : 'bg-black/5 dark:bg-white/5 text-geo-slate hover:bg-black/10 dark:hover:bg-white/10'"
                                    class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-transparent dark:border-white/5 flex items-center gap-2"
                                    :disabled="isLoadingHeatmap"
                                >
                                    <i class="fa-solid" :class="isLoadingHeatmap ? 'fa-circle-notch fa-spin' : 'fa-fire'"></i>
                                    {{ isLoadingHeatmap ? 'Syncing...' : 'Heatmap' }}
                                </button>
                                
                                <div class="bg-black/10 dark:bg-white/5 p-1 rounded-xl border border-white/5 backdrop-blur-md flex items-center shadow-inner">
                                    <span class="text-[8px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest px-3">View:</span>
                                    <CustomSelect 
                                        v-model="focusedProjectId"
                                        :options="[{ value: null, label: userRole === 'staff' ? 'SECTOR VIEW' : 'GLOBAL VIEW' }, ...projects_spatial.map(p => ({ value: p.id, label: p.name.toUpperCase() }))]"
                                        customClass="text-[9px] border-none py-1.5 px-3 min-w-[140px] shadow-sm bg-transparent dark:bg-transparent"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 relative min-h-[550px]">
                            <MapViewer 
                                readOnly 
                                :projects="projects_spatial" 
                                :focusProject="focusedProjectId" 
                                :modelValue="focusedProjectLocation"
                                :heatmapData="isHeatmapActive && spatialIntelligence ? spatialIntelligence : { type: 'FeatureCollection', features: [] }"
                            />
                        </div>
                    </div>

                    <!-- Side Panel: Activity Console -->
                    <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-6">
                        <div class="bg-[var(--geo-surface)] p-6 rounded-[2rem] shadow-sm border border-[var(--geo-border)] transition-colors duration-500 flex-1 flex flex-col min-h-[500px]">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xs font-black text-geo-navy dark:text-white uppercase tracking-widest flex items-center transition-colors">
                                    <span :class="userRole === 'admin' ? 'bg-amber-500' : (userRole === 'hod' ? 'bg-emerald-500' : 'bg-geo-teal')" class="w-2 h-2 rounded-full mr-2.5 shadow-lg shadow-current"></span>
                                    Audit Feed
                                </h3>
                                
                            </div>
                            
                            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-3">
                                <div v-for="(activity, index) in (userRole === 'admin' ? recent_activities.slice(0, 5) : (userRole === 'hod' ? hodCuratedFeed : recent_activities))" :key="activity.id" 
                                    @click="handleActivityClick(activity.id)"
                                    class="flex items-center gap-3 hover:bg-gray-50/50 dark:hover:bg-white/5 p-3 rounded-2xl transition-all duration-300 cursor-pointer relative group border border-transparent hover:border-[var(--geo-border)]"
                                >
                                    <div class="relative z-10 flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center transition-all group-hover:scale-105"
                                        :class="activity.status === 'approved' ? 'bg-emerald-500/10 text-emerald-600' : (activity.status === 'rejected' ? 'bg-red-500/10 text-red-600' : 'bg-amber-500/10 text-amber-600')">
                                        <i :class="activity.status === 'approved' ? 'fa-solid fa-circle-check' : (activity.status === 'rejected' ? 'fa-solid fa-circle-xmark' : 'fa-solid fa-circle-pause')" class="text-sm"></i>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center">
                                            <p class="font-black text-geo-navy dark:text-white text-[10px] uppercase tracking-tight truncate transition-colors">{{ activity.project?.name || 'Log' }}</p>
                                            <span v-if="activity.created_at" class="text-[7.5px] font-bold text-geo-slate dark:text-gray-500">{{ $formatDate(activity.created_at) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <p class="text-[9px] font-bold text-geo-slate dark:text-gray-400 truncate transition-colors">Unit: {{ activity.user?.name || 'Alpha' }}</p>
                                            <div class="w-1 h-1 rounded-full bg-gray-200 dark:bg-white/10"></div>
                                            <span :class="activity.status === 'approved' ? 'text-emerald-500' : (activity.status === 'rejected' ? 'text-red-500' : 'text-amber-500')" class="text-[7.5px] font-black uppercase tracking-widest italic">
                                                {{ activity.status }}
                                            </span>
                                        </div>
                                        <div v-if="hasUnread(activity.id)" class="absolute top-2 right-2 w-1.5 h-1.5 bg-red-500 rounded-full shadow-lg shadow-red-500/50 animate-ping"></div>
                                    </div>
                                </div>
                                <div v-if="recent_activities.length === 0" class="py-12 text-center text-[9px] font-bold text-geo-slate dark:text-gray-500 italic uppercase">Silent Sector</div>
                            </div>
                            
                            <!-- HOD: Strategic Decision Queue (Minimalist Sidebar) -->
                            <div v-if="userRole === 'hod'" class="mt-6 border-t border-[var(--geo-border)] pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-[9px] font-black text-geo-navy dark:text-white uppercase tracking-widest flex items-center">
                                        <i class="fa-solid fa-list-check text-red-500 mr-2"></i>
                                        Decision Queue
                                    </h3>
                                    <span class="text-[8px] font-black bg-red-500/10 text-red-500 px-2 py-0.5 rounded">{{ pending_details.length }} Pending</span>
                                </div>
                                <div class="space-y-2 max-h-[160px] overflow-y-auto pr-1 custom-scrollbar">
                                    <Link v-for="item in pending_details" :key="item.id" :href="route('surveys.show', item.id)"
                                        class="flex items-center justify-between p-2.5 bg-red-500/5 hover:bg-red-500 rounded-xl transition-all group border border-red-500/10">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-6 h-6 rounded-lg bg-red-500/10 flex items-center justify-center group-hover:bg-white/20 transition-colors shrink-0">
                                                <span class="text-[8px] font-black text-red-600 group-hover:text-white uppercase">{{ item.user?.name?.charAt(0) || 'U' }}</span>
                                            </div>
                                            <p class="text-[9px] font-black text-geo-navy dark:text-white group-hover:text-white truncate uppercase">{{ item.project?.name || 'Recon' }}</p>
                                        </div>
                                        <i class="fa-solid fa-chevron-right text-[8px] text-red-400 group-hover:text-white"></i>
                                    </Link>
                                    <div v-if="pending_details.length === 0" class="py-4 text-center">
                                        <p class="text-[8px] font-black text-emerald-500 uppercase italic">Queue Clear</p>
                                    </div>
                                </div>
                            </div>

                            <Link :href="route('surveys.index')" class="block text-center mt-6 py-3 rounded-xl text-[8px] font-black text-geo-slate dark:text-geo-teal bg-gray-50 dark:bg-white/5 border border-transparent hover:border-geo-teal/20 transition-all uppercase tracking-[0.2em]">
                                More Surveys
                            </Link>
                        </div>

                        <!-- Admin: Consolidated Fleet Performance -->
                        <div v-if="userRole === 'admin'" class="bg-[var(--geo-surface)] p-6 rounded-[2rem] shadow-sm border border-[var(--geo-border)] transition-colors duration-500 flex flex-col">
                            <div class="flex justify-between items-center mb-5">
                                <div>
                                    <h3 class="text-[10px] font-black text-geo-navy dark:text-white uppercase tracking-widest transition-colors">Most Active Staff</h3>
                                    
                                </div>
                                <i class="fa-solid fa-trophy text-amber-500 text-[10px]"></i>
                            </div>
                            <div class="space-y-2.5">
                                <div v-for="(user, index) in topThreeStaff" :key="user.id" class="flex items-center gap-3 p-2.5 bg-gray-50/50 dark:bg-white/5 border border-transparent hover:border-geo-teal/20 rounded-xl transition-all group">
                                    <div class="relative">
                                        <div class="w-7 h-7 rounded-lg bg-geo-navy group-hover:bg-geo-teal flex items-center justify-center text-white font-black text-[9px] transition-all shadow-inner">
                                            {{ user.name?.charAt(0) }}
                                        </div>
                                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-white dark:bg-geo-navy rounded-full border border-gray-100 dark:border-white/10 flex items-center justify-center text-[6px] font-black text-geo-teal">
                                            {{ index + 1 }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-black text-geo-navy dark:text-white text-[9px] truncate transition-colors">{{ user.name }}</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-[7px] font-bold text-geo-slate dark:text-gray-500 uppercase tracking-tighter">{{ user.surveys_count || 0 }} Submissions</p>
                                            
                                            
                                        </div>
                                    </div>
                                </div>
                                <div v-if="topThreeStaff.length === 0" class="py-6 text-center">
                                    <p class="text-[8px] font-bold text-geo-slate dark:text-gray-500 italic uppercase">Deploying Units...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Guidance Box -->
                        <div v-if="$page.props.auth.user.role === 'staff'" class="bg-gradient-to-br from-geo-navy to-slate-900 p-6 rounded-[2rem] shadow-xl text-white relative overflow-hidden group">
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-geo-teal/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-1000"></div>
                            <h3 class="text-sm font-black text-geo-teal mb-3 uppercase tracking-[0.25em]">Tactical Field Guidance</h3>
                            <p class="text-xs text-gray-400 leading-relaxed mb-4 italic">Precision telemetry is required. Ensure your GPS marker illuminates <span class="text-geo-teal font-black underline">Cyan</span> before initiating evidence capture.</p>
                            <Link :href="route('surveys.create')" class="flex items-center justify-center gap-2 text-[10px] font-black text-geo-navy bg-geo-teal px-6 py-3 rounded-xl hover:brightness-110 active:scale-95 transition-all uppercase tracking-[0.2em]">
                                <i class="fa-solid fa-map-location-dot"></i>
                                INITIALIZE SURVEY
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Specialized Strategic Modules -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pb-12">
                    
                    <!-- Admin Fleet Intel removed and moved to side panel -->

                    <!-- HOD Bottom section removed - consolidated to sidebar -->

                    <!-- Staff: Precision Analytics (Removed) -->
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
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(100, 116, 139, 0.4);
}
.staggered-fade-enter-active {
    transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.staggered-fade-enter-from {
    opacity: 0;
    transform: translateY(20px);
}
</style>
