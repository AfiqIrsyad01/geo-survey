<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import MapViewer from '@/Components/MapViewer.vue';
import { ref, nextTick } from 'vue';
import * as turf from '@turf/turf';
import { useOfflineStore } from '@/Utils/offlineStore';

const props = defineProps({
    projects: Array
});

const form = useForm({
    project_id: null,
    lat: null,
    lng: null,
    asl: null,
    images: [],
});

const isValidLocation = ref(true);
const isLocationInitialized = ref(false);
const selectedProject = ref(null);
const isFieldMode = ref(false);
const fileInput = ref(null);

// Template refs to the MapViewer instances — used for imperative fly-to
const mapRefDesktop = ref(null);
const mapRefField = ref(null);
const { isOnline, saveSurveyOffline } = useOfflineStore();

const imagePreviews = ref([]);

function handleFileChange(event) {
    const files = Array.from(event.target.files);
    // Append instead of overwrite for better field UX
    form.images = [...form.images, ...files];
    
    // Refresh previews
    imagePreviews.value = [];
    form.images.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviews.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
    });
}

function handleProjectChange() {
    // Cast to Number — HTML selects always emit strings
    const raw = form.project_id;
    const numericId = (raw !== null && raw !== '' && raw !== undefined) ? Number(raw) : null;
    form.project_id = numericId;

    selectedProject.value = (numericId && props.projects)
        ? props.projects.find(p => p.id === numericId) || null
        : null;

    if (selectedProject.value && selectedProject.value.boundary) {
        try {
            const boundaryData = typeof selectedProject.value.boundary === 'string'
                ? JSON.parse(selectedProject.value.boundary)
                : selectedProject.value.boundary;

            // Compute centroid to pre-position the marker
            const feature = (boundaryData.type === 'Feature' || boundaryData.type === 'FeatureCollection')
                ? boundaryData
                : { type: 'Feature', geometry: boundaryData, properties: {} };

            const centroid = turf.centroid(feature);
            form.lat = centroid.geometry.coordinates[1];
            form.lng = centroid.geometry.coordinates[0];
            isLocationInitialized.value = false; // user must still explicitly place pin
            isValidLocation.value = true;

            // Imperatively fly the map — wait for Vue to render the updated boundary prop first
            nextTick(() => {
                const activeMap = isFieldMode.value ? mapRefField.value : mapRefDesktop.value;
                if (activeMap && activeMap.flyToBoundary) {
                    activeMap.flyToBoundary(boundaryData);
                }
            });
        } catch (e) {
            console.error('handleProjectChange error:', e);
        }
    } else {
        selectedProject.value = null;
        form.lat = null;
        form.lng = null;
        isLocationInitialized.value = false;
        isValidLocation.value = true;
    }
}

const submit = () => {
    if (!form.project_id) {
        Swal.fire({
            icon: 'warning',
            title: 'SECTOR MISSING',
            text: 'Please select a target project zone before transmitting log data.',
            confirmButtonColor: '#0a192f'
        });
        return;
    }

    if (!isLocationInitialized.value) {
        Swal.fire({
            icon: 'warning',
            title: 'PINPOINT REQUIRED',
            text: 'Please set a precise location node on the map interface.',
            confirmButtonColor: '#0a192f'
        });
        return;
    }

    if (!isValidLocation.value) {
        Swal.fire({
            title: 'SPATIAL ANOMALY',
            text: 'Your current GPS location is outside the assigned project boundary. Proceed with high-risk transmission?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'PROCEED ANYWAY',
            cancelButtonText: 'RE-CALIBRATE'
        }).then((result) => {
            if (result.isConfirmed) {
                checkImagesAndSubmit();
            }
        });
    } else {
        checkImagesAndSubmit();
    }
};

const checkImagesAndSubmit = () => {
    if (form.images.length === 0) {
        Swal.fire({
            title: 'MISSING EVIDENCE',
            text: 'Operational protocol requires at least one geocoded image to verify site status. Transmission aborted.',
            icon: 'error',
            confirmButtonColor: '#0a192f'
        });
        return;
    }

    // We no longer capture an automatic snapshot to prevent "black ghost images" 
    // and to optimize transmission speed. Manual capture is still available via UI.
    executeSubmit();
};

