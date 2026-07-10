<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    stats: Object,
    projects: Array
});
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
                            <h3 class="text-xl font-bold mb-2">Generate Monthly Governance Report</h3>
                            <p class="text-gray-300 dark:text-gray-400 text-sm max-w-md transition-colors">Compile all field activities, spatial validations and audit outcomes into a standardized corporate PDF document.</p>
                            
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
                            <div class="flex gap-2 w-full sm:w-auto z-10 relative">
                                <CustomSelect 
                                    v-model="selectedMonth" 
                                    :options="months.map(m => ({ value: m.id, label: m.name }))"
                                    customClass="bg-transparent border-white/20 rounded-xl text-xs font-bold w-full !text-white"
                                />
                                <CustomSelect 
                                    v-model="selectedYear" 
                                    :options="years.map(y => ({ value: y, label: y.toString() }))"
                                    customClass="bg-transparent border-white/20 rounded-xl text-xs font-bold w-full !text-white"
                                />
                            </div>
                            <a 
                                :href="stats.total_surveys > 0 ? route('reports.monthly', { month: selectedMonth, year: selectedYear, trends: reportOptions.trends, distribution: reportOptions.distribution, registry: reportOptions.registry }) : '#'" 
                                :target="stats.total_surveys > 0 ? '_blank' : null"
                                :class="stats.total_surveys > 0 ? 'bg-geo-teal text-geo-navy hover:brightness-110 shadow-lg shadow-geo-teal/20' : 'bg-gray-500 text-gray-300 cursor-not-allowed opacity-50'"
                                class="px-8 py-3 rounded-2xl text-xs font-black transition-all uppercase tracking-widest whitespace-nowrap w-full sm:w-auto text-center"
                            >
                                <i class="fa-solid fa-file-pdf mr-2"></i>
                                {{ stats.total_surveys > 0 ? 'Generate' : 'No Data' }}
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Strategic Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Trends -->
                    <div class="bg-[var(--geo-surface)] p-8 rounded-3xl shadow-sm border border-[var(--geo-border)] h-[450px] flex flex-col transition-colors duration-500">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="font-bold text-geo-navy dark:text-white uppercase tracking-widest text-xs transition-colors">Submission Analytics</h4>
                            
                        </div>
                        <div class="flex-1">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <!-- Status Distribution -->
                    <div class="bg-[var(--geo-surface)] p-8 rounded-3xl shadow-sm border border-[var(--geo-border)] h-[450px] flex flex-col items-center transition-colors duration-500">
                        <h4 class="font-bold text-geo-navy dark:text-white uppercase tracking-widest text-xs w-full mb-6 transition-colors">Audit Survey Status</h4>
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
                        <h4 class="font-bold text-geo-navy uppercase tracking-widest text-xs">Personnel Units</h4>
                        <span class="text-[10px] font-bold text-geo-slate uppercase bg-gray-50 px-3 py-1 rounded-full">Top 5</span>
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
