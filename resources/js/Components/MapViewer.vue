<script setup>
import { onMounted, ref, watch, nextTick, onBeforeUnmount, computed } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
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
    },
    projects: {
        type: Array,
        default: () => []
    },
    focusProject: {
        type: Number,
        default: null
    },
    heatmapData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'validated', 'elevation']);

const mapContainer = ref(null);
const map = ref(null);
const marker = ref(null);
const error = ref(null);
const currentStyle = ref('streets');

const styles = {
    streets: 'https://tiles.openfreemap.org/styles/liberty',
    topo: 'https://tiles.openfreemap.org/styles/bright',
    dark: 'https://tiles.openfreemap.org/styles/dark',
    satellite: {
        'version': 8,
        'sources': {
            'raster-tiles': {
                'type': 'raster',
                'tiles': [
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
                ],
                'tileSize': 256,
                'attribution': 'Esri, Maxar, Earthstar Geographics'
            }
        },
        'layers': [
            {
                'id': 'raster-layer',
                'type': 'raster',
                'source': 'raster-tiles',
                'minzoom': 0,
                'maxzoom': 22
            }
        ]
    }
};

const mousePos = ref({ lat: 0, lng: 0 });
const boundaryOpacity = ref(0.1);
const searchQuery = ref('');
const isSearching = ref(false);

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
                zoom: 16,
                speed: 1.2
            });
            searchQuery.value = result.display_name;
        } else {
            alert("No location found for your search.");
        }
    } catch (e) {
        console.error("Geocoding Error:", e);
    } finally {
        isSearching.value = false;
    }
};

const handleMouseMove = (e) => {
    mousePos.value = { lat: e.lngLat.lat, lng: e.lngLat.lng };
};

const updateBoundaryOpacity = () => {
    if (map.value && map.value.getLayer('boundary-fill')) {
        const opacity = parseFloat(boundaryOpacity.value);
        if (!isNaN(opacity)) {
            map.value.setPaintProperty('boundary-fill', 'fill-opacity', opacity);
        }
    }
};

watch(boundaryOpacity, updateBoundaryOpacity);

const initMap = () => {
    if (!mapContainer.value) return;

    try {
        if (map.value) {
            map.value.remove();
        }

        const initialStyle = document.documentElement.classList.contains('dark') ? 'dark' : 'streets';
        currentStyle.value = initialStyle;

        const startLng = Number(props.modelValue?.lng) || 101.6869;
        const startLat = Number(props.modelValue?.lat) || 3.1390;

        map.value = new maplibregl.Map({
            container: mapContainer.value,
            style: styles[currentStyle.value],
            center: [startLng, startLat],
            zoom: 13,
            attributionControl: false,
            preserveDrawingBuffer: true // Required for Snapshots
        });

        map.value.addControl(new maplibregl.NavigationControl(), 'top-left');
        map.value.addControl(new maplibregl.ScaleControl({ maxWidth: 100, unit: 'metric' }), 'bottom-left');

        map.value.on('load', () => {
            setupMarkerAndListeners();
            if (props.boundary || (props.projects && props.projects.length > 0)) {
                renderBoundary();
                // Initial fit only on first load
                fitToGlobalContext();
            }
            setupHeatmap();
            enable3DBuildings();
            map.value.on('mousemove', handleMouseMove);
            
            // Listen for global theme changes to switch map style
            window.addEventListener('gss-theme-changed', (e) => {
                const isDark = e.detail.isDark;
                if (currentStyle.value === 'streets' && isDark) {
                    currentStyle.value = 'dark';
                    map.value.setStyle(styles.dark);
                } else if (currentStyle.value === 'dark' && !isDark) {
                    currentStyle.value = 'streets';
                    map.value.setStyle(styles.streets);
                }
            });

            // RESTORE RIGHT-CLICK FOR NAVIGATION, USE SHIFT+CLICK FOR NOTES
            map.value.on('click', (e) => {
                if (e.originalEvent.shiftKey) {
                    handleAnnotation(e);
                } else {
                    handleMapClick(e);
                }
            });
        });

        // RE-ADD CUSTOM LAYERS ON STYLE CHANGE
        map.value.on('style.load', () => {
            if (props.boundary || (props.projects && props.projects.length > 0)) renderBoundary();
            if (measurementPoints.value.length > 0) updateMeasurementLayers();
            enable3DBuildings();
            reconstituteSessionMarkers();
        });

    } catch (e) {
        console.error('MapLibre Init Error:', e);
        error.value = "Advanced Map Engine failed to load. Check connection.";
    }
};

const sessionMarkers = ref([]);
const landmarks = ref([]);
const isScanningLandmarks = ref(false);
const currentElevation = ref(null);
const isLoadingElevation = ref(false);

