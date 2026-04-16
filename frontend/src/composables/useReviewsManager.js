import { ref, watch } from 'vue';

/**
 * Composable handling the logic for filtering and paginating reviews from the server.
 */
export function useReviewsManager(fetchCallback) {
    const filterStatus = ref('all');
    const currentPage = ref(1);
    const itemsPerPage = ref(9);
    const totalPages = ref(1);
    const totalItems = ref(0);
    const reviews = ref([]);

    /**
     * Build query params object.
     */
    const buildQueryParams = () => {
        const params = {
            page: currentPage.value,
            per_page: itemsPerPage.value
        };

        if (filterStatus.value !== 'all') {
            params.status = filterStatus.value;
        }
        
        return params;
    };

    /**
     * Update pagination from Laravel metadata.
     */
    const setPaginationData = (meta) => {
        currentPage.value = meta.current_page || 1;
        totalPages.value = meta.last_page || 1;
        totalItems.value = meta.total || 0;
    };

    // Reset page and refetch when filters change
    watch(filterStatus, () => {
        currentPage.value = 1;
        if (fetchCallback) fetchCallback();
    });

    // Fetch data when page changes
    watch(currentPage, (newVal, oldVal) => {
        if (newVal !== oldVal && fetchCallback) {
            fetchCallback();
        }
    });

    return {
        // State
        filterStatus,
        currentPage,
        itemsPerPage,
        totalPages,
        totalItems,
        reviews,

        // Methods
        buildQueryParams,
        setPaginationData
    };
}
