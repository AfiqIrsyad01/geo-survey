<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, Object],
        default: null
    },
    options: {
        type: Array, // Array of { value: any, label: string }
        required: true
    },
    placeholder: {
        type: String,
        default: 'Select'
    },
    customClass: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const selectRef = ref(null);
const btnRef = ref(null);
const listStyle = ref({});

// Compute dropdown position from the trigger button's bounding rect
const computePosition = async () => {
    await nextTick();
    if (!btnRef.value) return;
    const rect = btnRef.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const dropUp = spaceBelow < 200 && spaceAbove > spaceBelow;

    listStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        minWidth: 'max-content',
        zIndex: 99999,
        ...(dropUp
            ? { bottom: `${window.innerHeight - rect.top}px`, top: 'auto' }
            : { top: `${rect.bottom + 4}px`, bottom: 'auto' }),
    };
};

const toggle = async () => {
    open.value = !open.value;
    if (open.value) {
        await computePosition();
    }
};

const close = () => { open.value = false; };

const isSelected = (opt) => opt.value === props.modelValue ||
    (opt.value == props.modelValue && props.modelValue !== null);

const currentLabel = computed(() => {
    const opt = props.options.find(isSelected);
    return opt ? opt.label : props.placeholder;
});

const selectOption = (opt) => {
    emit('update:modelValue', opt.value);
    emit('change', opt.value);
    close();
};

const handleClickOutside = (e) => {
    if (
        selectRef.value && !selectRef.value.contains(e.target) &&
        !e.target.closest('[data-custom-select-list]')
    ) {
        close();
    }
};

const handleKeydown = (e) => {
    if (!open.value) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            open.value = true;
        }
        return;
    }

    if (e.key === 'Escape') {
        close();
    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        const currentIndex = props.options.findIndex(isSelected);
        let nextIndex = e.key === 'ArrowDown' ? currentIndex + 1 : currentIndex - 1;
        if (nextIndex < 0) nextIndex = 0;
        if (nextIndex >= props.options.length) nextIndex = props.options.length - 1;
        emit('update:modelValue', props.options[nextIndex].value);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        close();
    }
};

// Reposition on scroll/resize while open
const handleScroll = () => { if (open.value) computePosition(); };

onMounted(() => {
    document.addEventListener('click', handleClickOutside, true);
    window.addEventListener('scroll', handleScroll, true);
    window.addEventListener('resize', handleScroll);
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside, true);
    window.removeEventListener('scroll', handleScroll, true);
    window.removeEventListener('resize', handleScroll);
});
</script>

<template>
    <div class="relative w-full" ref="selectRef" @keydown="handleKeydown">
        <button
            type="button"
            ref="btnRef"
            @click="toggle"
            :class="[
                'relative w-full cursor-pointer rounded-lg bg-white dark:bg-geo-navy py-1.5 pl-3 pr-8 text-left shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-white/10 focus:outline-none focus:ring-2 focus:ring-geo-teal text-xs transition-all',
                customClass
            ]"
            aria-haspopup="listbox"
            :aria-expanded="open"
        >
            <span class="block truncate text-geo-navy dark:text-white font-bold">{{ currentLabel }}</span>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg
                    class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform duration-150"
                    :class="{ 'rotate-180': open }"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                >
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <!-- Teleport to body to escape any overflow/z-index stacking context -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <ul
                    v-if="open"
                    data-custom-select-list
                    :style="listStyle"
                    class="max-h-[50vh] overflow-auto rounded-xl bg-white dark:bg-geo-navy py-1 text-xs shadow-2xl ring-1 ring-black/10 dark:ring-white/10 focus:outline-none custom-scrollbar"
                    tabindex="-1"
                    role="listbox"
                >
                    <li
                        v-for="(opt, index) in options"
                        :key="index"
                        class="relative cursor-pointer select-none py-1.5 pl-3 pr-8 whitespace-nowrap text-gray-900 dark:text-gray-200 hover:bg-geo-teal hover:text-white transition-colors group"
                        :class="{ 'bg-geo-teal/10 dark:bg-geo-teal/20 text-geo-teal dark:text-geo-teal font-extrabold': isSelected(opt) }"
                        @click="selectOption(opt)"
                        role="option"
                        :aria-selected="isSelected(opt)"
                    >
                        <span class="block" :class="{ 'font-black': isSelected(opt), 'font-medium': !isSelected(opt) }">
                            {{ opt.label }}
                        </span>
                        <span
                            v-if="isSelected(opt)"
                            class="absolute inset-y-0 right-0 flex items-center pr-2 text-geo-teal group-hover:text-white"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </li>
                </ul>
            </Transition>
        </Teleport>
    </div>
</template>