const scanLandmarks = async () => {
    if (!map.value) return;
    isScanningLandmarks.value = true;
    
    // Clear old landmarks
    landmarks.value.forEach(m => m.remove());
    landmarks.value = [];
    
    const { lat, lng } = props.modelValue;
    // Querying for schools, hospitals, police, etc. within 2km (2000 meters)
    const query = `[out:json];node(around:2000,${lat},${lng})[amenity~"hospital|school|university|police|fire_station|government|pharmacy|marketplace|post_office"];out;`;
    
    try {
        const response = await fetch('https://overpass-api.de/api/interpreter', {
            method: 'POST',
            body: query
        });
        const data = await response.json();
        
        if (data.elements && data.elements.length > 0) {
            data.elements.forEach(el => {
                const type = el.tags.amenity.replace('_', ' ');
                const name = el.tags.name || `Unnamed ${type}`;
                
                const m = new maplibregl.Marker({ color: '#3b82f6', scale: 0.7 })
                    .setLngLat([el.lon, el.lat])
                    .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(`
                        <div class="p-2 min-w-[120px]">
                            <p class="text-[9px] font-black uppercase text-blue-600 tracking-wider">${type}</p>
                            <p class="text-[11px] font-bold text-geo-navy leading-tight mt-0.5">${name}</p>
                        </div>
                    `))
                    .addTo(map.value);
                
                landmarks.value.push(m);
            });
        } else {
            alert("No significant landmarks discovered within 2km.");
        }
    } catch (e) {
        console.error("OSM Overpass Error:", e);
        alert("Failed to reach OpenStreetMap discovery node. Try again later.");
    } finally {
        isScanningLandmarks.value = false;
    }
};

const captureSnapshot = () => {
    if (!map.value) return;
    // Force a re-render to ensure the buffer is filled for the snapshot
    map.value.triggerRepaint();
    
    // Use a small timeout to ensure the paint cycle completes
    requestAnimationFrame(() => {
        const link = document.createElement('a');
        link.download = `GSS_Spatial_Snapshot_${Date.now()}.jpg`;
        link.href = getSnapshotDataUrl();
        link.click();
    });
};

const getSnapshotDataUrl = () => {
    if (!map.value) return null;
    return map.value.getCanvas().toDataURL('image/jpeg', 0.85);
};

const fetchElevation = async (lat, lng) => {
    if (!lat || !lng) return;
    isLoadingElevation.value = true;
    try {
        // Migrating to Open-Meteo for higher reliability and faster response times
        const response = await fetch(`https://api.open-meteo.com/v1/elevation?latitude=${lat}&longitude=${lng}`);
        const data = await response.json();
        if (data && data.elevation && data.elevation.length > 0) {
            const val = Math.round(data.elevation[0]);
            currentElevation.value = val;
            emit('elevation', val);
        }
    } catch (e) {
        console.error("Telemetry Elevation Error:", e);
    } finally {
        isLoadingElevation.value = false;
    }
};

const setupHeatmap = () => {
    if (!map.value || !props.heatmapData) return;

    // FILTER DATA FOR SAFETY: Remove points with null/invalid coordinates which crash MapLibre workers
    const safeFeatures = (props.heatmapData.features || []).filter(f => {
        const coords = f?.geometry?.coordinates;
        return coords && 
               typeof coords[0] === 'number' && !isNaN(coords[0]) && 
               typeof coords[1] === 'number' && !isNaN(coords[1]);
    });
    const safeData = { ...props.heatmapData, features: safeFeatures };

    if (map.value.getSource('heatmap-source')) {
        map.value.getSource('heatmap-source').setData(safeData);
        return;
    }

    map.value.addSource('heatmap-source', {
        type: 'geojson',
        data: safeData
    });

    map.value.addLayer({
        id: 'survey-heatmap',
        type: 'heatmap',
        source: 'heatmap-source',
        maxzoom: 15,
        paint: {
            // Increase weight as zoom level increases
            'heatmap-weight': [
                'interpolate',
                ['linear'],
                ['coalesce', ['get', 'weight'], 0],
                0, 0,
                1, 1
            ],
            // Increase intensity as zoom level increases
            // Increase intensity as zoom level increases
            'heatmap-intensity': [
                'interpolate',
                ['linear'],
                ['zoom'],
                11, 1,
                15, 3
            ],
            // Color ramp for heatmap.
            'heatmap-color': [
                'interpolate',
                ['linear'],
                ['heatmap-density'],
                0, 'rgba(0, 255, 255, 0)',
                0.2, 'rgb(0, 255, 255)',
                0.4, 'rgb(0, 191, 255)',
                0.6, 'rgb(0, 0, 255)',
                0.8, 'rgb(255, 0, 255)',
                1, 'rgb(255, 0, 0)'
            ],
            // Adjust radius as zoom level increases
            // Adjust radius as zoom level increases
            'heatmap-radius': [
                'interpolate',
                ['linear'],
                ['zoom'],
                11, 20,
                15, 40
            ],
            // Transition from heatmap to circle layer by zoom level
            // Transition from heatmap to circle layer by zoom level
            'heatmap-opacity': [
                'interpolate',
                ['linear'],
                ['zoom'],
                14, 1,
                15, 0
            ]
        }
    });

    map.value.addLayer({
        id: 'survey-point',
        type: 'circle',
        source: 'heatmap-source',
        minzoom: 14,
        paint: {
            'circle-radius': [
                'interpolate',
                ['linear'],
                ['zoom'],
                14, 5,
                16, 8
            ],
            'circle-color': [
                'match',
                ['get', 'status'],
                'approved', '#0d9488',
                'pending', '#fbbf24',
                'rejected', '#ef4444',
                '#ccc'
            ],
            'circle-stroke-width': 1,
            'circle-stroke-color': '#fff',
            'circle-opacity': [
                'interpolate',
                ['linear'],
                ['zoom'],
                14, 0,
                15, 1
            ]
        }
    });
};

