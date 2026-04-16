/**
 * Composable for shared room dictionaries and image parsing logic.
 */
export function useRoomHelpers() {
    const difficultyMap = {
        'easy': 'Легкий',
        'medium': 'Середній',
        'hard': 'Складний',
        'ultra hard': 'Експерт'
    };

    const genreMap = {
        'horror': 'Жахи',
        'detective': 'Детектив',
        'action': 'Екшн',
        'mystic': 'Містика',
        'adventure': 'Пригоди'
    };

    /**
     * Helper to format generic image path with the backend's storage URL.
     */
    const formatImageUrl = (path) => {
        if (!path) return null;
        if (path.startsWith('http')) return path;
        const baseUrl = import.meta.env.VITE_API_URL ? import.meta.env.VITE_API_URL.replace('/api', '') : 'http://localhost:8080';
        return `${baseUrl}/storage/${path}`;
    };

    /**
     * Safely parses the image path and returns the first image URL.
     */
    const getFirstImage = (imagePath) => {
        if (!imagePath) return null;
        let result = imagePath;
        if (Array.isArray(imagePath)) {
            result = imagePath[0];
        } else if (typeof imagePath === 'string' && imagePath.startsWith('[')) {
            try { result = JSON.parse(imagePath)[0]; }
            catch (e) { result = imagePath; }
        }
        return formatImageUrl(result);
    };

    /**
     * Safely parses the image path and returns an array of all image URLs.
     */
    const parseImagesArray = (imagePath) => {
        if (!imagePath) return [];
        let result = [];
        if (Array.isArray(imagePath)) {
            result = imagePath;
        } else if (typeof imagePath === 'string' && imagePath.startsWith('[')) {
            try { result = JSON.parse(imagePath); }
            catch (e) { result = [imagePath]; }
        } else {
            result = [imagePath];
        }
        return result.map(formatImageUrl);
    };

    return {
        difficultyMap,
        genreMap,
        getFirstImage,
        parseImagesArray
    };
}
