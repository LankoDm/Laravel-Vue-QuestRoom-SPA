/**
 * Composable for formatting data (prices, dates, time) across the app.
 */
export function useFormatters() {
    /**
     * Converts kopecks to standard currency format.
     * @param {number} kopecks - The price in kopecks.
     * @returns {number} The price in UAH.
     */
    const formatPrice = (kopecks) => {
        return kopecks ? kopecks / 100 : 0;
    };

    /**
     * Formats a date string to a localized standard format.
     * @param {string} dateString - The raw date string.
     * @returns {string} Formatted date (DD.MM.YYYY).
     */
    const formatDateTime = (dateString) => {
        if (!dateString) return '—';
        return new Date(dateString).toLocaleDateString('uk-UA', {
            day: '2-digit', month: '2-digit', year: 'numeric'
        });
    };

    /**
     * Formats a date string to extract just the time.
     * @param {string} dateString - The raw date string.
     * @returns {string} Formatted time (HH:MM).
     */
    const formatTime = (dateString) => {
        if (!dateString) return '—';
        return new Date(dateString).toLocaleTimeString('uk-UA', {
            hour: '2-digit', minute: '2-digit'
        });
    };

    /**
     * Formats a full date and time string.
     * @param {string} dateString - The raw date string.
     * @returns {string} Formatted full date (DD.MM.YYYY HH:MM).
     */
    const formatFullDate = (dateString) => {
        if (!dateString) return '—';
        return new Date(dateString).toLocaleString('uk-UA', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    };

    return {
        formatPrice,
        formatDateTime,
        formatTime,
        formatFullDate
    };
}
