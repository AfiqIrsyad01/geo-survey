<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, onBeforeUnmount, watch, computed } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import * as turf from '@turf/turf';

const mapContainer = ref(null);
const map = ref(null);
const points = ref([]);
const boundary = ref(null);
const isSimple = ref(true);

const props = defineProps({
    staff: Array
});

const searchQuery = ref('');
const isSearching = ref(false);

const form = useForm({
    name: '',
    description: '',
    deadline_date: '',
    cost: '',
    boundary: null, // GeoJSON
    assigned_staff: [], // Array of user IDs
});

const markers = ref([]);
const areaKm2 = ref(0);
const locationInfo = ref({ state: '', district: '', postcode: '' });
const isFetchingLocation = ref(false);



const fetchLocationData = async (lat, lng) => {
    isFetchingLocation.value = true;
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();
        if (data.address) {
            locationInfo.value = {
                state: data.address.state || data.address.region || '',
                district: data.address.city || data.address.town || data.address.district || '',
                postcode: data.address.postcode || ''
            };
        }
    } catch (e) {
        console.error("Location Fetch Error:", e);
    } finally {
        isFetchingLocation.value = false;
    }
};

const applyAutofill = () => {
    if (locationInfo.value.district && locationInfo.value.state) {
        form.name = `${locationInfo.value.district}, ${locationInfo.value.state} Operation`;
    }
};