const dataURLtoFile = (dataurl, filename) => {
    let arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
    bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
    while(n--){
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new File([u8arr], filename, {type:mime});
};

const executeSubmit = () => {
    if (!isOnline.value) {
        // Handle Offline Submission
        const surveyData = {
            project_id: form.project_id,
            lat: form.lat,
            lng: form.lng,
            asl: form.asl,
            images: form.images
        };

        Swal.fire({
            title: 'OFFLINE MODE ACTIVE',
            text: 'Operational signal lost. Staging intelligence in the local GSS Vault for automatic synchronization later.',
            icon: 'info',
            confirmButtonColor: '#0a192f',
            showCancelButton: true,
            confirmButtonText: 'SECURE OFFLINE',
            cancelButtonText: 'CANCEL'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await saveSurveyOffline(surveyData);
                    Swal.fire({
                        icon: 'success',
                        title: 'MISSION SECURED',
                        text: 'Logs have been encrypted and stored in the local vault.',
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        router.visit(route('surveys.index'));
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'VAULT FAILURE',
                        text: 'Critical error while staging data. Local storage may be full.',
                    });
                }
            }
        });
        return;
    }

    form.post(route('surveys.store'), {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'LOG TRANSMITTED',
                text: 'Spatial intelligence has been synchronized with the core.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'TRANSMISSION FAILED',
                text: 'The core rejected the log data. Please verify all data nodes.',
                confirmButtonColor: '#0a192f'
            });
        }
    });
};
</script>

