import { ref } from 'vue';

const DB_NAME = 'GSS_Offline_Core';
const STORE_NAME = 'pending_surveys';
const DB_VERSION = 1;

export function useOfflineStore() {
    const isOnline = ref(navigator.onLine);
    const pendingCount = ref(0);

    window.addEventListener('online', () => isOnline.value = true);
    window.addEventListener('offline', () => isOnline.value = false);

    const openDB = () => {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            request.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    };

    const saveSurveyOffline = async (surveyData) => {
        const db = await openDB();
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        
        // We need to convert File objects to Blobs/Base64 because File objects 
        // sometimes don't serialize well in indexedDB across sessions
        const processedImages = await Promise.all(surveyData.images.map(async (file) => {
            return {
                blob: file,
                name: file.name,
                type: file.type
            };
        }));

        const record = {
            ...surveyData,
            images: processedImages,
            timestamp: new Date().toISOString()
        };

        return new Promise((resolve, reject) => {
            const request = store.add(record);
            request.onsuccess = () => {
                updatePendingCount();
                resolve(true);
            };
            request.onerror = () => reject(request.error);
        });
    };

    const getPendingSurveys = async () => {
        const db = await openDB();
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        return new Promise((resolve) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
        });
    };

    const removeSurveyOffline = async (id) => {
        const db = await openDB();
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        return new Promise((resolve) => {
            const request = store.delete(id);
            request.onsuccess = () => {
                updatePendingCount();
                resolve(true);
            };
        });
    };

    const updatePendingCount = async () => {
        const surveys = await getPendingSurveys();
        pendingCount.value = surveys.length;
    };

    // Initial count
    updatePendingCount();

    return {
        isOnline,
        pendingCount,
        saveSurveyOffline,
        getPendingSurveys,
        removeSurveyOffline,
        updatePendingCount
    };
}
