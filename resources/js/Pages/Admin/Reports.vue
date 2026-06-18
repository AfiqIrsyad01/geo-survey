<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MapViewer from '@/Components/MapViewer.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    stats: Object,
    projects: Array
});

const spatialIntelligence = ref(null);
const isLoadingAnalytics = ref(false);
const selectedAnalyticsProject = ref(null);
const mapViewerRef = ref(null);

const fetchSpatialIntelligence = async () => {
    isLoadingAnalytics.value = true;
    try {
        const response = await fetch(route('api.analytics.spatial'));
        spatialIntelligence.value = await response.json();
    } catch (e) {
        console.error("Spatial Intelligence Error:", e);
    } finally {
        isLoadingAnalytics.value = false;
    }
};

const flyToAnalyticsProject = () => {
    if (!selectedAnalyticsProject.value) {
        if (mapViewerRef.value) {
            mapViewerRef.value.fitToGlobalContext();
        }
        return;
    }
    const project = props.projects.find(p => p.id == selectedAnalyticsProject.value);
    if (project && project.boundary && mapViewerRef.value) {
        mapViewerRef.value.flyToBoundary(project.boundary);
    }
};

const selectedMonth = ref(props.stats.selected_month || new Date().getMonth() + 1);
const selectedYear = ref(props.stats.selected_year || new Date().getFullYear());

const reportOptions = ref({
    trends: true,
    distribution: true,
    registry: true
});

watch([selectedMonth, selectedYear], () => {
    router.get(route('admin.reports'), {
        month: selectedMonth.value,
        year: selectedYear.value
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['stats']
    });
});

const months = [
    { id: 1, name: 'January' }, { id: 2, name: 'February' }, { id: 3, name: 'March' },
    { id: 4, name: 'April' }, { id: 5, name: 'May' }, { id: 6, name: 'June' },
    { id: 7, name: 'July' }, { id: 8, name: 'August' }, { id: 9, name: 'September' },
    { id: 10, name: 'October' }, { id: 11, name: 'November' }, { id: 12, name: 'December' }
];

const years = [2024, 2025, 2026];

let statusChart = null;
let trendChart = null;

function initializeCharts() {
    if (typeof Chart === 'undefined') {
        setTimeout(initializeCharts, 500);
        return;
    }

    const statusCanvas = document.getElementById('statusChart');
    const trendCanvas = document.getElementById('trendChart');

    if (!statusCanvas || !trendCanvas || !props.stats) {
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const chartLabelColor = isDark ? '#f1f5f9' : '#1e293b';
    const chartGridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : '#e2e8f0';

    if (statusChart) statusChart.destroy();
    if (trendChart) trendChart.destroy();

    // Pie Chart: Status Distribution
    const statusCtx = statusCanvas.getContext('2d');
    
    // Explicitly define status ordering and colors to prevent mismatch
    const statusOrder = [
        { key: 'approved', label: 'APPROVED', color: '#0d9488' },
        { key: 'pending', label: 'PENDING', color: '#fbbf24' },
        { key: 'rejected', label: 'REJECTED', color: '#ef4444' }
    ];

    const chartLabels = [];
    const chartData = [];
    const chartColors = [];

    statusOrder.forEach(status => {
        const found = (props.stats.status_counts || []).find(s => s.status.toLowerCase() === status.key);
        const count = found ? found.count : 0;
        
        chartLabels.push(status.label);
        chartData.push(count);
        chartColors.push(status.color);
    });

    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: chartColors,
                hoverOffset: 10,
                borderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        boxWidth: 10, 
                        color: chartLabelColor,
                        font: { weight: 'bold', size: 10 },
                        padding: 15
                    } 
                }
            },
            cutout: '65%'
        }
    });

    // Line Chart: Submission Trends
    const trendCtx = trendCanvas.getContext('2d');
    const trends = [...(props.stats.monthly_trends || [])].reverse();
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trends.map(t => t.month),
            datasets: [{
                label: 'Survey Volume',
                data: trends.map(t => t.count),
                borderColor: '#0d9488',
                backgroundColor: isDark ? 'rgba(13, 148, 136, 0.2)' : 'rgba(13, 148, 136, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#0d9488',
                pointRadius: 4
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { borderDash: [5, 5], color: chartGridColor },
                    ticks: { color: chartLabelColor }
                },
                x: { 
                    grid: { display: false },
                    ticks: { color: chartLabelColor }
                }
            }
        }
    });
}

onMounted(() => {
    initializeCharts();
    fetchSpatialIntelligence();
    window.addEventListener('gss-theme-changed', initializeCharts);
});

onBeforeUnmount(() => {
    window.removeEventListener('gss-theme-changed', initializeCharts);
});


