import { ref, computed, watch } from 'vue';
import { usePagination } from './usePagination';

/**
 * Composable handling the logic for filtering, searching, and paginating bookings.
 * Used by both Admin and Manager views.
 * * @param {Array} initialBookings - Reactive reference to the main bookings array.
 */
export function useBookingsManager(initialBookings) {
    const searchQuery = ref('');
    const selectedStatuses = ref(['pending', 'confirmed', 'finished', 'cancelled']);
    const dateMode = ref('all');
    const customDate = ref('');

    // Helper function to get local YYYY-MM-DD
    const getLocalYYYYMMDD = (dateObj) => {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // Main computed property for filtered bookings
    const filteredBookings = computed(() => {
        if (!initialBookings.value) return [];

        let result = initialBookings.value.filter(b => selectedStatuses.value.includes(b.status));

        // Filter by Date
        if (dateMode.value !== 'all') {
            const todayStr = getLocalYYYYMMDD(new Date());
            result = result.filter(b => {
                if (!b.created_at) return false;
                const bDateStr = getLocalYYYYMMDD(new Date(b.created_at));

                if (dateMode.value === 'today') {
                    return bDateStr === todayStr;
                } else if (dateMode.value === 'custom' && customDate.value) {
                    return bDateStr === customDate.value;
                }
                return true;
            });
        }

        // Filter by Search Query
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const queryDigits = query.replace(/\D/g, '');

            result = result.filter(b => {
                const idStr = String(b.id);
                const clientName = (b.guest_name || b.user?.name || '').toLowerCase();
                const clientEmail = (b.guest_email || b.user?.email || '').toLowerCase();
                const clientPhoneOriginal = (b.guest_phone || '').toLowerCase();
                const clientPhoneDigits = clientPhoneOriginal.replace(/\D/g, '');

                const phoneMatch = clientPhoneOriginal.includes(query) ||
                    (queryDigits.length > 0 && clientPhoneDigits.includes(queryDigits));

                return idStr.includes(query) ||
                    clientName.includes(query) ||
                    clientEmail.includes(query) ||
                    phoneMatch;
            });
        }
        return result;
    });

    /**
     * Integrate universal pagination composable.
     * We specify 20 items per page and rename 'paginatedData' to 'paginatedBookings' for context.
     */
    const {
        currentPage,
        itemsPerPage,
        totalPages,
        paginatedData: paginatedBookings,
        resetPage
    } = usePagination(filteredBookings, 20);

    // Reset pagination to the first page whenever any filter changes
    watch([searchQuery, selectedStatuses, dateMode, customDate], () => {
        resetPage();
    }, { deep: true });

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
        // State
        searchQuery,
        selectedStatuses,
        dateMode,
        customDate,
        currentPage,
        itemsPerPage,

        // Computed
        filteredBookings,
        paginatedBookings,
        totalPages,

        // Constants
        statusNames,
        paymentNames,
        statusClasses
    };
}
