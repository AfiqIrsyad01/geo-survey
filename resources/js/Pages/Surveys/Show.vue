<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MapViewer from '@/Components/MapViewer.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    survey: Object
});

const location = computed(() => {
    if (!props.survey.location) return { lat: 0, lng: 0 };
    const geo = JSON.parse(props.survey.location);
    return {
        lng: geo.coordinates[0],
        lat: geo.coordinates[1]
    };
});

const approvalForm = useForm({
    decision: '',
    comments: ''
});

const reportOptions = ref({
    map: true,
    images: true,
    approvals: true
});

const mapViewerRef = ref(null);

function submitApproval(decision) {
    Swal.fire({
        title: `${decision.toUpperCase()} SURVEY?`,
        text: `This decision will be finalized in the audit log for record SRV-${props.survey.id}.`,
        icon: decision === 'approved' ? 'success' : 'warning',
        showCancelButton: true,
        confirmButtonColor: decision === 'approved' ? '#10b981' : '#ef4444',
        confirmButtonText: `YES, ${decision.toUpperCase()}`
    }).then((result) => {
        if (result.isConfirmed) {
            approvalForm.decision = decision;
            approvalForm.post(route('surveys.approve', props.survey.id));
        }
    });
}

function downloadReport() {
    let mapImageBase64 = null;
    
    // Attempt to extract live WebGL canvas if Map is requested
    if (reportOptions.value.map && mapViewerRef.value) {
        mapImageBase64 = mapViewerRef.value.getSnapshotDataUrl();
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('reports.survey', props.survey.id);
    form.target = '_blank';
    
    // CSRF Protection
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);

    const mapInput = document.createElement('input');
    mapInput.type = 'hidden';
    mapInput.name = 'map';
    mapInput.value = reportOptions.value.map ? '1' : '0';
    form.appendChild(mapInput);

    const imagesInput = document.createElement('input');
    imagesInput.type = 'hidden';
    imagesInput.name = 'images';
    imagesInput.value = reportOptions.value.images ? '1' : '0';
    form.appendChild(imagesInput);

    const approvalsInput = document.createElement('input');
    approvalsInput.type = 'hidden';
    approvalsInput.name = 'approvals';
    approvalsInput.value = reportOptions.value.approvals ? '1' : '0';
    form.appendChild(approvalsInput);

    // Inject Base64 Map Screenshot
    if (mapImageBase64) {
        const mapImageInput = document.createElement('input');
        mapImageInput.type = 'hidden';
        mapImageInput.name = 'map_image';
        mapImageInput.value = mapImageBase64;
        form.appendChild(mapImageInput);
    }

    document.body.appendChild(form);
    form.submit();
    
    // Cleanup DOM
    setTimeout(() => {
        document.body.removeChild(form);
    }, 100);
}
</script>