<template>
    <Head title="New Survey" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center transition-colors duration-500">
                <h2 class="font-black text-xl text-geo-navy dark:text-white tracking-tight">Field Intelligence Entry</h2>
                <button @click="isFieldMode = !isFieldMode" 
                    :class="isFieldMode ? 'bg-geo-teal text-geo-navy' : 'bg-gray-200 dark:bg-white/10 text-gray-600 dark:text-gray-400'"
                    class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest transition-all shadow-sm">
                    {{ isFieldMode ? 'Standard UI Off' : 'Field Mode On' }}
                </button>
            </div>
        </template>

        <div class="py-8 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500 relative">
            <!-- Processing Overlay (Premium Experience) -->
            <transition name="fade">
                <div v-if="form.processing" class="fixed inset-0 z-[100] bg-geo-navy/80 backdrop-blur-md flex flex-col items-center justify-center text-white">
                    <div class="relative mb-8">
                        <div class="w-24 h-24 border-4 border-geo-teal/20 rounded-full"></div>
                        <div class="absolute top-0 left-0 w-24 h-24 border-4 border-t-geo-teal rounded-full animate-spin"></div>
                        <i class="fa-solid fa-satellite-dish text-geo-teal text-3xl absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 animate-pulse"></i>
                    </div>
                    <h3 class="text-xl font-black uppercase tracking-[0.4em] mb-2">Transmitting Intel</h3>
                    <p class="text-[10px] font-bold text-geo-teal/60 uppercase tracking-[0.2em] animate-pulse">Synchronizing with Core Repository...</p>
                </div>
            </transition>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- FIELD OPS MODE (Simplified Mobile-First) -->
                <div v-if="isFieldMode" class="space-y-6 pb-20">
                    <!-- Step 1: Project Dropdown but BIG -->
                    <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)]">
                        <label class="block text-[10px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-4">Step 1: Geographic Assignment</label>
                        <select v-model="form.project_id" @change="handleProjectChange" 
                            class="w-full py-4 px-6 rounded-2xl bg-gray-50 dark:bg-white/5 border-none text-lg font-bold text-geo-navy dark:text-white focus:ring-2 focus:ring-geo-teal shadow-inner transition-colors">
                            <option :value="null">Select Project Zone...</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                        </select>
                    </div>

                    <!-- Step 2: Map Visualization BIG -->
                    <div v-if="form.project_id" class="bg-[var(--geo-surface)] p-2 rounded-3xl shadow-sm border border-[var(--geo-border)] overflow-hidden">
                        <div class="h-[730px] relative">
                            <MapViewer 
                                ref="mapRefField"
                                :modelValue="{ lat: form.lat ?? 3.1390, lng: form.lng ?? 101.6869 }" 
                                @update:modelValue="(val) => { form.lat = val.lat; form.lng = val.lng; isLocationInitialized = true }"
                                @elevation="(val) => form.asl = val"
                                :boundary="selectedProject?.boundary"
                                :focusProject="form.project_id"
                                :projects="projects"
                                @validated="(val) => isValidLocation = val"
                            />
                            <div v-if="!isValidLocation" class="absolute bottom-4 left-4 right-4 bg-red-600/90 backdrop-blur-md text-white p-3 rounded-xl text-xs font-black text-center animate-bounce">
                                LOCATION OUTSIDE ASSIGNED ZONE
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Evidence Capture GIANT -->
                    <div v-if="form.project_id" class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] text-center transition-colors">
                        <label class="block text-[10px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-6">Step 2: Evidence Registry</label>
                        <div class="flex flex-wrap gap-4 mb-6">
                            <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group">
                                <img :src="preview" class="w-20 h-20 rounded-xl object-cover border-2 border-gray-100 shadow-sm" />
                            </div>
                            <label class="w-20 h-20 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="file" multiple @change="handleFileChange" class="hidden" />
                                <i class="fa-solid fa-plus text-gray-300"></i>
                            </label>
                        </div>
                        
                        <button type="button" @click="fileInput.click()" 
                            class="w-full py-10 bg-geo-navy dark:bg-white/5 text-white dark:text-geo-teal rounded-3xl flex flex-col items-center justify-center gap-3 active:scale-95 transition-all shadow-xl border border-transparent dark:border-white/10">
                            <i class="fa-solid fa-camera text-4xl text-geo-teal"></i>
                            <span class="text-xs font-black uppercase tracking-widest">Capture Physical Evidence</span>
                        </button>
                        <input type="file" ref="fileInput" multiple @change="handleFileChange" class="hidden" />
                    </div>

                    <!-- Final Action Button STICKY MOBILE -->
                    <div class="fixed bottom-6 left-6 right-6 z-50">
                        <button @click="submit" 
                            :disabled="form.processing || !isLocationInitialized || !isValidLocation"
                            class="w-full py-5 rounded-2xl font-black text-sm uppercase tracking-[0.2em] shadow-2xl transition-all active:scale-95 flex items-center justify-center gap-3"
                            :class="isValidLocation ? 'bg-geo-teal text-geo-navy shadow-geo-teal/30' : 'bg-red-500 text-white cursor-not-allowed'">
                            <i class="fa-solid fa-satellite-dish animate-pulse"></i>
                            Transmit Log Data
                        </button>
                    </div>
                </div>

                <!-- STANDARD DESKTOP VIEW (Optimized UX Flow) -->
                <div v-else class="space-y-8">
                    <!-- Top: Major Form Controls -->
                    <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] grid grid-cols-1 md:grid-cols-2 gap-6 transition-colors duration-500">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-geo-navy dark:text-gray-400 uppercase tracking-[0.2em] mb-1">Geographic Mission Assignment</label>
                            <select v-model="form.project_id" @change="handleProjectChange" class="w-full rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 py-4 text-sm font-black text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy focus:ring-geo-teal transition-all">
                                <option :value="null">Select an active project zone...</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                            </select>
                        </div>
                        <div class="flex items-center justify-end">
                            <div v-if="form.project_id" class="p-4 bg-teal-50 dark:bg-geo-teal/10 border border-geo-teal/20 rounded-2xl flex items-center gap-4">
                                <div class="w-10 h-10 bg-geo-teal rounded-xl flex items-center justify-center text-geo-navy">
                                    <i class="fa-solid fa-map-pin animate-bounce"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-geo-navy dark:text-geo-teal uppercase tracking-widest">Target Locked</p>
                                    <p class="text-xs text-geo-slate dark:text-gray-400 font-medium">Please verify the pinpoint on the spatial engine below.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle: Massive Map Hero -->
                    <div class="bg-[var(--geo-surface)] p-3 rounded-3xl shadow-2xl border border-[var(--geo-border)] relative overflow-hidden transition-colors duration-500">
                        <div class="h-[730px] rounded-2xl overflow-hidden relative">
                            <MapViewer 
                                ref="mapRefDesktop"
                                :modelValue="{ lat: form.lat ?? 3.1390, lng: form.lng ?? 101.6869 }" 
                                @update:modelValue="(val) => { form.lat = val.lat; form.lng = val.lng; isLocationInitialized = true }"
                                @elevation="(val) => form.asl = val"
                                :boundary="selectedProject?.boundary"
                                :focusProject="form.project_id"
                                :projects="projects"
                                @validated="(val) => isValidLocation = val"
                            />
                            
                            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-30 flex gap-4 pointer-events-none">
                                <div v-if="!isValidLocation" class="bg-red-600 text-white px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest shadow-2xl border-2 border-white/20 animate-bounce">
                                    OUTSIDE OPERATIONAL ZONE
                                </div>
                                <div v-else-if="isLocationInitialized" class="bg-geo-teal text-geo-navy px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest shadow-2xl border-2 border-white/20">
                                    COORDINATES VERIFIED
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom: Evidence & Submit -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 bg-[var(--geo-surface)] p-8 rounded-3xl shadow-sm border border-[var(--geo-border)] transition-all duration-500"
                            :class="{ 'opacity-40 grayscale pointer-events-none': !form.project_id || !isLocationInitialized }">
                            <div class="flex items-center justify-between mb-6">
                                <label class="block text-[10px] font-black text-geo-navy dark:text-gray-400 uppercase tracking-[0.2em]">Evidence Registry</label>
                                <span v-if="!form.project_id" class="text-[9px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/20 px-3 py-1 rounded-full border border-amber-100 dark:border-amber-900/30">Select Project First</span>
                            </div>
                            
                            <div class="flex flex-wrap gap-4">
                                <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group w-24 h-24">
                                    <img :src="preview" class="w-full h-full rounded-2xl object-cover border-2 border-gray-200 shadow-sm" />
                                </div>
                                <label class="w-24 h-24 rounded-2xl border-2 border-dashed border-gray-200 dark:border-white/10 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 hover:border-geo-teal transition-all group/upload">
                                    <input type="file" multiple @change="handleFileChange" class="hidden" />
                                    <i class="fa-solid fa-cloud-arrow-up text-gray-300 dark:text-gray-600 group-hover/upload:text-geo-teal transition-colors mb-1"></i>
                                    <span class="text-[8px] font-black uppercase tracking-tighter text-gray-400">Add Intel</span>
                                </label>
                            </div>
                            <p class="text-[9px] font-bold text-geo-slate dark:text-gray-500 uppercase mt-6 opacity-60">{{ form.images.length }} FILES STAGED FOR UPLOAD</p>
                        </div>

                        <div class="bg-geo-navy p-8 rounded-3xl shadow-2xl text-white flex flex-col justify-between">
                            <div>
                                <h4 class="text-[10px] font-black text-geo-teal uppercase tracking-[0.3em] mb-4">Core Transmission</h4>
                                <p class="text-xs text-gray-400 font-medium leading-relaxed italic">"Ensuring high-fidelity spatial data is synchronized with the global operational repository."</p>
                            </div>
                            
                            <button @click="submit" 
                                :disabled="form.processing || !isLocationInitialized || !isValidLocation" 
                                class="w-full mt-6 bg-gradient-to-r from-geo-teal to-teal-400 text-geo-navy py-5 rounded-2xl font-black shadow-xl hover:brightness-110 active:scale-95 disabled:opacity-20 transition-all uppercase tracking-widest text-[10px] flex items-center justify-center gap-3">
                                <i class="fa-solid fa-satellite-dish"></i>
                                Transmit Entry
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
