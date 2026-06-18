<script setup>
import { ref, nextTick, watch } from 'vue';

const isOpen = ref(false);
const inputMessage = ref('');
const isTyping = ref(false);
const messagesContainer = ref(null);

const messages = ref([
    {
        id: Date.now(),
        sender: 'ai',
        text: 'GeoSurvey AI initialized. How may I assist you today?',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
]);

// Rule-Based Knowledge Graph
const knowledgeBase = [
    {
        keywords: ['sync', 'offline', 'internet', 'line', 'connection', 'vault', 'stage'],
        response: 'GeoSurvey utilizes an IndexedDB Local Vault. If you lose network connection, proceed capturing your survey as usual. The system will store the payload safely on your device. Once reconencted, the system will detect the network and auto-sync your staged logs to the core server.'
    },
    {
        keywords: ['project', 'zone', 'polygon', 'sector'],
        response: 'Project Zones are strict spatial boundaries (Polygons) defined by Administrators using the Digital Twin engine. Field staff must be physically present within these exact geofences to submit valid operations.'
    },
    {
        keywords: ['gps', 'location', 'mismatch', 'reject', 'exif', 'metadata', 'fake'],
        response: 'A "Mismatch" integrity flag occurs if your image EXIF metadata coordinates fall outside the assigned Project Zone polygon. Please ensure your smartphone GPS/Location Services remain ON. Do not transfer images via WhatsApp before uploading, as it permanently strips secure spatial data.'
    },
    {
        keywords: ['hod', 'approval', 'review', 'pending'],
        response: 'Heads of Department (HOD) are granted Compliance capabilities. They access the Decision Queue to evaluate pending field evidence against automated spatial precision scores, executing final Approval or Rejection mandates.'
    },
    {
        keywords: ['report', 'export', 'pdf', 'chart'],
        response: 'Administrators possess Corporate Reporting clearance. The system can synthesize high-level spatial data into automated, dynamic domPDF layouts complete with absolute operational volume metrics.'
    },
    {
        keywords: ['password', 'profile', 'security', 'change'],
        response: 'You can update your operational clearance via the Security Profile screen (Top Right Menu). Note: The system enforces strict credential complexity (8+ chars, 1 number, 1 symbol) to maintain corporate compliance.'
    },
    {
        keywords: ['most active zone', 'active zone', 'highest zone', 'dashboard insight', 'grid'],
        response: 'The most active operational zones are automatically calculated and displayed in ascending order on your main Dashboard metrics grid. This utilizes real-time spatial aggregation to reflect live field activity.'
    },
    {
        keywords: ['most submission', 'monthly', 'volume by month'],
        response: 'Monthly submission volumes are tracked autonomously. You can view the full Breakdown via the Dashboard infographics or generate a detailed Corporate Report for specific strategic insights into monthly peak periods.'
    },
    {
        keywords: ['list of active project', 'where are the project', 'all zone'],
        response: 'To view all active Project Zones, click "Project Zones" in the top navigation. Admins see the full registry, while Staff can view the global boundaries distributed via their personal MapViewer portal.'
    },
    {
        keywords: ['how to create', 'create project', 'create zone', 'new project', 'draw polygon'],
        response: 'ADMIN PROTOCOL: 1. Navigate to Project Zones. 2. Click "+ New Project Zone". 3. Provide a designation alias. 4. Use the MapLibre drawing tools (Top Right of map) to trace the boundary polygon. 5. Double-click to seal the polygon and hit Save.'
    },
    {
        keywords: ['dispatch', 'submit survey', 'how to survey', 'snap', 'capture photo'],
        response: 'STAFF PROTOCOL: 1. Navigate to Field Surveys. 2. Select "+ Dispatch Telemetry". 3. Ensure your device GPS is Active. 4. Physically stand inside the target Project Zone. 5. Snap an image directly via the browser interface and Submit. Do NOT transfer images via WhatsApp first.'
    },
    {
        keywords: ['who am i', 'my role', 'what can i do'],
        response: 'Your access is determined by your Role. Admins create zones and users. HODs approve or reject evidence. Staff collect geospatial telemetry in the field. Your current clearance level is displayed in the top right menu dropdown.'
    }
];

const fallbackResponses = [
    'I did not quite catch that. Could you specificy if it is regarding Offline Syncing, GPS Integrity, or Project Zones?',
    'My databanks are optimized for GeoSurvey operations. Try asking me about EXIF Metadata or HOD Approvals.',
    'I am a localized rule-based assistant. Please rephrase your query using core GeoSurvey terminology.'
];

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const findResponse = (query) => {
    const normalizedQuery = query.toLowerCase();
    
    // Scan Knowledge Base for keyword matches
    for (const rule of knowledgeBase) {
        if (rule.keywords.some(keyword => normalizedQuery.includes(keyword))) {
            return rule.response;
        }
    }
    
    // Return random fallback if no match
    return fallbackResponses[Math.floor(Math.random() * fallbackResponses.length)];
};

const sendMessage = async () => {
    if (!inputMessage.value.trim()) return;

    const userText = inputMessage.value.trim();
    inputMessage.value = '';

    // 1. Push User Message
    messages.value.push({
        id: Date.now(),
        sender: 'user',
        text: userText,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    });
    
    scrollToBottom();

    // 2. Simulate AI Processing Delay (Ultra Fast: 400ms - 800ms)
    isTyping.value = true;
    scrollToBottom();
    
    await new Promise(resolve => setTimeout(resolve, Math.random() * 400 + 400));
    
    const aiResponseText = findResponse(userText);
    isTyping.value = false;

    // 3. Push AI Response
    messages.value.push({
        id: Date.now(),
        sender: 'ai',
        text: aiResponseText,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    });
    
    scrollToBottom();
};

watch(isOpen, (newVal) => {
    if (newVal) {
        scrollToBottom();
    }
});
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[1000] flex flex-col items-end">
        <!-- Chat Interface -->
        <Transition
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="opacity-0 translate-y-10 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-10 scale-95"
        >
            <div v-show="isOpen" class="mb-4 w-[350px] sm:w-[400px] h-[550px] max-h-[80vh] flex flex-col bg-white dark:bg-geo-navy rounded-3xl shadow-[0_15px_60px_-15px_rgba(0,0,0,0.5)] border border-gray-100 dark:border-white/10 overflow-hidden">
                <!-- Header -->
                <div class="px-5 py-4 bg-gradient-to-r from-geo-navy to-geo-blue dark:from-slate-900 dark:to-slate-800 flex items-center justify-between border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20">
                                <i class="fa-solid fa-microchip text-white text-lg"></i>
                            </div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-geo-teal border-2 border-geo-navy rounded-full"></div>
                        </div>
                        <div>
                            <h3 class="text-white font-black leading-tight tracking-wide">AIGSS Engine</h3>
                            <p class="text-white/60 text-[10px] uppercase font-bold tracking-widest mt-0.5">Operative Intelligence Matrix</p>
                        </div>
                    </div>
                    <button @click="isOpen = false" class="w-8 h-8 rounded-full bg-white/5 hover:bg-red-500/80 hover:text-white flex items-center justify-center text-white/60 transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Chat Canvas -->
                <div ref="messagesContainer" class="flex-1 p-5 overflow-y-auto bg-gray-50/50 dark:bg-slate-900/50 space-y-4">
                    <div v-for="msg in messages" :key="msg.id" :class="msg.sender === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        
                        <!-- AI Message -->
                        <div v-if="msg.sender === 'ai'" class="flex gap-2 max-w-[85%]">
                            <div class="w-6 h-6 rounded-full bg-geo-blue/10 dark:bg-geo-teal/20 flex-shrink-0 flex items-center justify-center mt-1 border border-geo-blue/20 dark:border-geo-teal/30">
                                <i class="fa-solid fa-robot text-[10px] text-geo-blue dark:text-geo-teal"></i>
                            </div>
                            <div>
                                <div class="bg-white dark:bg-slate-800 p-3.5 rounded-2xl rounded-tl-sm shadow-sm border border-gray-100 dark:border-white/5 text-sm text-geo-slate dark:text-gray-300 leading-relaxed font-medium">
                                    {{ msg.text }}
                                </div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest block mt-1 ml-1">{{ msg.timestamp }}</span>
                            </div>
                        </div>

                        <!-- User Message -->
                        <div v-else class="flex max-w-[85%]">
                            <div class="flex-1">
                                <div class="bg-geo-blue dark:bg-geo-teal p-3.5 rounded-2xl rounded-tr-sm shadow-sm text-sm text-white dark:text-geo-navy leading-relaxed font-bold">
                                    {{ msg.text }}
                                </div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest block mt-1 text-right mr-1">{{ msg.timestamp }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="flex justify-start">
                        <div class="flex gap-2 max-w-[85%]">
                            <div class="w-6 h-6 rounded-full bg-geo-blue/10 dark:bg-geo-teal/20 flex-shrink-0 flex items-center justify-center mt-1 border border-geo-blue/20 dark:border-geo-teal/30">
                                <i class="fa-solid fa-robot text-[10px] text-geo-blue dark:text-geo-teal"></i>
                            </div>
                            <div class="bg-white dark:bg-slate-800 p-3.5 rounded-2xl rounded-tl-sm shadow-sm border border-gray-100 dark:border-white/5 flex gap-1.5 items-center">
                                <div class="w-1.5 h-1.5 bg-gray-300 dark:bg-gray-500 rounded-full animate-bounce"></div>
                                <div class="w-1.5 h-1.5 bg-gray-300 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                                <div class="w-1.5 h-1.5 bg-gray-300 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-4 bg-white dark:bg-geo-navy border-t border-gray-100 dark:border-white/10">
                    <form @submit.prevent="sendMessage" class="relative">
                        <input 
                            v-model="inputMessage"
                            type="text" 
                            placeholder="Interrogate data core..." 
                            class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-white/10 rounded-full pl-5 pr-14 py-3 text-sm text-geo-navy dark:text-white focus:ring-2 focus:ring-geo-teal focus:border-transparent transition-all placeholder-gray-400 dark:placeholder-gray-500"
                        />
                        <button 
                            type="submit" 
                            :disabled="!inputMessage.trim() || isTyping"
                            class="absolute right-1.5 top-1.5 bottom-1.5 w-10 bg-geo-teal hover:bg-teal-500 text-white rounded-full flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-md"
                        >
                            <i class="fa-solid fa-paper-plane text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Floating Trigger Button -->
        <button 
            v-show="!isOpen"
            @click="isOpen = true"
            class="group w-16 h-16 bg-gradient-to-tr from-geo-navy to-geo-blue dark:from-geo-teal dark:to-emerald-400 rounded-full shadow-[0_10px_30px_rgba(0,0,0,0.3)] flex items-center justify-center hover:scale-105 hover:shadow-[0_10px_40px_rgba(0,0,0,0.4)] transition-all duration-300 relative z-[1001]"
        >
            <!-- Particle Ring Animation -->
            <div class="absolute inset-0 rounded-full border-2 border-white/20 scale-110 opacity-0 group-hover:animate-ping"></div>
            
            <i class="fa-solid fa-robot text-white dark:text-geo-navy text-2xl group-hover:rotate-12 transition-transform duration-300"></i>
            
            <span class="absolute top-0 right-0 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 border-2 border-[var(--geo-bg)] top-0 right-0"></span>
            </span>
        </button>
    </div>
</template>