<template>
    <Head title="Survey Report" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-xl text-geo-navy dark:text-white leading-tight transition-colors">
                    Survey Report #{{ survey.id }}
                </h2>
                <div class="flex items-center gap-3">
                    <!-- Report Intel Selector -->
                    <div class="flex items-center gap-2 bg-gray-100 dark:bg-white/5 p-1 rounded-xl border border-gray-200 dark:border-white/5 transition-colors">
                        <label class="flex items-center gap-1.5 px-2 py-1 cursor-pointer group/opt text-[9px] font-black uppercase text-geo-slate dark:text-gray-400 group-hover/opt:text-geo-navy dark:group-hover/opt:text-white transition-colors">
                            <input type="checkbox" v-model="reportOptions.map" class="w-3 h-3 rounded border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-geo-blue focus:ring-geo-blue">
                            <span>Map</span>
                        </label>
                        <label class="flex items-center gap-1.5 px-2 py-1 cursor-pointer group/opt text-[9px] font-black uppercase text-geo-slate dark:text-gray-400 group-hover/opt:text-geo-navy dark:group-hover/opt:text-white transition-colors">
                            <input type="checkbox" v-model="reportOptions.images" class="w-3 h-3 rounded border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-geo-blue focus:ring-geo-blue">
                            <span>Images</span>
                        </label>
                        <label class="flex items-center gap-1.5 px-2 py-1 cursor-pointer group/opt text-[9px] font-black uppercase text-geo-slate dark:text-gray-400 group-hover/opt:text-geo-navy dark:group-hover/opt:text-white transition-colors">
                            <input type="checkbox" v-model="reportOptions.approvals" class="w-3 h-3 rounded border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-geo-blue focus:ring-geo-blue">
                            <span>Audit</span>
                        </label>
                    </div>

                    <button 
                        @click="downloadReport"
                        class="bg-geo-navy text-white px-5 py-2.5 rounded-xl text-xs font-black shadow-lg shadow-geo-navy/10 hover:brightness-125 transition-all uppercase tracking-widest"
                    >
                        <i class="fa-solid fa-file-pdf mr-2 text-geo-teal"></i>
                        Download PDF
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Metadata & Map -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- Map View -->
                        <div class="bg-[var(--geo-surface)] p-6 rounded-2xl shadow-sm border border-[var(--geo-border)] transition-colors duration-500">
                            <h3 class="text-lg font-bold text-geo-navy dark:text-white mb-4 transition-colors">Spatial Verification</h3>
                            <MapViewer 
                                ref="mapViewerRef"
                                :modelValue="location" 
                                :boundary="survey.project?.boundary"
                                readOnly 
                            />
                            <div class="mt-4 flex items-center justify-between text-sm">
                                <span class="text-geo-slate dark:text-gray-400">Validated Coordinates:</span>
                                <div class="flex items-center gap-4">
                                     <div v-if="survey.attributes && JSON.parse(survey.attributes).asl" class="flex items-center gap-1.5 px-3 py-1 bg-geo-teal/10 rounded-lg border border-geo-teal/20">
                                         <i class="fa-solid fa-mountain-sun text-geo-teal text-[10px]"></i>
                                         <span class="text-[11px] font-black text-geo-navy dark:text-geo-teal uppercase">{{ JSON.parse(survey.attributes).asl }}m ASL</span>
                                     </div>
                                     <span class="font-mono font-bold text-geo-navy dark:text-geo-teal transition-colors">{{ location.lat.toFixed(6) }}, {{ location.lng.toFixed(6) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Image Evidence -->
                        <div class="bg-[var(--geo-surface)] p-6 rounded-2xl shadow-sm border border-[var(--geo-border)] transition-colors duration-500">
                            <h3 class="text-lg font-bold text-geo-navy dark:text-white mb-4 transition-colors">Evidence Gallery (Watermarked)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="image in survey.images" :key="image.id" class="group relative rounded-xl overflow-hidden shadow-md border border-[var(--geo-border)]">
                                    <img :src="'/storage/' + image.image_path" class="w-full h-64 object-cover" />
                                    <div class="absolute inset-0 bg-geo-navy bg-opacity-0 group-hover:bg-opacity-40 transition-all flex items-end p-4">
                                        <p class="text-white text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity">
                                            Captured: {{ new Date(image.created_at).toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="survey.images.length === 0" class="py-12 text-center text-geo-slate dark:text-gray-500 italic transition-colors">
                                No photographic evidence uploaded for this survey.
                            </div>
                        </div>
                    </div>

                    <!-- Right: Status, Project, & Approval -->
                    <div class="space-y-8">
                        
                        <!-- Status Card -->
                        <div class="bg-[var(--geo-surface)] p-6 rounded-2xl shadow-sm border border-[var(--geo-border)] transition-colors duration-500">
                            <h3 class="text-sm font-bold text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-4 transition-colors">Operational Status</h3>
                            <div class="flex items-center space-x-4">
                                <span :class="{
                                    'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400 ring-yellow-200 dark:ring-yellow-900/30': survey.status === 'pending',
                                    'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400 ring-green-200 dark:ring-green-900/30': survey.status === 'approved',
                                    'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400 ring-red-200 dark:ring-red-900/30': survey.status === 'rejected'
                                }" class="px-3 py-1 rounded-full text-sm font-bold ring-1 uppercase transition-colors">
                                    {{ survey.status }}
                                </span>
                                <span class="text-xs text-geo-slate dark:text-gray-500 transition-colors">{{ new Date(survey.created_at).toLocaleDateString() }}</span>
                            </div>
                            
                            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-white/5">
                                <p class="text-xs text-geo-slate dark:text-gray-400 font-bold uppercase tracking-tighter transition-colors">Assigned Project</p>
                                <p class="text-lg font-bold text-geo-navy dark:text-white transition-colors">{{ survey.project?.name }}</p>
                                <p class="text-sm text-geo-slate dark:text-gray-400 mt-1 line-clamp-2 transition-colors">{{ survey.project?.description }}</p>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5">
                                <p class="text-xs text-geo-slate dark:text-gray-400 font-bold uppercase tracking-tighter transition-colors">Submitted By</p>
                                <p class="text-md font-bold text-geo-navy dark:text-gray-100 transition-colors">{{ survey.user?.name }}</p>
                                <p class="text-xs text-geo-slate dark:text-gray-500 transition-colors">{{ survey.user?.email }}</p>
                            </div>
                        </div>

                        <!-- HOD Approval Logic -->
                        <div v-if="$page.props.auth.user.role === 'hod' && survey.status === 'pending'" class="bg-geo-navy p-6 rounded-2xl shadow-xl text-white">
                            <h3 class="text-lg font-bold text-geo-teal mb-4">HOD Review Console</h3>
                            <div class="space-y-4">
                                <textarea v-model="approvalForm.comments" rows="3" class="w-full rounded-lg bg-geo-light-navy border-geo-slate text-sm text-white focus:ring-geo-teal" placeholder="Review comments..."></textarea>
                                <div class="grid grid-cols-2 gap-4">
                                    <button @click="submitApproval('approved')" class="bg-geo-teal text-geo-navy py-2 rounded-lg font-bold text-sm hover:brightness-110">Approve</button>
                                    <button @click="submitApproval('rejected')" class="bg-red-500 text-white py-2 rounded-lg font-bold text-sm hover:bg-red-600">Reject</button>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Log -->
                        <div v-if="survey.approvals.length > 0" class="bg-[var(--geo-surface)] p-6 rounded-2xl shadow-sm border border-[var(--geo-border)] transition-colors duration-500">
                            <h3 class="text-sm font-bold text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-4 transition-colors">Review History</h3>
                            <div v-for="approval in survey.approvals" :key="approval.id" class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-geo-navy dark:text-gray-100 text-sm transition-colors">{{ approval.user.name }}</span>
                                    <span :class="approval.decision === 'approved' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-xs font-bold uppercase transition-colors">{{ approval.decision }}</span>
                                </div>
                                <p class="text-sm text-geo-slate dark:text-gray-400 italic transition-colors">"{{ approval.comments || 'No comments provided' }}"</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 transition-colors">{{ new Date(approval.created_at).toLocaleString() }}</p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