watch(() => props.stats, () => {
    initializeCharts();
}, { deep: true });
</script>

<template>
    <Head title="Corporate Reporting" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center transition-colors">
                <div>
                    <h2 class="font-black text-2xl text-geo-navy dark:text-white leading-tight transition-colors">Corporate Intelligence Dashboard</h2>
                    <p class="text-sm text-geo-slate dark:text-gray-400 mt-1 font-medium transition-colors">Strategic overview of system operations and personnel performance.</p>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- PDF Generation Tool with Intelligence Customizer -->
                <div class="bg-geo-navy dark:bg-white/5 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden group border border-transparent dark:border-white/5 transition-all duration-500">
                    <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-file-invoice-dollar text-9xl"></i>
                    </div>
                    <div class="relative z-10 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
                        <div>
                            <h3 class="text-xl font-bold mb-2">Automated Monthly Governance Report</h3>
                            <p class="text-gray-300 dark:text-gray-400 text-sm max-w-md transition-colors">Compile all field activities, spatial validations, and audit outcomes into a standardized corporate PDF document.</p>
                            
                            <!-- Customizer Toggles -->
                            <div class="mt-6 flex flex-wrap gap-4">
                                <label class="flex items-center gap-2 cursor-pointer group/label">
                                    <input type="checkbox" v-model="reportOptions.trends" class="rounded border-white/20 bg-white/5 text-geo-teal focus:ring-geo-teal transition-all">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-300 group-hover/label:text-white transition-colors">Daily Trends</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group/label">
                                    <input type="checkbox" v-model="reportOptions.distribution" class="rounded border-white/20 bg-white/5 text-geo-teal focus:ring-geo-teal transition-all">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-300 group-hover/label:text-white transition-colors">Audit Integrity</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group/label">
                                    <input type="checkbox" v-model="reportOptions.registry" class="rounded border-white/20 bg-white/5 text-geo-teal focus:ring-geo-teal transition-all">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-300 group-hover/label:text-white transition-colors">Full Registry</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-4 bg-white/5 p-6 rounded-3xl backdrop-blur-md border border-white/10 w-full lg:w-auto">
                            <div class="flex gap-2 w-full sm:w-auto">
                                <select v-model="selectedMonth" class="bg-transparent border-white/20 rounded-xl text-sm font-bold focus:ring-geo-teal w-full">
                                    <option v-for="m in months" :key="m.id" :value="m.id" class="text-geo-navy">{{ m.name }}</option>
                                </select>
                                <select v-model="selectedYear" class="bg-transparent border-white/20 rounded-xl text-sm font-bold focus:ring-geo-teal w-full">
                                    <option v-for="y in years" :key="y" :value="y" class="text-geo-navy">{{ y }}</option>
                                </select>
                            </div>
                            <a 
                                :href="stats.total_surveys > 0 ? route('reports.monthly', { month: selectedMonth, year: selectedYear, trends: reportOptions.trends, distribution: reportOptions.distribution, registry: reportOptions.registry }) : '#'" 
                                :class="stats.total_surveys > 0 ? 'bg-geo-teal text-geo-navy hover:brightness-110 shadow-lg shadow-geo-teal/20' : 'bg-gray-500 text-gray-300 cursor-not-allowed opacity-50'"
                                class="px-8 py-3 rounded-2xl text-xs font-black transition-all uppercase tracking-widest whitespace-nowrap w-full sm:w-auto text-center"
                            >
                                <i class="fa-solid fa-file-pdf mr-2"></i>
                                {{ stats.total_surveys > 0 ? 'Download Report' : 'No Data' }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Strategic Heatmap Intelligence (Phase 6) -->
                <div class="bg-[var(--geo-surface)] p-2 rounded-[2rem] shadow-2xl border border-[var(--geo-border)] relative overflow-hidden transition-all duration-500">
                    <div class="h-[500px] w-full relative">
                        <MapViewer 
                            ref="mapViewerRef"
                            v-if="spatialIntelligence"
                            :heatmapData="spatialIntelligence"
                            :projects="projects"
                            :readOnly="true" 
                        />
                        <div v-else class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-geo-navy/40 backdrop-blur-sm z-50">
                            <i class="fa-solid fa-satellite-dish text-geo-teal text-4xl animate-pulse mb-4"></i>
                            <p class="text-xs font-black uppercase text-geo-navy dark:text-gray-300 tracking-widest">Synchronizing Global Intelligence...</p>
                        </div>

                        <!-- Tactical Sector Selector -->
                        <div class="absolute top-6 left-20 z-[100] flex gap-2">
                             <select v-model="selectedAnalyticsProject" @change="flyToAnalyticsProject" class="bg-white/90 dark:bg-geo-navy/90 backdrop-blur-md border border-white/20 dark:border-white/10 rounded-2xl px-5 py-3 text-[10px] font-black uppercase tracking-widest text-geo-navy dark:text-geo-teal shadow-2xl focus:ring-2 focus:ring-geo-teal outline-none transition-all">
                                <option :value="null">GLOBAL THEATER OVERVIEW</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">Focus: {{ project.name }}</option>
                             </select>
                        </div>

                        <!-- Map Overlay Info -->
                        <div class="absolute bottom-6 left-20 z-[100] bg-geo-navy/90 backdrop-blur-md p-4 rounded-2xl border border-white/10 shadow-2xl max-w-[240px]">
                            <h5 class="text-[10px] font-black text-geo-teal uppercase tracking-widest mb-2">Regional Heat Extraction</h5>
                            <p class="text-[11px] text-white/80 leading-relaxed font-medium">Visualizing spatial density based on all historic survey nodes. Warmer zones indicate high operational concentration.</p>
                            <div class="mt-4 flex items-center gap-3">
                                <div class="flex -space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-red-500 border-2 border-geo-navy"></div>
                                    <div class="w-6 h-6 rounded-full bg-orange-400 border-2 border-geo-navy"></div>
                                    <div class="w-6 h-6 rounded-full bg-blue-400 border-2 border-geo-navy"></div>
                                </div>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">Intensity Scale</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategic Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Trends -->
                    <div class="bg-[var(--geo-surface)] p-8 rounded-3xl shadow-sm border border-[var(--geo-border)] h-[450px] flex flex-col transition-colors duration-500">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="font-bold text-geo-navy dark:text-white uppercase tracking-widest text-xs transition-colors">Submission Velocity (12 Months)</h4>
                            
                        </div>
                        <div class="flex-1">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <!-- Status Distribution -->
                    <div class="bg-[var(--geo-surface)] p-8 rounded-3xl shadow-sm border border-[var(--geo-border)] h-[450px] flex flex-col items-center transition-colors duration-500">
                        <h4 class="font-bold text-geo-navy dark:text-white uppercase tracking-widest text-xs w-full mb-6 transition-colors">Audit Integrity Distribution</h4>
                        <div class="flex-1 w-full max-w-[300px]">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-4 w-full">
                            <div class="text-center p-3 rounded-2xl bg-teal-50 dark:bg-geo-teal/10 border border-teal-100 dark:border-geo-teal/20 transition-colors">
                                <p class="text-[9px] font-black text-geo-teal uppercase">Approved</p>
                                <p class="text-lg font-bold text-geo-navy dark:text-white">{{ stats.status_counts.find(s => s.status === 'approved')?.count || 0 }}</p>
                            </div>
                            <div class="text-center p-3 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/20 transition-colors">
                                <p class="text-[9px] font-black text-amber-500 uppercase">Pending</p>
                                <p class="text-lg font-bold text-geo-navy dark:text-white">{{ stats.status_counts.find(s => s.status === 'pending')?.count || 0 }}</p>
                            </div>
                            <div class="text-center p-3 rounded-2xl bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/20 transition-colors">
                                <p class="text-[9px] font-black text-red-500 uppercase">Rejected</p>
                                <p class="text-lg font-bold text-geo-navy dark:text-white">{{ stats.status_counts.find(s => s.status === 'rejected')?.count || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Personnel Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                        <h4 class="font-bold text-geo-navy uppercase tracking-widest text-xs">High-Performance Personnel Units</h4>
                        <span class="text-[10px] font-bold text-geo-slate uppercase bg-gray-50 px-3 py-1 rounded-full">Top 5 Units</span>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 text-[10px] font-black uppercase text-geo-slate tracking-widest">
                            <tr>
                                <th class="px-8 py-4">Unit Designation</th>
                                <th class="px-8 py-4">Operational Status</th>
                                <th class="px-8 py-4">Logs Generated</th>
                                <th class="px-8 py-4 text-right">Performance Rank</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(user, index) in stats.top_personnel" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-geo-navy text-white flex items-center justify-center font-black text-xs">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-geo-navy leading-none mb-1">{{ user.name }}</p>
                                            <p class="text-[10px] text-geo-slate">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase border border-emerald-100">Active</span>
                                </td>
                                <td class="px-8 py-5 font-mono font-bold text-geo-navy">
                                    {{ user.surveys_count }} Logs
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <i v-for="i in (5-index)" :key="i" class="fa-solid fa-star text-amber-400 text-[8px]"></i>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
select {
    cursor: pointer;
}
</style>
