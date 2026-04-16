import { ref, watch, computed } from 'vue';

/**
 * Composable handling the logic for filtering, searching, and paginating bookings.
 * Adapted for server-side pagination.
 */
export function useBookingsManager(fetchCallback) {
    const searchQuery = ref('');
    const selectedStatuses = ref(['pending', 'confirmed', 'finished', 'cancelled']);
    const dateMode = ref('all');
    const customDate = ref('');

    const currentPage = ref(1);
    const itemsPerPage = ref(20);
    const totalPages = ref(1);
    const totalItems = ref(0);

    const bookings = ref([]);

    /**
     * Build the query params object to send to the server.
     */
    const buildQueryParams = () => {
        const params = {
            page: currentPage.value,
            per_page: itemsPerPage.value,
        };

        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        if (selectedStatuses.value.length) {
            params.statuses = selectedStatuses.value;
        }

        if (dateMode.value !== 'all') {
            params.dateMode = dateMode.value;
            if (dateMode.value === 'custom' && customDate.value) {
                params.customDate = customDate.value;
            }
        }

        return params;
    };

    /**
     * Applies metadata from the Laravel paginator response.
     */
    const setPaginationData = (meta) => {
        currentPage.value = meta.current_page || 1;
        totalPages.value = meta.last_page || 1;
        totalItems.value = meta.total || 0;
    };

    // Watch filters to reset page to 1 and fetch data
    watch([searchQuery, selectedStatuses, dateMode, customDate], () => {
        currentPage.value = 1;
        if (fetchCallback) fetchCallback();
    }, { deep: true });

    // Watch page changes (not triggering reset) to fetch data
    watch(currentPage, (newVal, oldVal) => {
        if (newVal !== oldVal && fetchCallback) {
            fetchCallback();
        }
    });

    // Dictionaries
    const statusNames = {
        pending: 'Очікує',
        confirmed: 'Підтверджено',
        cancelled: 'Скасовано',
        finished: 'Завершено'
    };

    const paymentNames = {
        cash: 'Готівка',
        card: 'Картка'
    };

    const statusClasses = {
        pending: 'bg-yellow-100 text-yellow-700',
        confirmed: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
        finished: 'bg-blue-100 text-blue-700'
    };

    return {
        // Search & Filters State
        searchQuery,
        selectedStatuses,
        dateMode,
        customDate,

        // Pagination State
        currentPage,
        itemsPerPage,
        totalPages,
        totalItems,

        // Data
        bookings,
        buildQueryParams,
        setPaginationData,

        // Constants
        statusNames,
        paymentNames,
        statusClasses
    };
}
