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
     * Safely parses the image path and returns the first image URL.
     */
    const getFirstImage = (imagePath) => {
        if (!imagePath) return null;
        if (Array.isArray(imagePath)) return imagePath[0];
        if (typeof imagePath === 'string' && imagePath.startsWith('[')) {
            try { return JSON.parse(imagePath)[0]; }
            catch (e) { return imagePath; }
        }
        return imagePath;
    };

    /**
     * Safely parses the image path and returns an array of all image URLs.
     */
    const parseImagesArray = (imagePath) => {
        if (!imagePath) return [];
        if (Array.isArray(imagePath)) return imagePath;
        if (typeof imagePath === 'string' && imagePath.startsWith('[')) {
            try { return JSON.parse(imagePath); }
            catch (e) { return [imagePath]; }
        }
        return [imagePath];
    };

    return {
        difficultyMap,
        genreMap,
        getFirstImage,
        parseImagesArray
    };
}