const setupLayers = () => {
    if (!map.value || !map.value.isStyleLoaded()) return;

    const ensureSource = (id, type) => {
        if (!map.value.getSource(id)) {
            map.value.addSource(id, { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
        }
    };

    const ensureLayer = (id, type, source, paint, layout = {}) => {
        if (!map.value.getLayer(id)) {
            map.value.addLayer({ id, type, source, paint, layout });
        } else {
            map.value.moveLayer(id);
        }
    };

    ensureSource('draw-line');
    ensureSource('draw-poly');
    ensureSource('draw-kinks');

    ensureLayer('draw-poly-fill', 'fill', 'draw-poly', {
        'fill-color': '#10b981',
        'fill-opacity': 0.3
    });

    ensureLayer('draw-line-layer', 'line', 'draw-line', {
        'line-color': '#10b981',
        'line-width': 2,
    }, {
        'line-cap': 'round',
        'line-join': 'round'
    });

    ensureLayer('draw-points-layer', 'circle', 'draw-line', {
        'circle-radius': 5,
        'circle-color': '#10b981',
        'circle-stroke-width': 2,
        'circle-stroke-color': '#ffffff'
    });

    ensureLayer('draw-kinks-layer', 'circle', 'draw-kinks', {
        'circle-radius': 10,
        'circle-color': '#ef4444',
        'circle-stroke-width': 2,
        'circle-stroke-color': '#ffffff'
    });

    map.value.off('click', handleInternalClick);
    map.value.on('click', handleInternalClick);
    
    if (points.value.length > 0) updateDrawing();
};

const handleInternalClick = (e) => {
    const { lng, lat } = e.lngLat;
    addPoint(lat, lng);
};

const getMapStyle = () => {
    return document.documentElement.classList.contains('dark') 
        ? 'https://tiles.openfreemap.org/styles/dark' 
        : 'https://tiles.openfreemap.org/styles/bright';
};

onMounted(async () => {
    await nextTick();
    
    map.value = new maplibregl.Map({
        container: mapContainer.value,
        style: getMapStyle(),
        center: [101.6869, 3.1390],
        zoom: 12,
        attributionControl: false
    });

    map.value.addControl(new maplibregl.NavigationControl(), 'bottom-right');

    map.value.on('load', () => {
        setupLayers();
    });
    map.value.on('styledata', setupLayers);

    window.addEventListener('gss-theme-changed', () => {
        if (map.value) map.value.setStyle(getMapStyle());
    });
});

function toggleStaff(id) {
    const idx = form.assigned_staff.indexOf(id);
    if (idx > -1) {
        form.assigned_staff.splice(idx, 1);
    } else {
        form.assigned_staff.push(id);
    }
}

let layerInterval = null;
onMounted(() => {
    layerInterval = setInterval(() => {
        if (map.value && map.value.isStyleLoaded()) {
            setupLayers();
        }
    }, 3000);
});

onBeforeUnmount(() => {
    if (layerInterval) clearInterval(layerInterval);
});

const handleSearch = async () => {
    if (!searchQuery.value) return;
    isSearching.value = true;
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}`);
        const data = await response.json();
        if (data && data.length > 0) {
            const result = data[0];
            map.value.flyTo({
                center: [parseFloat(result.lon), parseFloat(result.lat)],
                zoom: 15,
                speed: 1.2
            });
        }
    } catch (e) {
        console.error("Geocoding Error:", e);
    } finally {
        isSearching.value = false;
    }
};

function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = JSON.parse(e.target.result);
            const feature = data.type === 'FeatureCollection' ? data.features[0] : data;
            
            if (feature.geometry.type !== 'Polygon') {
                Swal.fire({
                    icon: 'error',
                    title: 'GEOMETRY MISMATCH',
                    text: 'Only Polygon geometries are supported for project zones. Points or Lines detected.',
                    confirmButtonColor: '#0a192f'
                });
                return;
            }

            clearDraw();
            const coords = feature.geometry.coordinates[0];
            const pts = coords.slice(0, -1);
            pts.forEach(p => {
                addPoint(p[1], p[0], false);
            });
            updateDrawing();

            const bbox = turf.bbox(feature);
            map.value.fitBounds([[bbox[0], bbox[1]], [bbox[2], bbox[3]]], { padding: 40 });

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'PARSING FAILED',
                text: 'Invalid GeoJSON file format. The core could not digest the spatial structure.',
                confirmButtonColor: '#0a192f'
            });
        }
    };
    reader.readAsText(file);
}

function addPoint(lat, lng, triggerUpdate = true) {
    points.value.push([lng, lat]);
    
    // Add Physical Marker (DOM) - Guarantees visibility
    const el = document.createElement('div');
    el.className = 'w-3 h-3 bg-geo-teal border-2 border-white rounded-full shadow-lg';
    const m = new maplibregl.Marker({ element: el })
        .setLngLat([lng, lat])
        .addTo(map.value);
    markers.value.push(m);

    if (triggerUpdate) updateDrawing();
}

let lastReportedError = null;

watch(() => points.value, (newPoints) => {
    updateDrawing();
}, { deep: true });

function updateDrawing() {
    if (!map.value || !map.value.isStyleLoaded()) return;

    const lineSource = map.value.getSource('draw-line');
    const polySource = map.value.getSource('draw-poly');
    const kinksSource = map.value.getSource('draw-kinks');

    if (!lineSource || !polySource) return;

    try {
        const coords = points.value.map(p => [Number(p[0]), Number(p[1])]);
        
        // 1. Always update the line visibility if we have at least 2 points
        if (coords.length > 1 && lineSource) {
            lineSource.setData({
                "type": "Feature",
                "geometry": { "type": "LineString", "coordinates": coords }
            });
        }

        // 2. Always update the polygon visibility if we have at least 3 points
        if (coords.length >= 3 && polySource) {
            const closedCoords = [...coords, coords[0]];
            polySource.setData({
                "type": "Feature",
                "geometry": { "type": "Polygon", "coordinates": [closedCoords] }
            });

            // Topology Validation
            try {
                const poly = turf.polygon([closedCoords]);
                const kinks = turf.kinks(poly);
                isSimple.value = kinks.features.length === 0;
                boundary.value = poly;
                form.boundary = JSON.stringify(poly.geometry);
                
                if (kinksSource) {
                    kinksSource.setData(JSON.parse(JSON.stringify(kinks)));
                }

                // Spatial Analytics
                areaKm2.value = turf.area(poly) / 1000000;
                
                if (points.value.length >= 3 && !locationInfo.value.state && !isFetchingLocation.value) {
                    fetchLocationData(points.value[0][1], points.value[0][0]);
                }
            } catch (e) {
                isSimple.value = false;
            }

            // Explicit Visual Push to MapLibre with checks
            const color = isSimple.value ? '#10b981' : '#ef4444';
            let opacity = 0.45;
            if (!isSimple.value) opacity = 0.7;
            
            if (map.value.getLayer('draw-poly-fill')) {
                map.value.setPaintProperty('draw-poly-fill', 'fill-color', color);
                map.value.setPaintProperty('draw-poly-fill', 'fill-opacity', opacity);
            }
            if (map.value.getLayer('draw-line-layer')) {
                map.value.setPaintProperty('draw-line-layer', 'line-color', color);
                map.value.setPaintProperty('draw-line-layer', 'line-width', 8);
            }
        }
    } catch (err) {
        if (err.message !== lastReportedError) {
            console.error("GSS Drawing Engine: Sync Failure", err);
            lastReportedError = err.message;
        }
    }
}

function clearDraw() {
    points.value = [];
    boundary.value = null;
    form.boundary = null;
    isSimple.value = true;
    
    markers.value.forEach(m => m.remove());
    markers.value = [];
    
    if (map.value && map.value.getSource('draw-line')) {
        map.value.getSource('draw-line').setData({ type: 'Feature', geometry: { type: 'LineString', coordinates: [] } });
        map.value.getSource('draw-poly').setData({ type: 'Feature', geometry: { type: 'Polygon', coordinates: [[]] } });
        map.value.getSource('draw-kinks').setData({ type: 'FeatureCollection', features: [] });
    }

    areaKm2.value = 0;
    locationInfo.value = { state: '', district: '', postcode: '' };
}

function submit() {
    if (!form.boundary) {
        Swal.fire({
            icon: 'warning',
            title: 'SPATIAL DEFICIT',
            text: 'Please define a project boundary on the map before initialization.',
            confirmButtonColor: '#0a192f'
        });
        return;
    }

    if (!isSimple.value) {
        Swal.fire({
            icon: 'error',
            title: 'TOPOLOGY ERROR',
            text: 'The proposed boundary contains self-intersections (kinks). Please rectify the drawing.',
            confirmButtonColor: '#0a192f'
        });
        return;
    }

    form.post(route('projects.store'), {
        onSuccess: () => {
             Swal.fire({
                icon: 'success',
                title: 'OPERATION INITIALIZED',
                text: 'Project zone has been registered and personnel assets assigned.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}
</script>

<template>
    <Head title="Create Project" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-geo-navy leading-tight">Define New Project Zone</h2>
        </template>

        <div class="py-12 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- 1. HEADER & SEARCH -->
                <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] flex flex-col md:flex-row justify-between items-center gap-6 transition-colors duration-500">
                    <div>
                        <h3 class="text-[10px] font-black text-geo-teal uppercase tracking-[0.3em] mb-1">Strategic Planning</h3>
                        <p class="text-sm text-geo-navy dark:text-gray-100 font-bold italic transition-colors">"Define precise operational boundaries for geocoded intelligence gathering."</p>
                    </div>
                    <div class="w-full md:w-96 relative group">
                        <input 
                            v-model="searchQuery" 
                            @keyup.enter="handleSearch"
                            type="text" 
                            placeholder="Identify Site Location (e.g. Putrajaya)..." 
                            class="w-full bg-gray-50 dark:bg-white/5 border-none rounded-2xl px-12 py-3.5 text-xs font-bold text-geo-navy dark:text-white shadow-inner focus:ring-2 focus:ring-geo-teal transition-all"
                        />
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-geo-navy dark:text-gray-400">
                            <i v-if="!isSearching" class="fa-solid fa-location-dot"></i>
                            <i v-else class="fa-solid fa-circle-notch fa-spin text-geo-teal"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. EXPANDED MAP INTERFACE (CENTRAL HUB) -->
                <div class="w-full h-[600px] relative rounded-[2.5rem] overflow-hidden shadow-2xl border-4 border-white bg-slate-100 group transition-all duration-700">
                    <div ref="mapContainer" class="absolute inset-0 z-10 w-full h-full text-geo-navy"></div>
                    
                    <!-- VECTOR OPERATIONS OVERLAY -->
                    <div class="absolute top-6 right-6 z-[1000] bg-white/95 dark:bg-geo-navy/95 backdrop-blur-md p-5 rounded-2xl shadow-2xl border border-gray-100 dark:border-white/5 max-w-[240px] animate-fade-in transition-colors">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-geo-teal animate-pulse shadow-[0_0_10px_#14b8a6]"></div>
                            <h5 class="text-[10px] font-black text-geo-navy dark:text-gray-100 uppercase tracking-widest transition-colors">Vector Acquisition</h5>
                        </div>
                        <p class="text-[9px] leading-relaxed text-geo-slate dark:text-gray-400 font-bold transition-colors">Spatial input active. Deploy 3+ vertices to close the polygon loop. Red indicates topology violation.</p>
                        <div class="mt-4 pt-4 border-t border-gray-50 dark:border-white/5 flex justify-between items-center transition-colors">
                            <span class="text-[8px] font-black text-gray-400 uppercase">{{ points.length }} Nodes</span>
                            <button type="button" @click="clearDraw" class="text-[8px] font-black text-red-500 uppercase hover:underline">Reset Drawing</button>
                        </div>
                    </div>

                    <!-- COORDINATE LOG -->
                    <div v-if="points.length > 0" class="absolute bottom-6 left-6 z-[1000] bg-geo-navy/90 backdrop-blur text-white px-5 py-3 rounded-2xl text-[10px] font-mono shadow-2xl border border-white/10 flex items-center gap-3">
                        <i class="fa-solid fa-crosshairs text-geo-teal"></i>
                        <span>{{ points[points.length-1][1].toFixed(6) }}, {{ points[points.length-1][0].toFixed(6) }}</span>
                    </div>

                    <!-- GEOMETRY HEALTH -->
                    <div v-if="points.length >= 3" class="absolute bottom-6 right-6 z-[1000] flex gap-2">
                        <div :class="isSimple ? 'bg-geo-teal text-geo-navy' : 'bg-red-600 text-white'" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-xl flex items-center gap-2">
                            <i :class="isSimple ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                            {{ isSimple ? 'Topology Valid' : 'Intersect Error' }}
                        </div>
                        <div v-if="areaKm2 > 0" class="bg-white/95 backdrop-blur text-geo-navy px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-xl border border-white/20">
                            {{ areaKm2.toFixed(3) }} km²
                        </div>
                    </div>
                </div>

                <!-- 3. CONFIGURATION & PERSONNEL (BELOW MAP) -->
                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left: Identity & Description -->
                    <div class="bg-[var(--geo-surface)] p-8 rounded-3xl shadow-sm border border-[var(--geo-border)] space-y-6 transition-colors duration-500">
                        <h4 class="text-[10px] font-black text-geo-navy dark:text-white uppercase tracking-[0.2em] mb-4 border-b border-gray-50 dark:border-white/5 pb-4 transition-colors">Zone Configuration</h4>
                        <div>
                            <label class="block text-[9px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-2 ml-1 transition-colors">Mission Identifier</label>
                            <input v-model="form.name" type="text" class="w-full rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 py-4 px-6 text-sm font-bold text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy focus:ring-geo-teal transition-all" placeholder="e.g. Sector-A Regional Survey" required />
                        </div>

                        <div>
                            <label class="block text-[9px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-2 ml-1 transition-colors">Operational Scope</label>
                            <textarea v-model="form.description" class="w-full rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 py-4 px-6 text-sm font-medium text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy focus:ring-geo-teal transition-all" rows="4" placeholder="Detail the strategic objectives..."></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[9px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-2 ml-1 transition-colors">Deadline Date</label>
                                <input v-model="form.deadline_date" type="date" class="w-full rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 py-3 px-4 text-sm font-bold text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy focus:ring-geo-teal transition-all" />
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest mb-2 ml-1 transition-colors">Estimated Cost (MYR)</label>
                                <input v-model="form.cost" type="number" step="0.01" min="0" class="w-full rounded-2xl border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 py-3 px-4 text-sm font-bold text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy focus:ring-geo-teal transition-all" placeholder="0.00" />
                            </div>
                        </div>
                    </div>

                    <!-- Middle: Personnel Deployment -->
                    <div class="bg-[var(--geo-surface)] p-8 rounded-3xl shadow-sm border border-[var(--geo-border)] space-y-6 transition-colors duration-500">
                        <div class="flex items-center justify-between mb-4 border-b border-gray-50 dark:border-white/5 pb-4 transition-colors text-geo-navy dark:text-white">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em]">Personnel Deployment</h4>
                            <span class="text-[9px] font-black bg-geo-navy dark:bg-geo-teal text-geo-teal dark:text-geo-navy px-2 py-0.5 rounded italic transition-colors">{{ form.assigned_staff.length }} Units</span>
                        </div>
                        
                        <div class="h-[210px] overflow-y-auto pr-3 space-y-2 custom-scrollbar">
                            <div v-for="person in staff" :key="person.id" 
                                @click="toggleStaff(person.id)"
                                class="flex items-center justify-between p-3 rounded-2xl cursor-pointer transition-all border group"
                                :class="form.assigned_staff.includes(person.id) ? 'bg-teal-50 dark:bg-geo-teal/10 border-geo-teal/30 shadow-md' : 'bg-gray-50 dark:bg-white/5 border-transparent hover:border-gray-200 dark:hover:border-white/10'"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-[10px] font-black transition-all shadow-sm"
                                        :class="form.assigned_staff.includes(person.id) ? 'bg-geo-teal text-geo-navy' : 'bg-white dark:bg-white/10 text-gray-400 group-hover:text-geo-navy dark:group-hover:text-white'">
                                        {{ person.name.charAt(0) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black transition-colors" :class="form.assigned_staff.includes(person.id) ? 'text-geo-navy dark:text-geo-teal' : 'text-gray-500'">{{ person.name }}</span>
                                        <span class="text-[8px] text-gray-400 uppercase tracking-tighter">Field Operative</span>
                                    </div>
                                </div>
                                <i v-if="form.assigned_staff.includes(person.id)" class="fa-solid fa-circle-check text-geo-teal text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Intel Sync & Finalize -->
                    <div class="bg-geo-navy p-8 rounded-3xl shadow-2xl text-white flex flex-col justify-between">
                        <div class="space-y-6">
                            <h4 class="text-[10px] font-black text-geo-teal uppercase tracking-[0.3em] mb-4 border-b border-white/10 pb-4">Mission Intelligence</h4>
                            
                            <div v-if="boundary" class="space-y-4 animate-fade-in">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-3 bg-white/5 rounded-2xl border border-white/5">
                                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-tighter mb-1">Postcode</p>
                                        <p class="text-xs font-black">{{ locationInfo.postcode || 'Syncing...' }}</p>
                                    </div>
                                    <div class="p-3 bg-white/5 rounded-2xl border border-white/5">
                                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-tighter mb-1">State</p>
                                        <p class="text-xs font-black truncate">{{ locationInfo.state || 'Syncing...' }}</p>
                                    </div>
                                </div>
                                <div class="p-3 bg-white/5 rounded-2xl border border-white/5">
                                    <p class="text-[8px] text-gray-400 font-black uppercase tracking-tighter mb-1">Administrative District</p>
                                    <p class="text-xs font-black">{{ locationInfo.district }} {{ locationInfo.district ? ',' : '' }} {{ locationInfo.state }}</p>
                                </div>
                                <button type="button" @click="applyAutofill" class="w-full py-2 bg-geo-teal/10 hover:bg-geo-teal text-geo-teal hover:text-geo-navy text-[9px] font-black uppercase rounded-xl border border-geo-teal/30 transition-all">
                                    Sync Metadata to Name
                                </button>
                            </div>
                            
                            <!-- External Import -->
                            <div v-else class="space-y-4">
                                <p class="text-[10px] text-gray-400 italic">No spatial data detected. Map acquisition active or import file...</p>
                                <label class="w-full flex items-center justify-center gap-3 py-4 bg-white/5 border-2 border-dashed border-white/10 rounded-2xl cursor-pointer hover:bg-white/10 transition-all">
                                    <i class="fa-solid fa-file-import text-geo-teal"></i>
                                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-300">Digest GeoJSON</span>
                                    <input type="file" @change="handleFileUpload" accept=".json,.geojson" class="hidden" />
                                </label>
                            </div>
                        </div>

                        <button @click="submit" :disabled="form.processing" 
                            class="w-full mt-8 bg-gradient-to-r from-geo-teal to-teal-400 text-geo-navy py-5 rounded-2xl font-black shadow-xl hover:brightness-110 active:scale-95 disabled:opacity-20 transition-all uppercase tracking-widest text-[10px] flex items-center justify-center gap-3">
                            <i class="fa-solid fa-satellite"></i>
                            Initialize Project Zone
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.maplibregl-canvas {
    cursor: crosshair !important;
}
</style>
