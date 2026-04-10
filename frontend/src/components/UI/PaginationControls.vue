<script setup>
import { computed } from 'vue';

/**
 * @property {number} currentPage - The current active page.
 * @property {number} totalPages - Total number of available pages.
 * @property {number} totalItems - Total number of items across all pages.
 * @property {number} itemsPerPage - Number of items displayed per page.
 */
const props = defineProps({
    currentPage: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    totalItems: { type: Number, required: true },
    itemsPerPage: { type: Number, required: true }
});

const emit = defineEmits(['update:currentPage']);

/**
 * Calculates the starting index of items on the current page.
 */
const startItem = computed(() => {
    if (props.totalItems === 0) return 0;
    return (props.currentPage - 1) * props.itemsPerPage + 1;
});

/**
 * Calculates the ending index of items on the current page.
 */
const endItem = computed(() => {
    return Math.min(props.currentPage * props.itemsPerPage, props.totalItems);
});

/**
 * Emits event to go to the previous page.
 */
const prevPage = () => {
    if (props.currentPage > 1) {
        emit('update:currentPage', props.currentPage - 1);
    }
};

/**
 * Emits event to go to the next page.
 */
const nextPage = () => {
    if (props.currentPage < props.totalPages) {
        emit('update:currentPage', props.currentPage + 1);
    }
};
</script>

<template>
    <div v-if="totalPages > 1" class="flex flex-col sm:flex-row justify-between items-center gap-4 p-4 border-t border-secondary bg-gray-50/50 mt-auto">
        <div class="text-sm font-medium text-gray-500">
            Показано <span class="font-bold text-text">{{ startItem }}</span> -
            <span class="font-bold text-text">{{ endItem }}</span>
            із <span class="font-bold text-text">{{ totalItems }}</span>
        </div>

        <div class="flex items-center gap-4">
            <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="px-4 py-2 rounded-xl font-bold text-sm transition-colors border"
                :class="currentPage === 1 ? 'border-gray-100 text-gray-300 bg-gray-50 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:bg-secondary'">
                &larr; Назад
            </button>

            <span class="text-sm font-bold text-text">Стор. {{ currentPage }} з {{ totalPages }}</span>

            <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="px-4 py-2 rounded-xl font-bold text-sm transition-colors border"
                :class="currentPage === totalPages ? 'border-gray-100 text-gray-300 bg-gray-50 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:bg-secondary'">
                Далі &rarr;
            </button>
        </div>
    </div>
</template>
