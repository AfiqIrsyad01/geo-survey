<script setup>
import { onMounted, ref, watch, nextTick, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import * as turf from '@turf/turf';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ lat: 3.1390, lng: 101.6869 })
    },
    boundary: {
        type: Object,
        default: null
    },
    readOnly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'validated']);

const mapContainer = ref(null);
const map = ref(null);
const marker = ref(null);
const boundaryLayer = ref(null);
const error = ref(null);

const initMap = () => {
    if (!mapContainer.value) return;

    try {
        // Destroy existing map instance
        if (map.value) {
            map.value.remove();
        }

        // Initialize Leaflet
        map.value = L.map(mapContainer.value, {
            zoomControl: true, // Enable zoom controls
            attributionControl: false,
            scrollWheelZoom: true
        }).setView([props.modelValue.lat, props.modelValue.lng], 13);

        // Add robust tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            crossOrigin: true
        }).addTo(map.value);

        // Fix for "Missing Images" in Leaflet + Vite
        const DefaultIcon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41]
        });
        L.Marker.prototype.options.icon = DefaultIcon;

        // Add Marker at initial position
        marker.value = L.marker([props.modelValue.lat, props.modelValue.lng], {
            draggable: !props.readOnly
        }).addTo(map.value);

        if (!props.readOnly) {
            marker.value.on('dragend', (e) => {
                const latLng = e.target.getLatLng();
                updateLocation(latLng.lat, latLng.lng);
            });

            map.value.on('click', (e) => {
                const { lat, lng } = e.latlng;
                marker.value.setLatLng(e.latlng);
                updateLocation(lat, lng);
                
                // Optional: Zoom in slightly on click if it's the first pin
                if (map.value.getZoom() < 15) {
                    map.value.setView(e.latlng, 15);
                }
            });
        }

        if (props.boundary) {
            loadBoundary();
        }

        // Aggressive Resize Fix
        setTimeout(() => {
            if (map.value) map.value.invalidateSize();
        }, 300);

    } catch (e) {
        console.error('Leaflet Init Error:', e);
        error.value = "Map failed to load. Check browser console.";
    }
};

const loadBoundary = () => {
    if (!map.value || !props.boundary) return;

    if (boundaryLayer.value) {
        map.value.removeLayer(boundaryLayer.value);
    }

    boundaryLayer.value = L.geoJSON(props.boundary, {
        style: {
            color: '#00f2fe',
            weight: 3,
            opacity: 0.8,
            fillColor: '#00f2fe',
            fillOpacity: 0.1
        }
    }).addTo(map.value);

    const bounds = boundaryLayer.value.getBounds();
    if (bounds.isValid()) {
        map.value.fitBounds(bounds, { padding: [30, 30] });
    }
};

onMounted(async () => {
    await nextTick();
    initMap();

    // Watch for container resize
    const resizeObserver = new ResizeObserver(() => {
        if (map.value) map.value.invalidateSize();
    });
    if (mapContainer.value) resizeObserver.observe(mapContainer.value);
});

watch(() => props.boundary, () => {
    loadBoundary();
}, { deep: true });

function updateLocation(lat, lng) {
    emit('update:modelValue', { lat, lng });
    if (props.boundary) {
        try {
            const pt = turf.point([lng, lat]);
            const poly = props.boundary.type === 'Feature' ? props.boundary.geometry : props.boundary;
            emit('validated', turf.booleanPointInPolygon(pt, poly));
        } catch (e) {}
    }
}

function handleManualSync() {
    if (map.value) {
        // Force the map engine to recalculate its viewport
        map.value.invalidateSize();
        
        // Re-align map to the current marker/model position with precision
        const target = [props.modelValue.lat, props.modelValue.lng];
        map.value.flyTo(target, 16, {
            animate: true,
            duration: 1.5
        });
        
        // Ensure the marker is exactly on the point
        if (marker.value) {
            marker.value.setLatLng(target);
        }
    }
}
</script>

<template>
    <div class="relative w-full h-[450px] bg-slate-100 rounded-2xl overflow-hidden border border-gray-200">
        <!-- Error Overlay -->
        <div v-if="error" class="absolute inset-0 z-[1000] bg-white/90 flex flex-col items-center justify-center p-6">
            <p class="text-red-500 font-bold mb-4">{{ error }}</p>
            <button type="button" @click.stop.prevent="initMap" class="bg-geo-navy text-white px-4 py-2 rounded-lg text-xs font-bold uppercase transition hover:bg-geo-light-navy">RETRY CONNECTION</button>
        </div>

        <!-- Background Diagnostic -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <span class="text-geo-navy/5 font-black text-4xl uppercase">L-Map Engine</span>
        </div>
        
        <div ref="mapContainer" class="absolute inset-0 z-[10] w-full h-full"></div>
        
        <!-- Controls Overlay -->
        <div class="absolute top-4 right-4 z-[1000] flex flex-col gap-2">
            <button type="button" @click.stop.prevent="handleManualSync" 
                class="p-2.5 bg-white shadow-xl rounded-xl text-geo-navy hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="Sync & Accuracy Check">
                <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            </button>
        </div>

        <div class="absolute bottom-4 left-4 z-[1000] bg-geo-navy text-white px-4 py-1.5 rounded-full text-[10px] font-bold shadow-2xl flex items-center space-x-2 border border-white/10">
            <div class="w-2 h-2 rounded-full bg-geo-teal animate-pulse"></div>
            <span class="font-mono tracking-wider text-geo-teal">OSM LIVE:</span>
            <span>{{ modelValue.lat.toFixed(6) }}, {{ modelValue.lng.toFixed(6) }}</span>
        </div>
    </div>
</template>

<style>
/* Leaflet Specific Fixes */
.leaflet-container {
    width: 100% !important;
    height: 100% !important;
    background: transparent !important;
}
.leaflet-tile {
    /* Ensure tiles are visible even if browser has weird opacity filters */
    opacity: 1 !important;
}
</style>
