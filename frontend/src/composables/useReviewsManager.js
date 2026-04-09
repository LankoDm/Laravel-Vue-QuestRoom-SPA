import { ref, computed, watch } from 'vue';
import { usePagination } from './usePagination';

/**
 * Composable handling the logic for filtering and paginating reviews.
 *
 * @param {import('vue').Ref<Array>} initialReviews - Reactive reference to the raw reviews array fetched from the API.
 * @returns {Object} Reactive state, computed properties, and pagination controls for review management.
 */
export function useReviewsManager(initialReviews) {
    /** * Current filter status ('all', 'published', 'new').
     * @type {import('vue').Ref<string>}
     */
    const filterStatus = ref('all');

    /**
     * Filter the reviews based on the selected status.
     * Evaluates dynamically whenever `initialReviews` or `filterStatus` changes.
     */
    const filteredReviews = computed(() => {
        if (!initialReviews.value) return [];

        if (filterStatus.value === 'published') {
            return initialReviews.value.filter(r => r.is_approved);
        }
        if (filterStatus.value === 'new') {
            return initialReviews.value.filter(r => !r.is_approved);
        }

        return initialReviews.value;
    });

    /**
     * Initialize universal pagination with the filtered data.
     * Sets the default items per page to 12.
     */
    const {
        currentPage,
        totalPages,
        paginatedData: paginatedReviews, // Aliased for better contextual readability
        itemsPerPage,
        resetPage
    } = usePagination(filteredReviews, 12);

    /**
     * Reset pagination to the first page whenever the filter criteria changes.
     */
    watch(filterStatus, () => {
        resetPage();
    });

    return {
        // State
        filterStatus,
        currentPage,
        itemsPerPage,

        // Computed
        filteredReviews,
        paginatedReviews,
        totalPages
    };
}
