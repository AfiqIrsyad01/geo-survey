<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    projects: Array
});

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
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-geo-navy leading-tight">Spatial Project Assets</h2>
                <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('projects.create')" class="bg-geo-blue text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-blue-600 transition">
                    + New Project Zone
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-3xl border border-gray-100 p-2">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 uppercase text-[10px] font-black tracking-widest text-geo-slate">
                            <tr>
                                <th class="px-6 py-4 text-left">Project Name</th>
                                <th class="px-6 py-4 text-left">Internal Scope</th>
                                <th class="px-6 py-4 text-left">Submitter</th>
                                <th class="px-6 py-4 text-left">Creation Date</th>
                                <th class="px-6 py-4 text-right">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white text-sm">
                            <tr v-for="project in projects" :key="project.id" class="hover:bg-blue-50/30 transition group">
                                <td class="px-6 py-5">
                                    <div class="font-bold text-geo-navy group-hover:text-geo-blue transition-colors">{{ project.name }}</div>
                                    <div class="text-[10px] text-geo-slate font-mono uppercase">ID: PROJ-{{ String(project.id).padStart(4, '0') }}</div>
                                </td>
                                <td class="px-6 py-5 text-geo-slate line-clamp-1 max-w-xs">{{ project.description || 'N/A' }}</td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-[11px] font-bold text-geo-navy">
                                        {{ project.user?.name || 'System' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-geo-slate font-medium">{{ new Date(project.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-5 text-right space-x-2">
                                    <button @click="deleteProject(project.id)" class="text-red-400 hover:text-red-600 font-bold text-xs uppercase transition">Decommission</button>
                                </td>
                            </tr>
                            <tr v-if="projects.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-geo-slate italic">Zero project assets indexed.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
