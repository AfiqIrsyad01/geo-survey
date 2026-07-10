<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    surveys: Array,
    filters: Object
});

const searchQuery = ref('');
const filterStatus = ref('');
const filterDate = ref('');

const filteredSurveys = computed(() => {
    return props.surveys.filter(survey => {
        const matchesSearch = survey.project?.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             survey.user?.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = filterStatus.value ? survey.status === filterStatus.value : true;
        const matchesDate = filterDate.value ? survey.created_at.startsWith(filterDate.value) : true;
        return matchesSearch && matchesStatus && matchesDate;
    });
});

const resetFilters = () => {
    searchQuery.value = '';
    filterStatus.value = '';
    filterDate.value = '';
};
</script>

<template>
    <Head title="Surveys" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-black text-xl text-geo-navy dark:text-gray-100 leading-tight transition-colors">Spatial Data Audit</h2>
                    <p class="text-sm text-geo-slate dark:text-gray-400 mt-1 transition-colors">Operational log of all georeferenced survey submissions.</p>
                </div>
                
                <div class="flex items-center space-x-4">

                    <a v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.bulk.surveys.export')" class="hidden md:flex items-center gap-2 bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-geo-navy dark:text-gray-300 px-6 py-2 rounded-xl font-bold shadow-sm hover:border-geo-teal hover:text-geo-teal transition-all">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </a>
                    
                    <Link v-if="$page.props.auth.user.role !== 'hod'" :href="route('surveys.create')" class="bg-geo-blue text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-blue-600 active:scale-95 transition-all">
                        + Dispatch Survey
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Search & Live Filters -->
                <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] flex flex-col md:flex-row gap-4 items-center justify-between transition-colors duration-500">
                    <div class="relative w-full md:w-96">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-geo-slate dark:text-gray-500"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search project or personnel..." class="w-full pl-12 pr-4 py-3 rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 focus:ring-geo-teal focus:border-geo-teal text-sm text-geo-navy dark:text-white transition-all" />
                    </div>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto z-10">
                        <CustomSelect 
                            v-model="filterStatus" 
                            :options="[
                                { value: '', label: 'All Statuses' },
                                { value: 'pending', label: 'Pending' },
                                { value: 'approved', label: 'Approved' },
                                { value: 'rejected', label: 'Rejected' }
                            ]"
                            customClass="rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 text-xs font-bold text-geo-slate dark:text-gray-400 py-2.5 transition-all min-w-[140px]"
                        />
                        <input v-model="filterDate" type="date" class="rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 focus:ring-geo-teal text-sm text-geo-slate dark:text-gray-400 py-3 transition-all" />
                        <button @click="resetFilters" class="p-3 rounded-2xl bg-white dark:bg-white/5 border border-gray-100 dark:border-white/10 text-geo-slate dark:text-gray-400 hover:text-geo-teal dark:hover:text-geo-teal hover:border-geo-teal transition-all group" title="Reset Filters">
                            <i class="fa-solid fa-rotate-right text-xs group-active:rotate-180 transition-transform duration-500"></i>
                        </button>
                        <span class="text-xs font-bold text-geo-slate dark:text-gray-500 uppercase tracking-widest px-2 whitespace-nowrap">{{ filteredSurveys.length }} Logs</span>
                    </div>
                </div>

                <div class="bg-[var(--geo-surface)] overflow-hidden shadow-2xl sm:rounded-3xl border border-[var(--geo-border)] transition-colors duration-500">
                    <table class="min-w-full divide-y divide-[var(--geo-border)]">
                        <thead class="bg-gray-50 dark:bg-white/5 uppercase text-[10px] font-black tracking-widest text-geo-slate dark:text-gray-400 transition-colors">
                            <tr>
                                <th class="px-6 py-4 text-left">Code</th>
                                <th class="px-6 py-4 text-left">Project</th>
                                <th class="px-6 py-4 text-left">Assigned Staff</th>
                                <th class="px-6 py-4 text-left">Audit Status</th>
                                <th class="px-6 py-4 text-left">Log Date</th>
                                <th class="px-6 py-4 text-right">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--geo-surface)] divide-y divide-[var(--geo-border)] transition-colors">
                            <tr v-for="survey in filteredSurveys" :key="survey.id" class="hover:bg-blue-50/20 dark:hover:bg-geo-teal/5 transition-all text-sm group">
                                <td class="px-6 py-4 font-mono font-bold text-geo-navy dark:text-geo-teal">
                                    SRV-{{ String(survey.id).padStart(5, '0') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-geo-navy dark:text-gray-100">{{ survey.project?.name }}</div>
                                    <div class="text-[10px] text-geo-slate dark:text-gray-500 uppercase">{{ survey.project?.user?.name || 'System' }} Zone</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 rounded-full bg-geo-navy dark:bg-geo-teal text-white dark:text-geo-navy text-[10px] flex items-center justify-center font-bold">
                                            {{ survey.user?.name.charAt(0) }}
                                        </div>
                                        <span class="font-bold text-geo-navy dark:text-gray-200">{{ survey.user?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 transition-colors">
                                    <span :class="{
                                        'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900/30': survey.status === 'pending',
                                        'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-900/30': survey.status === 'approved',
                                        'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900/30': survey.status === 'rejected'
                                    }" class="px-3 py-1 rounded-full text-[10px] font-black uppercase border tracking-tighter">
                                        {{ survey.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-geo-slate dark:text-gray-400 font-medium">{{ new Date(survey.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link :href="route('surveys.show', survey.id)" class="inline-block bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-geo-navy dark:text-gray-300 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-50 dark:hover:bg-white/10 transition shadow-sm">Review</Link>
                                    <a :href="route('reports.survey', survey.id)" class="inline-block bg-geo-navy dark:bg-geo-teal text-white dark:text-geo-navy px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-geo-light-navy dark:hover:bg-geo-teal/80 transition shadow-sm">PDF</a>
                                </td>
                            </tr>
                            <tr v-if="filteredSurveys.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-geo-slate italic">Zero survey logs aligned with current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
