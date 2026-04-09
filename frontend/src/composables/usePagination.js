import { ref, computed } from 'vue';

/**
 * Universal Composable for client-side pagination of any data array.
 * * @param {import('vue').Ref<Array>} dataRef - Reactive reference to the filtered array.
 * @param {number} defaultItemsPerPage - Number of items per page.
 */
export function usePagination(dataRef, defaultItemsPerPage = 20) {
    const currentPage = ref(1);
    const itemsPerPage = ref(defaultItemsPerPage);

    // Count the total number of pages
    const totalPages = computed(() => {
        if (!dataRef.value || dataRef.value.length === 0) return 1;
        return Math.ceil(dataRef.value.length / itemsPerPage.value);
    });

    // Get elements for the current page only
    const paginatedData = computed(() => {
        if (!dataRef.value) return [];
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return dataRef.value.slice(start, end);
    });

    // Method to reset to the first page
    const resetPage = () => {
        currentPage.value = 1;
    };

    return {
        currentPage,
        itemsPerPage,
        totalPages,
        paginatedData,
        resetPage
    };
}