watch(() => props.heatmapData, () => {
    if (map.value && props.heatmapData) {
        setupHeatmap();
    }
}, { deep: true });

const handleAnnotation = (e) => {
    const { lng, lat } = e.lngLat;
    const note = prompt("Enter temporary session node label (Shift+Click used):");
    if (note) {
        const m = new maplibregl.Marker({ color: '#f59e0b' })
            .setLngLat([lng, lat])
            .setPopup(new maplibregl.Popup().setHTML(`<p class="text-[10px] font-bold p-1">${note}</p>`))
            .addTo(map.value);
        
        sessionMarkers.value.push({ marker: m, note, lng, lat });
    }
};

const reconstituteSessionMarkers = () => {
    sessionMarkers.value.forEach(item => {
        item.marker.addTo(map.value);
    });
};

watch(() => props.focusProject, (newId) => {
    if (!newId || !map.value) return;

    let geom = null;
    if (props.projects && props.projects.length > 0) {
        const project = props.projects.find(p => p.id == newId);
        if (project && project.boundary) geom = project.boundary;
    }

    if (geom) {
        flyToBoundary(geom);
    }
}, { immediate: true });

const clearSessionMarkers = () => {
    sessionMarkers.value.forEach(item => item.marker.remove());
    sessionMarkers.value = [];
};

// Update elevation and marker when pin moves or external sync occurs
watch(() => props.modelValue, (newVal) => {
    if (newVal && newVal.lat != null && newVal.lng != null) {
        fetchElevation(newVal.lat, newVal.lng);
        
        if (map.value && marker.value) {
            marker.value.setLngLat([newVal.lng, newVal.lat]);
        }
    }
}, { immediate: true, deep: true });

const enable3DBuildings = () => {
    if (!map.value) return;
    // Only attempt on vector styles (streets/topo)
    if (currentStyle.value === 'streets' || currentStyle.value === 'topo') {
        const style = map.value.getStyle();
        const layers = style.layers;
        
        // Dynamically find the vector source ID (OpenFreeMap usually uses 'openmaptiles' or similar)
        let sourceName = Object.keys(style.sources).find(s => style.sources[s].type === 'vector');
        
        if (!sourceName) return; // No vector source found, cannot enable 3D extrusions

        const labelLayerId = layers.find(l => l.type === 'symbol' && l.layout['text-field'])?.id;

        if (!map.value.getLayer('3d-buildings')) {
            map.value.addLayer({
                'id': '3d-buildings',
                'source': sourceName,
                'source-layer': 'building',
                'type': 'fill-extrusion',
                'minzoom': 15,
                'paint': {
                    'fill-extrusion-color': '#aaa',
                    // Use coalesce and to-number to prevent "Expected number, found null/string" errors
                    'fill-extrusion-height': ['coalesce', ['to-number', ['get', 'render_height']], ['to-number', ['get', 'height']], 0],
                    'fill-extrusion-base': ['coalesce', ['to-number', ['get', 'render_min_height']], ['to-number', ['get', 'min_height']], 0],
                    'fill-extrusion-opacity': 0.6
                }
            }, labelLayerId);
        }
    }
};

const findMyLocation = () => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition((position) => {
            const { latitude, longitude } = position.coords;
            if (map.value) {
                map.value.flyTo({
                    center: [longitude, latitude],
                    zoom: 18,
                    speed: 1.5,
                    curve: 1
                });
                if (!props.readOnly && marker.value) {
                    marker.value.setLngLat([longitude, latitude]);
                    updateLocation(latitude, longitude);
                }
            }
        }, (err) => {
            alert("Error obtaining GPS position. Please check browser permissions.");
        }, { enableHighAccuracy: true });
    }
};

const fitToGlobalContext = () => {
    if (!map.value || !map.value.loaded()) {
        setTimeout(fitToGlobalContext, 800);
        return;
    }
    
    if (props.projects && props.projects.length > 0) {
        const features = props.projects.map(p => {
            let geom = p.boundary;
            if (typeof geom === 'string') {
                try { geom = JSON.parse(geom); } catch (e) { return null; }
            }
            return { type: 'Feature', geometry: geom };
        }).filter(f => f && f.geometry);
        
        if (features.length > 0) {
            const bbox = turf.bbox(turf.featureCollection(features));
            map.value.fitBounds(bbox, { padding: 80, duration: 2000 });
        }
    } else if (props.boundary) {
        let geoJson = props.boundary;
        if (typeof geoJson === 'string') {
            try { geoJson = JSON.parse(geoJson); } catch (e) { return; }
        }
        const bbox = turf.bbox(geoJson);
        map.value.fitBounds(bbox, { padding: 60, duration: 2000 });
    }
};

