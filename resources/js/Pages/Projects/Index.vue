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

const downloadGeoJSON = (project) => {
    if (!project.boundary_geojson && !project.boundary) {
        alert('No boundary data available for this project.');
        return;
    }
    
    let geojsonData = project.boundary_geojson || project.boundary;
    if (typeof geojsonData === 'string') {
        try {
            geojsonData = JSON.parse(geojsonData);
        } catch (e) {
            console.error("Invalid GeoJSON string", e);
            alert('Failed to parse boundary data.');
            return;
        }
    }
    
    let finalGeoJSON = geojsonData;
    if (finalGeoJSON.type === 'Polygon' || finalGeoJSON.type === 'MultiPolygon') {
        finalGeoJSON = {
            type: 'FeatureCollection',
            features: [
                {
                    type: 'Feature',
                    properties: {
                        id: project.id,
                        name: project.name,
                        description: project.description
                    },
                    geometry: finalGeoJSON
                }
            ]
        };
    } else if (finalGeoJSON.type === 'Feature') {
        finalGeoJSON.properties = { ...finalGeoJSON.properties, id: project.id, name: project.name, description: project.description };
        finalGeoJSON = {
            type: 'FeatureCollection',
            features: [finalGeoJSON]
        };
    }
    
    const blob = new Blob([JSON.stringify(finalGeoJSON, null, 2)], { type: 'application/geo+json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `project_${project.id}_boundary.geojson`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};
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
                                <th class="px-6 py-4 text-left">Scope</th>
                                <th class="px-6 py-4 text-left">Assigner</th>
                                <th class="px-6 py-4 text-left">Assigned Date</th>
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
                                <td class="px-6 py-5 text-right space-x-2">
                                    <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('projects.edit', project.id)" title="View/Edit" class="inline-block align-middle bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-geo-navy dark:text-gray-300 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-white/10 transition shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </Link>
                                    <button @click="downloadGeoJSON(project)" title="Download GeoJSON" class="inline-block align-middle bg-geo-navy dark:bg-geo-teal text-white dark:text-geo-navy p-2 rounded-lg hover:bg-geo-light-navy dark:hover:bg-geo-teal/80 transition shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                    </button>
                                    <button v-if="$page.props.auth.user.role === 'admin'" @click="deleteProject(project.id)" title="Decommission" class="inline-block align-middle bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-900/30 p-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
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
