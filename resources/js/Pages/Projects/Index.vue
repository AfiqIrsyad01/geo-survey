<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    projects: Array
});

const searchQuery = ref('');
const filterDate = ref('');

const filteredProjects = computed(() => {
    return props.projects.filter(project => {
        const matchesSearch = project.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesDate = filterDate.value ? project.created_at.startsWith(filterDate.value) : true;
        return matchesSearch && matchesDate;
    });
});

const resetFilters = () => {
    searchQuery.value = '';
    filterDate.value = '';
};

const form = useForm({});

function deleteProject(id) {
    if (confirm('Are you sure you want to decommission this project zone? All related surveys will be deleted.')) {
        form.delete(route('projects.destroy', id));
    }
}
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center transition-colors">
                <h2 class="font-black text-xl text-geo-navy dark:text-white leading-tight transition-colors">Spatial Project Assets</h2>
                <div class="flex items-center gap-4">
                    <a v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.bulk.projects.export')" class="hidden md:flex items-center gap-2 bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-geo-navy dark:text-gray-300 px-6 py-2 rounded-xl font-bold shadow-sm hover:border-geo-teal hover:text-geo-teal transition-all">
                        <i class="fa-solid fa-file-csv"></i> Export Registry
                    </a>
                    <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('projects.create')" class="bg-geo-blue dark:bg-geo-teal text-white dark:text-geo-navy px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-blue-600 dark:hover:bg-geo-teal/80 transition-all">
                        + New Project Zone
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Search & Filters -->
                <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] flex flex-col md:flex-row gap-4 items-center justify-between transition-colors duration-500">
                    <div class="relative w-full md:w-96">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-geo-slate dark:text-gray-500"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search by project name..." class="w-full pl-12 pr-4 py-3 rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 focus:ring-geo-teal focus:border-geo-teal text-sm text-geo-navy dark:text-white transition-all" />
                    </div>
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="flex items-center gap-2">
                            <input v-model="filterDate" type="date" class="rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 focus:ring-geo-teal text-sm text-geo-slate dark:text-gray-400 transition-all" />
                            <button 
                                v-if="searchQuery || filterDate" 
                                @click="resetFilters"
                                class="p-2.5 rounded-xl bg-gray-100 dark:bg-white/5 text-geo-slate dark:text-gray-400 hover:text-geo-teal hover:bg-teal-50 dark:hover:bg-geo-teal/10 transition-colors border border-transparent hover:border-geo-teal/20"
                                title="Reset Filters"
                            >
                                <i class="fa-solid fa-rotate-right"></i>
                            </button>
                        </div>
                        <span class="text-xs font-bold text-geo-slate dark:text-gray-500 uppercase tracking-widest">{{ filteredProjects.length }} Results</span>
                    </div>
                </div>

                <div class="bg-[var(--geo-surface)] overflow-hidden shadow-xl sm:rounded-3xl border border-[var(--geo-border)] p-2 transition-colors duration-500">
                    <table class="min-w-full divide-y divide-[var(--geo-border)]">
                        <thead class="bg-gray-50 dark:bg-white/5 transition-colors uppercase text-[10px] font-black tracking-widest text-geo-slate dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-4 text-left">Project Name</th>
                                <th class="px-6 py-4 text-left">Internal Scope</th>
                                <th class="px-6 py-4 text-left">Submitter</th>
                                <th class="px-6 py-4 text-left">Creation Date</th>
                                <th class="px-6 py-4 text-right">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--geo-border)] bg-[var(--geo-surface)] text-sm transition-colors">
                            <tr v-for="project in filteredProjects" :key="project.id" class="hover:bg-blue-50/30 dark:hover:bg-geo-teal/5 transition group">
                                <td class="px-6 py-5">
                                    <div class="font-bold text-geo-navy dark:text-white transition-colors group-hover:text-geo-blue dark:group-hover:text-geo-teal">{{ project.name }}</div>
                                    <div class="text-[10px] text-geo-slate dark:text-gray-500 font-mono uppercase">ID: PROJ-{{ String(project.id).padStart(4, '0') }}</div>
                                </td>
                                <td class="px-6 py-5 text-geo-slate dark:text-gray-400 line-clamp-1 max-w-xs transition-colors">{{ project.description || 'N/A' }}</td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-white/10 text-[11px] font-bold text-geo-navy dark:text-geo-teal transition-colors">
                                        {{ project.user?.name || 'System' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-geo-slate dark:text-gray-400 font-medium transition-colors">{{ new Date(project.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-5 text-right space-x-4">
                                    <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('projects.edit', project.id)" class="text-geo-blue dark:text-geo-teal hover:text-blue-800 dark:hover:text-geo-teal/80 font-bold text-xs uppercase transition">View/Edit</Link>
                                    <button v-if="$page.props.auth.user.role === 'admin'" @click="deleteProject(project.id)" class="text-red-400 hover:text-red-600 font-bold text-xs uppercase transition">Decommission</button>
                                </td>
                            </tr>
                            <tr v-if="filteredProjects.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-geo-slate italic">Zero project assets aligned with current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