const setupMarkerAndListeners = () => {
    if (!map.value) return;
    
    // Only hide default marker if we are in EXPLICIT dashboard view (no modelValue)
    if (props.projects && props.projects.length > 0 && !props.modelValue?.lat) return;

    // Add Marker
    marker.value = new maplibregl.Marker({
        draggable: !props.readOnly,
        color: '#0d9488'
    })
        .setLngLat([props.modelValue.lng, props.modelValue.lat])
        .addTo(map.value);

    // Marker interference protection
    const markerEl = marker.value.getElement();
    markerEl.style.pointerEvents = measurementMode.value ? 'none' : 'auto';

    marker.value.on('dragend', () => {
        const lngLat = marker.value.getLngLat();
        updateLocation(lngLat.lat, lngLat.lng);
    });
};

const renderBoundary = () => {
    if (!map.value) return;

    // 1. Handle GLOBAL VIEW (Multiple Projects/Dashboard)
    if (props.projects && props.projects.length > 0) {
        const sourceId = 'global-projects';
        
        const features = props.projects.map(p => {
            let geom = p.boundary;
            if (typeof geom === 'string') {
                try { geom = JSON.parse(geom); } catch (e) { return null; }
            }
            if (!geom) return null;
            return {
                type: 'Feature',
                properties: { id: p.id, name: p.name || 'Unnamed Sector' },
                geometry: geom
            };
        }).filter(f => f && f.geometry);

        if (features.length === 0) {
            console.warn('MapViewer: No valid project geometries to render in global view.');
            return;
        }
        const collection = turf.featureCollection(features);

        if (map.value.getSource(sourceId)) {
            map.value.getSource(sourceId).setData(collection);
        } else {
            map.value.addSource(sourceId, { type: 'geojson', data: collection });
            
            map.value.addLayer({
                id: 'global-projects-fill',
                type: 'fill',
                source: sourceId,
                paint: { 'fill-color': '#0d9488', 'fill-opacity': 0.15 }
            });

            map.value.addLayer({
                id: 'global-projects-outline',
                type: 'line',
                source: sourceId,
                paint: { 'line-color': '#0d9488', 'line-width': 2, 'line-dasharray': [2, 1] }
            });

            map.value.addLayer({
                id: 'global-projects-labels',
                type: 'symbol',
                source: sourceId,
                layout: {
                    'text-field': ['get', 'name'],
                    'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
                    'text-size': 11,
                    'text-variable-anchor': ['top', 'bottom', 'left', 'right'],
                    'text-radial-offset': 0.5,
                    'text-justify': 'auto'
                },
                paint: { 'text-color': '#0a192f', 'text-halo-color': '#fff', 'text-halo-width': 2 }
            });

            // RIGHT-CLICK POPUP FOR CORPORATE DETAILS
            map.value.on('contextmenu', 'global-projects-fill', async (e) => {
                const feature = e.features[0];
                const project = props.projects.find(p => p.id === feature.properties.id);
                if (!project) return;

                const popup = new maplibregl.Popup({ className: 'corporate-popup', offset: 10, maxWidth: '300px' })
                    .setLngLat(e.lngLat)
                    .setHTML('<div class="p-4 text-xs font-bold text-geo-navy animate-pulse">Syncing Site Intelligence...</div>')
                    .addTo(map.value);

                let address = "Scanning Regional Operations Zone...";
                try {
                    const resp = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.lngLat.lat}&lon=${e.lngLat.lng}`);
                    const geoData = await resp.json();
                    address = geoData.display_name || "Registered Operational Sector";
                } catch(err) { 
                    address = "Cached Offline Location"; 
                }

                popup.setHTML(`
                    <div class="px-5 py-4 border-t-4 border-geo-teal rounded-xl shadow-2xl bg-white border border-gray-100">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="text-[9px] font-black text-geo-teal uppercase tracking-widest mb-1">Strategic Asset</h4>
                                <p class="text-base font-black text-geo-navy leading-none uppercase tracking-tighter">${project.name}</p>
                            </div>
                            <span class="bg-slate-50 text-geo-navy border border-slate-100 px-2 py-0.5 rounded text-[8px] font-black italic">GSS-${String(project.id).padStart(3, '0')}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                             <div>
                                <p class="text-[8px] uppercase font-bold text-slate-400 tracking-tighter">Site Activity</p>
                                <p class="text-sm font-black text-geo-navy">${project.survey_count || 0} <span class="text-[10px] font-normal opacity-40">logs</span></p>
                            </div>
                             <div class="text-right">
                                <p class="text-[8px] uppercase font-bold text-slate-400 tracking-tighter">Last Personnel</p>
                                <p class="text-[11px] font-black text-geo-teal truncate">${project.latest_submitter || 'None'}</p>
                                <p class="text-[9px] text-gray-400 italic">${project.latest_date || 'Awaiting Sync'}</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="text-[8px] uppercase font-bold text-slate-400 tracking-tighter mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-align-left text-geo-teal"></i>
                                Operational Scope
                            </p>
                            <p class="text-[9px] font-bold text-geo-slate leading-relaxed bg-white border border-gray-50 p-2.5 rounded-lg shadow-inner max-h-16 overflow-y-auto">${(project.description || 'No description provided').replace(/\n/g, '<br>')}</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                             <div>
                                <p class="text-[8px] uppercase font-bold text-slate-400 tracking-tighter">Project Deadline</p>
                                <p class="text-[11px] font-black text-red-500">${project.deadline_date ? project.deadline_date.split('-').reverse().join('-') : 'Not specified'}</p>
                            </div>
                             <div class="text-right">
                                <p class="text-[8px] uppercase font-bold text-slate-400 tracking-tighter">Budget / Cost</p>
                                <p class="text-[11px] font-black text-emerald-600">${project.cost ? 'RM ' + parseFloat(project.cost).toFixed(2) : 'Not specified'}</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="text-[8px] uppercase font-bold text-slate-400 tracking-tighter mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-satellite-dish text-geo-teal animate-pulse"></i>
                                Precise Geospatial Address
                            </p>
                            <p class="text-[9px] font-bold text-geo-slate leading-relaxed bg-white border border-gray-50 p-2.5 rounded-lg shadow-inner max-h-20 overflow-y-auto">${address}</p>
                        </div>
                        
                        <div class="flex justify-between items-center text-[8px] font-black text-slate-300 uppercase tracking-widest pt-2 border-t border-gray-50">
                            <span>Sector 0${project.id}</span>
                            <span class="text-geo-teal">Active Node</span>
                        </div>
                    </div>
                `);
            });

            // CURSOR DISCOVERY
            map.value.on('mouseenter', 'global-projects-fill', () => {
                map.value.getCanvas().style.cursor = 'help';
            });
            map.value.on('mouseleave', 'global-projects-fill', () => {
                map.value.getCanvas().style.cursor = '';
            });
        }
        return;
    }

    // 2. Handle INDIVIDUAL VIEW (Single Project/Survey)
    if (!props.boundary) return;

    let geoJson = props.boundary;
    if (typeof geoJson === 'string') {
        try { geoJson = JSON.parse(geoJson); } catch (e) { return; }
    }

    // Prepare the Normalized Polygon Data
    let coords = [];
    try {
        if (geoJson.geometry && geoJson.geometry.coordinates) {
            coords = geoJson.geometry.type === 'Polygon' ? geoJson.geometry.coordinates[0] : geoJson.geometry.coordinates;
        } else if (geoJson.coordinates) {
            coords = geoJson.type === 'Polygon' ? geoJson.coordinates[0] : geoJson.coordinates;
        } else if (Array.isArray(geoJson)) {
            coords = geoJson;
        }

        if (coords.length >= 3) {
            // Ensure Closure
            const first = coords[0];
            const last = coords[coords.length - 1];
            if (first[0] !== last[0] || first[1] !== last[1]) {
                coords.push([...first]);
            }
        }
    } catch (e) {
        return;
    }

    if (coords.length < 3) return;

    const sourceId = 'project-boundary';
    const polygonFeature = turf.polygon([coords]);

    if (map.value.getSource(sourceId)) {
        map.value.getSource(sourceId).setData(polygonFeature);
    } else {
        map.value.addSource(sourceId, {
            type: 'geojson',
            data: polygonFeature
        });

        // 1. Background Fill Layer
        map.value.addLayer({
            id: 'boundary-fill',
            type: 'fill',
            source: sourceId,
            paint: {
                'fill-color': '#0d9488',
                'fill-opacity': 0.3
            }
        });

        // 2. Connector Line Layer
        map.value.addLayer({
            id: 'boundary-line',
            type: 'line',
            source: sourceId,
            paint: {
                'line-color': '#0d9488',
                'line-width': 2
            }
        });

        // 3. Vertex Circle Layer (The specific points)
        map.value.addLayer({
            id: 'boundary-vertices',
            type: 'circle',
            source: sourceId,
            paint: {
                'circle-radius': 5,
                'circle-color': '#0d9488',
                'circle-stroke-width': 2,
                'circle-stroke-color': '#ffffff'
            }
        });
    }
};

const toggleStyle = () => {
    if (currentStyle.value === 'streets') {
        currentStyle.value = 'topo';
    } else if (currentStyle.value === 'topo') {
        currentStyle.value = 'satellite';
    } else {
        currentStyle.value = 'streets';
    }
    
    if (map.value) {
        map.value.setStyle(styles[currentStyle.value]);
    }
};

const is3D = ref(false);

const toggle3D = () => {
    is3D.value = !is3D.value;
    if (map.value) {
        map.value.easeTo({
            pitch: is3D.value ? 60 : 0,
            bearing: is3D.value ? -20 : 0,
            duration: 1000
        });
    }
};

const isValidLocation = computed(() => {
    if (!props.boundary || !props.modelValue) return true;
    try {
        const pt = turf.point([props.modelValue.lng, props.modelValue.lat]);
        const poly = props.boundary.type === 'Feature' ? props.boundary.geometry : props.boundary;
        return turf.booleanPointInPolygon(pt, poly);
    } catch (e) {
        return true;
    }
});

onMounted(async () => {
    await nextTick();
    initMap();
});

onBeforeUnmount(() => {
    if (map.value) map.value.remove();
});

watch(() => props.boundary, (newVal) => {
    if (newVal) {
        renderBoundary();
        flyToBoundary(newVal);
    }
}, { deep: true, immediate: true });

watch(() => props.projects, () => {
    renderBoundary();
}, { deep: true });

function updateLocation(lat, lng) {
    const numericLat = Number(lat);
    const numericLng = Number(lng);
    emit('update:modelValue', { lat: numericLat, lng: numericLng });
    if (props.boundary) {
        try {
            const pt = turf.point([lng, lat]);
            const poly = props.boundary.type === 'Feature' ? props.boundary.geometry : props.boundary;
            emit('validated', turf.booleanPointInPolygon(pt, poly));
        } catch (e) {}
    }
}

watch(isValidLocation, (newVal) => {
    if (map.value && map.value.getLayer('boundary-fill')) {
        const color = newVal ? '#0d9488' : '#ef4444';
        map.value.setPaintProperty('boundary-fill', 'fill-color', color);
        map.value.setPaintProperty('boundary-fill', 'fill-opacity', newVal ? 0.1 : 0.4);
        map.value.setPaintProperty('boundary-line', 'line-color', color);
    }
}, { immediate: true });

// Expose an imperative fly-to method for parent components
const flyToBoundary = (geom) => {
    if (!geom) return;
    
    if (!map.value || !map.value.loaded()) {
        setTimeout(() => flyToBoundary(geom), 800);
        return;
    }

    if (typeof geom === 'string') {
        try { 
            const parsed = JSON.parse(geom); 
            geom = parsed;
        } catch (e) { return; }
    }

    const feature = (geom.type === 'Feature' || geom.type === 'FeatureCollection')
        ? geom
        : { type: 'Feature', geometry: geom, properties: {} };

    try {
        const bbox = turf.bbox(feature);
        if (bbox.some(coord => isNaN(coord) || coord === null)) {
            console.warn('MapViewer: Aborting flyToBoundary - invalid coordinates detected:', bbox);
            return;
        }
        
        console.log('MapViewer: Executing imperative fly-to boundary:', bbox);
        map.value.fitBounds([[bbox[0], bbox[1]], [bbox[2], bbox[3]]], {
            padding: 80,
            duration: 1800,
            maxZoom: 16
        });
    } catch (e) {
        console.error('MapViewer flyToBoundary execution failed:', e);
    }
};

defineExpose({ flyToBoundary, getSnapshotDataUrl, fitToGlobalContext });

const measurementMode = ref(false);
const measurementPoints = ref([]);
const measurementDistance = ref(0);

const toggleMeasurementMode = () => {
    measurementMode.value = !measurementMode.value;
    measurementPoints.value = [];
    measurementDistance.value = 0;
    
    // Toggle marker interactivity to prevent interference
    if (marker.value) {
        marker.value.getElement().style.pointerEvents = measurementMode.value ? 'none' : 'auto';
    }

    if (map.value && map.value.getSource('measurement-line')) {
        map.value.getSource('measurement-line').setData({ type: 'FeatureCollection', features: [] });
        map.value.getSource('measurement-points').setData({ type: 'FeatureCollection', features: [] });
    }
};

const handleMapClick = (e) => {
    if (measurementMode.value) {
        const { lng, lat } = e.lngLat;
        measurementPoints.value.push([lng, lat]);
        
        if (measurementPoints.value.length > 1) {
            const line = turf.lineString(measurementPoints.value);
            measurementDistance.value = turf.length(line, { units: 'kilometers' }) * 1000; // in meters
        }
        
        updateMeasurementLayers();
    } else if (!props.readOnly) {
        const { lng, lat } = e.lngLat;
        marker.value.setLngLat([lng, lat]);
        updateLocation(lat, lng);
    }
};

const updateMeasurementLayers = () => {
    if (!map.value || measurementPoints.value.length < 2) return;
    
    const lineData = {
        type: 'Feature',
        geometry: {
            type: 'LineString',
            coordinates: measurementPoints.value
        }
    };
    
    const pointData = {
        type: 'FeatureCollection',
        features: measurementPoints.value.map(p => ({
            type: 'Feature',
            geometry: { type: 'Point', coordinates: p }
        }))
    };

    if (!map.value.getSource('measurement-line')) {
        map.value.addSource('measurement-line', { type: 'geojson', data: lineData });
        map.value.addLayer({
            id: 'measurement-line-layer',
            type: 'line',
            source: 'measurement-line',
            paint: { 'line-color': '#ff0000', 'line-width': 4 },
            layout: { 'line-cap': 'round', 'line-join': 'round' }
        });
        
        map.value.addSource('measurement-points', { type: 'geojson', data: pointData });
        map.value.addLayer({
            id: 'measurement-points-layer',
            type: 'circle',
            source: 'measurement-points',
            paint: { 'circle-radius': 6, 'circle-color': '#ff0000', 'circle-stroke-width': 2, 'circle-stroke-color': '#fff' }
        });
    } else {
        map.value.getSource('measurement-line').setData(lineData);
        map.value.getSource('measurement-points').setData(pointData);
    }
};

function handleManualSync() {
    if (map.value && marker.value) {
        const target = [props.modelValue.lng, props.modelValue.lat];
        map.value.flyTo({ center: target, zoom: 16 });
        marker.value.setLngLat(target);
    }
}
</script>

<template>
    <div class="relative w-full h-full bg-slate-100 rounded-3xl overflow-hidden border-4 border-white shadow-2xl">
        <!-- Error Overlay -->
        <div v-if="error" class="absolute inset-0 z-[40] bg-white/90 flex flex-col items-center justify-center p-6 text-center">
            <i class="fa-solid fa-triangle-exclamation text-red-500 text-4xl mb-4"></i>
            <p class="text-geo-navy font-bold mb-4">{{ error }}</p>
            <button type="button" @click.stop.prevent="initMap" class="bg-geo-navy text-white px-6 py-2 rounded-xl text-xs font-bold uppercase transition hover:scale-105 active:scale-95">RETRY CONNECTION</button>
        </div>
        
        <div ref="mapContainer" class="absolute inset-0 z-[10] w-full h-full"></div>
        
        <!-- Search Tool -->
        <div class="absolute top-4 left-1/2 -translate-x-1/2 z-[40] w-[280px]">
            <div class="relative group">
                <input 
                    v-model="searchQuery" 
                    @keyup.enter="handleSearch"
                    type="text" 
                    placeholder="Search Site Location..." 
                    class="w-full bg-white/90 backdrop-blur-md border border-white/20 rounded-2xl px-5 py-3 text-xs font-bold text-geo-navy shadow-2xl focus:ring-2 focus:ring-geo-blue outline-none transition-all placeholder:text-geo-navy/40"
                >
                <button @click="handleSearch" class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-geo-navy hover:text-geo-blue transition-colors">
                    <i v-if="!isSearching" class="fa-solid fa-magnifying-glass"></i>
                    <i v-else class="fa-solid fa-circle-notch fa-spin"></i>
                </button>
            </div>
        </div>

        <!-- Controls Overlay -->
        <div class="absolute top-4 right-4 z-[40] flex flex-col gap-2">
            <button type="button" @click.stop.prevent="handleManualSync" 
                class="p-3 bg-white shadow-xl rounded-2xl text-geo-navy hover:text-geo-teal hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="Sync & Accuracy Check">
                <i class="fa-solid fa-location-crosshairs fa-lg group-hover:rotate-90 transition-transform duration-500"></i>
            </button>
            <button type="button" @click.stop.prevent="findMyLocation" 
                class="p-3 bg-white shadow-xl rounded-2xl text-blue-600 hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="Find My GPS Location">
                <i class="fa-solid fa-satellite fa-lg"></i>
            </button>
            <button type="button" @click.stop.prevent="toggleMeasurementMode" 
                :class="measurementMode ? 'bg-red-500 text-white' : 'bg-white text-geo-navy'"
                class="p-3 shadow-xl rounded-2xl hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="Technical Measurement Tool (Turf.js)">
                <i class="fa-solid fa-ruler-combined fa-lg"></i>
            </button>
            <button type="button" @click.stop.prevent="captureSnapshot" 
                class="p-3 bg-white shadow-xl rounded-2xl text-geo-teal hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="Capture High-Res Site Snapshot">
                <i class="fa-solid fa-camera-retro fa-lg"></i>
            </button>
            <button type="button" @click.stop.prevent="toggleStyle" 
                class="p-3 bg-white shadow-xl rounded-2xl text-geo-navy hover:text-geo-blue hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="Switch Visual Layer">
                <i class="fa-solid fa-layer-group fa-lg"></i>
            </button>
            <button type="button" @click.stop.prevent="toggle3D" 
                :class="is3D ? 'bg-geo-blue text-white' : 'bg-white text-geo-navy'"
                class="p-3 shadow-xl rounded-2xl hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="3D Perspective Mode">
                <i class="fa-solid fa-cube fa-lg"></i>
            </button>
            <button type="button" @click.stop.prevent="scanLandmarks" 
                :class="isScanningLandmarks ? 'bg-blue-600 text-white animate-pulse' : 'bg-white text-blue-600'"
                class="p-3 shadow-xl rounded-2xl hover:scale-110 active:scale-95 transition-all border border-gray-100 group"
                title="Discover Local Landmarks (OSM)">
                <i class="fa-solid fa-hotel fa-lg"></i>
            </button>
        </div>

        <!-- Scanning Status Indicator -->
        <div v-if="isScanningLandmarks" class="absolute top-20 left-1/2 -translate-x-1/2 z-[41] bg-blue-600 text-white px-4 py-2 rounded-full shadow-2xl flex items-center gap-3 border border-white/20">
            <i class="fa-solid fa-radar fa-spin"></i>
            <span class="text-[10px] font-black uppercase tracking-widest">Scanning Site Intelligence...</span>
        </div>

        <!-- Landmarks Summary (Top Right) -->
        <div v-if="landmarks.length > 0" class="absolute top-4 right-16 z-[40] bg-white/90 backdrop-blur-md px-3 py-1.5 rounded-xl shadow-xl border border-blue-100 flex items-center space-x-2">
            <i class="fa-solid fa-city text-blue-600 text-[10px]"></i>
            <span class="text-[10px] font-extra-bold text-geo-navy uppercase tracking-tighter">
                {{ landmarks.length }} Landmarks Identified
            </span>
            <button @click="landmarks.forEach(m => m.remove()); landmarks = []" class="ml-2 hover:text-red-500 transition-colors">
                <i class="fa-solid fa-circle-xmark"></i>
            </button>
        </div>

        <!-- Opacity Controller (Now in Bottom Right to avoid Scale Bar overlap) -->
        <div v-if="props.boundary" class="absolute right-24 bottom-6 z-[40] bg-white/90 backdrop-blur-md p-2 rounded-2xl shadow-xl border border-gray-100 flex flex-row gap-3 items-center">
            <i class="fa-solid fa-eye-low-beam text-geo-navy/40 text-[10px]"></i>
            <input 
                type="range" 
                v-model="boundaryOpacity" 
                min="0.0" max="0.8" step="0.1" 
                class="w-24 h-1.5 appearance-none bg-geo-cyan/20 accent-geo-teal rounded-lg cursor-pointer"
            >
        </div>

        <!-- Mouse Explorer (Balanced between zoom controls and search bar) -->
        <div class="absolute top-4 left-[64px] z-[40] bg-geo-navy/20 backdrop-blur-[2px] text-geo-navy/50 px-3 py-1 rounded-full text-[8px] font-mono pointer-events-none border border-geo-navy/10">
            EXPLORE: {{ mousePos.lat.toFixed(5) }}, {{ mousePos.lng.toFixed(5) }}
        </div>

        <!-- Measurement Readout -->
        <div v-if="measurementMode" class="absolute top-4 left-[420px] z-[40] bg-white/90 backdrop-blur-sm border border-red-100 rounded-2xl px-4 py-2 shadow-2xl flex items-center gap-3">
            <div class="p-2 bg-red-100 text-red-600 rounded-xl">
                <i class="fa-solid fa-vector-square"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase font-black text-red-600 leading-none">Measurement Activity</p>
                <p class="text-lg font-black text-geo-navy tabular-nums leading-tight">
                    {{ measurementDistance.toFixed(2) }} <span class="text-xs">m</span>
                </p>
            </div>
        </div>

        <!-- Combined Technical Dashboard (Bottom Middle) -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-[40] flex flex-col items-center">
            <div :class="isValidLocation ? 'bg-geo-navy/90 border-white/20' : 'bg-red-600/95 border-white/40'"
                class="backdrop-blur-md text-white px-6 py-2.5 rounded-2xl text-[11px] font-bold shadow-2xl flex items-center space-x-4 border transition-all duration-500">
                
                <div class="flex items-center space-x-2">
                    <div :class="isValidLocation ? 'bg-geo-teal shadow-[0_0_10px_#0d9488]' : 'bg-white shadow-[0_0_10px_#fff]'"
                        class="w-2.5 h-2.5 rounded-full animate-pulse "></div>
                    <span class="font-mono tracking-widest uppercase opacity-80">{{ isValidLocation ? 'Coordinate:' : 'OUT-OF-BOUNDS:' }}</span>
                    <span class="font-mono text-xs tabular-nums">{{ (modelValue?.lat ?? 3.1390).toFixed(6) }}°, {{ (modelValue?.lng ?? 101.6869).toFixed(6) }}°</span>
                </div>

                <div class="w-px h-4 bg-white/20"></div>

                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-mountain-sun text-geo-teal text-[10px]"></i>
                    <span class="font-mono text-xs tabular-nums">
                        {{ isLoadingElevation ? 'SYNCING...' : (currentElevation || '0') + 'm ASL' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Session Annotation Controls -->
        <div v-if="sessionMarkers.length > 0" class="absolute bottom-20 right-6 z-[40]">
            <button @click="clearSessionMarkers" class="bg-red-500 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase shadow-xl hover:scale-105 active:scale-95 transition-all">
                Clear {{ sessionMarkers.length }} Session Notes
            </button>
        </div>

        <div class="absolute bottom-6 right-6 z-[40] pointer-events-none">
            <p class="text-[9px] font-black text-geo-navy uppercase tracking-[0.2em] opacity-30 drop-shadow-sm">GSS MapEngine</p>
        </div>
    </div>
</template>

<style scoped>
.maplibregl-ctrl-group {
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1) !important;
}
</style>
