import { ref, computed, watch } from 'vue';
import { usePagination } from './usePagination';

/**
 * Composable handling the logic for filtering and paginating reviews.
 */
export function useReviewsManager(initialReviews) {
    /** * Current filter status.
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
    } = usePagination(filteredReviews, 9);

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
