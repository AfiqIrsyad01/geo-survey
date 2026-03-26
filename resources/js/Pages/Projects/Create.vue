<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import * as turf from '@turf/turf';

const mapContainer = ref(null);
const map = ref(null);
const markers = ref([]);
const lineLayer = ref(null);
const polygonLayer = ref(null);
const points = ref([]);
const boundary = ref(null);

const form = useForm({
    name: '',
    description: '',
    boundary: null, // GeoJSON
});

onMounted(async () => {
    await nextTick();
    
    // Initialize Leaflet
    map.value = L.map(mapContainer.value, {
        zoomControl: true,
        attributionControl: false
    }).setView([3.1390, 101.6869], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map.value);

    // Fix for Leaflet Default Icon
    const DefaultIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });
    L.Marker.prototype.options.icon = DefaultIcon;

    map.value.on('click', (e) => {
        const { lat, lng } = e.latlng;
        addPoint(lat, lng);
    });

    // Invalidate size after mount to ensure proper rendering
    setTimeout(() => {
        map.value.invalidateSize();
    }, 400);
});

function addPoint(lat, lng) {
    const pt = [lng, lat];
    points.value.push(pt);
    
    // Add visual point marker
    const m = L.circleMarker([lat, lng], {
        radius: 6,
        fillColor: '#00f2fe',
        color: '#fff',
        weight: 2,
        fillOpacity: 1
    }).addTo(map.value);
    markers.value.push(m);

    updateDrawing();
}

function updateDrawing() {
    // Remove old layers
    if (lineLayer.value) map.value.removeLayer(lineLayer.value);
    if (polygonLayer.value) map.value.removeLayer(polygonLayer.value);

    if (points.value.length > 1) {
        const coords = [...points.value];
        
        // Draw Line
        lineLayer.value = L.polyline(points.value.map(p => [p[1], p[0]]), {
            color: '#00f2fe',
            weight: 3
        }).addTo(map.value);

        if (points.value.length > 2) {
            // Close the polygon for preview
            const polyCoords = [...coords, coords[0]];
            polygonLayer.value = L.polygon(polyCoords.map(p => [p[1], p[0]]), {
                color: '#00f2fe',
                fillColor: '#00f2fe',
                fillOpacity: 0.2
            }).addTo(map.value);

            // Update Form Data
            const poly = turf.polygon([[...coords, coords[0]]]);
            boundary.value = poly;
            form.boundary = JSON.stringify(poly);
        }
    }
}

function clearDraw() {
    points.value = [];
    boundary.value = null;
    form.boundary = null;
    
    markers.value.forEach(m => map.value.removeLayer(m));
    markers.value = [];
    
    if (lineLayer.value) map.value.removeLayer(lineLayer.value);
    if (polygonLayer.value) map.value.removeLayer(polygonLayer.value);
    
    lineLayer.value = null;
    polygonLayer.value = null;
}

function submit() {
    if (!form.boundary) {
        alert('Please draw a project boundary on the map first.');
        return;
    }
    form.post(route('projects.store'), {
        onSuccess: () => alert('Project Initialized Successfully!'),
        onError: () => alert('Failed to create project. Please check form fields.')
    });
}
</script>

<template>
    <Head title="Create Project" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-geo-navy leading-tight">Define New Project Zone</h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-8">
                
                <!-- Form Settings -->
                <div class="w-full lg:w-1/3">
                    <form @submit.prevent="submit" class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-geo-navy mb-2">Project Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 focus:border-geo-teal focus:ring-geo-teal" placeholder="e.g. KVMRT Phase 3" required />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-geo-navy mb-2">Internal Description</label>
                            <textarea v-model="form.description" class="w-full rounded-lg border-gray-300 focus:border-geo-teal focus:ring-geo-teal" rows="3" placeholder="Scope and operational goals..."></textarea>
                        </div>

                        <div class="p-4 bg-geo-navy rounded-xl text-white">
                            <h4 class="text-xs font-bold uppercase tracking-widest text-geo-teal mb-2">Boundary Status</h4>
                            <p v-if="!boundary" class="text-sm opacity-80">Click points on the map to define the operational area (minimum 3 points).</p>
                            <div v-else class="space-y-2">
                                <p class="text-sm text-geo-teal font-bold flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    Polygon Defined
                                </p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ points.length }} Vertices recorded</p>
                            </div>
                            
                            <button type="button" @click.stop.prevent="clearDraw" class="mt-4 text-xs font-bold underline text-red-400 hover:text-red-300 transition-colors uppercase tracking-widest">Restart Drawing</button>
                        </div>

                        <div class="pt-6">
                            <button type="submit" :disabled="form.processing" class="w-full bg-geo-blue text-white py-4 rounded-xl font-bold shadow-lg hover:bg-blue-600 active:scale-95 transition-all">
                                Initialise Project Zone
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Map Interface -->
                <div class="w-full lg:w-2/3 h-[600px] relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-100 group">
                    <!-- Loading Mask -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none group-hover:opacity-0 transition-opacity">
                        <span class="text-geo-navy/10 font-black text-6xl uppercase">Project Designer</span>
                    </div>

                    <div ref="mapContainer" class="absolute inset-0 z-10"></div>
                    
                    <!-- Tooltip Overlay -->
                    <div class="absolute top-4 left-4 z-[1000] bg-white/90 backdrop-blur-md p-4 rounded-2xl shadow-xl border border-gray-100 max-w-xs transition-transform hover:scale-105">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-3 h-3 rounded-full bg-geo-blue"></div>
                            <h5 class="text-xs font-black text-geo-navy uppercase">Vector Operations</h5>
                        </div>
                        <p class="text-[10px] leading-relaxed text-geo-slate">Click anywhere to place a spatial vertex. The system will automatically close the loop once you have 3 or more points.</p>
                    </div>

                    <!-- Coordinates Display -->
                    <div v-if="points.length > 0" class="absolute bottom-4 right-4 z-[1000] bg-geo-navy text-white px-3 py-1.5 rounded-lg text-[10px] font-mono shadow-lg border border-white/20">
                        {{ points[points.length-1][1].toFixed(5) }}, {{ points[points.length-1][0].toFixed(5) }}
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.leaflet-container {
    cursor: crosshair !important;
}
</style>
