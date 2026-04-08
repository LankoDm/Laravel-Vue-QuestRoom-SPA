import { ref, computed, watch } from 'vue';

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
    const currentPage = ref(1);
    const itemsPerPage = 20;

    // Reset pagination when filters change
    watch([searchQuery, selectedStatuses, dateMode, customDate], () => {
        currentPage.value = 1;
    }, { deep: true });

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

    // Pagination logic
    const totalPages = computed(() => Math.ceil(filteredBookings.value.length / itemsPerPage) || 1);

    const paginatedBookings = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        return filteredBookings.value.slice(start, end);
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
