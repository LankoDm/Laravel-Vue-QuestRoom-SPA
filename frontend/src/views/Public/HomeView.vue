<script setup>
import {ref, onMounted} from 'vue';
import {useRouter} from 'vue-router';
import axios from 'axios';
import {useRoomHelpers} from '@/composables/useRoomHelpers';

const router = useRouter();

// Import shared logic
const {difficultyMap, genreMap, getFirstImage} = useRoomHelpers();

// State
const currentPage = ref(1);
const lastPage = ref(1);
const rooms = ref([]);
const isLoading = ref(true);
let searchTimeout = null;
const openSections = ref(['difficulty', 'age', 'genres']);

const filters = ref({
    search: '', difficulty: [], players_count: '', sort: '', age: [], genres: []
});

/**
 * Fetch filtered rooms from server.
 */
const fetchRooms = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {page};

        if (filters.value.search) params.search = filters.value.search;
        if (filters.value.players_count) params.players_count = filters.value.players_count;
        if (filters.value.sort) params.sort = filters.value.sort;
        if (filters.value.difficulty?.length) params.difficulty = filters.value.difficulty;
        if (filters.value.age?.length) params.age = filters.value.age;
        if (filters.value.genres?.length) params.genres = filters.value.genres;

        const response = await axios.get('/rooms', {params});
        if (response.data && response.data.data) {
            rooms.value = response.data.data;
            currentPage.value = response.data.meta?.current_page || response.data.current_page || 1;
            lastPage.value = response.data.meta?.last_page || response.data.last_page || 1;
        } else {
            rooms.value = response.data || [];
            currentPage.value = 1;
            lastPage.value = 1;
        }
    } catch (error) {
        console.error('Помилка завантаження даних:', error);
    } finally {
        isLoading.value = false;
    }
};

/**
 * Change page and scroll up.
 */
const changePage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        fetchRooms(page);
        window.scrollTo({top: 400, behavior: 'smooth'});
    }
};

/**
 * Handle search input with debounce.
 */
const onInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchRooms(1), 500);
};

/**
 * Reset all filters.
 */
const resetFilters = () => {
    filters.value = {search: '', difficulty: [], players_count: '', sort: '', age: [], genres: []};
    fetchRooms(1);
};

const openRoom = (slug) => router.push({name: 'room.show', params: {slug}});

const toggleSection = (name) => {
    if (openSections.value.includes(name)) {
        openSections.value = openSections.value.filter(s => s !== name);
    } else {
        openSections.value.push(name);
    }
};

onMounted(() => fetchRooms());
</script>

<template>
    <div>
        <div class="bg-primary text-white py-20 px-6 relative overflow-hidden">
            <div class="absolute inset-0 bg-black/20 z-10"></div>
            <div class="max-w-6xl mx-auto relative z-20 text-center">
                <h1 class="text-5xl md:text-6xl font-black mb-6 tracking-tight">
                    Вирвись із реальності
                </h1>
                <p class="text-xl md:text-2xl font-medium opacity-90 max-w-2xl mx-auto mb-10">
                    Наші квест кімнати найкращі і найцікавіші <!-- потім може змінити -->
                </p>
            </div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl z-0"></div>
            <div class="absolute top-10 -right-10 w-48 h-48 bg-purple-500/40 rounded-full blur-3xl z-0"></div>
        </div>

        <div class="py-16 px-6 max-w-7xl mx-auto">

            <div class="mb-10">
                <h2 class="text-3xl font-black text-text mb-2">Наші квести</h2>
                <p class="text-gray-500 font-medium">Оберіть свою наступну пригоду</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start">

                <div class="w-full lg:w-1/4 shrink-0 lg:sticky lg:top-24">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary space-y-6">

                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <h3 class="font-black text-xl text-text">Фільтри</h3>
                            <button @click="resetFilters"
                                    v-if="filters.difficulty.length || filters.age.length || filters.genres.length || filters.search || filters.players_count"
                                    class="text-xs font-bold text-red-500 hover:text-red-700 cursor-pointer">
                                Скинути все
                            </button>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Пошук</label>
                            <input v-model="filters.search" @input="onInput" type="text" placeholder="Назва кімнати"
                                   class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 text-text font-medium">
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">К-ть
                                    людей</label>
                                <input v-model="filters.players_count" @input="onInput" type="number" min="1"
                                       placeholder="Гравців"
                                       class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 text-text font-medium">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Сортування</label>
                                <select v-model="filters.sort" @change="fetchRooms(1)"
                                        class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 font-medium cursor-pointer appearance-none">
                                    <option value="">Нові</option>
                                    <option value="rating_desc">Топ рейтинг</option>
                                    <option value="rating_asc">Низький рейтинг</option>
                                    <option value="difficulty_asc">Легкі спочатку</option>
                                    <option value="difficulty_desc">Складні спочатку</option>
                                </select>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4">
                            <button @click="toggleSection('difficulty')" type="button"
                                    class="w-full flex justify-between items-center cursor-pointer mb-2">
                                <label class="text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer">Складність</label>
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                     :class="{'rotate-180': openSections.includes('difficulty')}" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div v-show="openSections.includes('difficulty')" class="space-y-2 mt-3 animate-fade-in">
                                <label
                                    v-for="(label, val) in {'easy': 'Легкий', 'medium': 'Середній', 'hard': 'Складний', 'ultra hard': 'Експерт'}"
                                    :key="val"
                                    class="flex items-center group cursor-pointer">
                                    <input type="checkbox" :value="val" v-model="filters.difficulty"
                                           @change="fetchRooms(1)"
                                           class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer transition-colors bg-gray-50 checked:bg-primary">
                                    <span
                                        class="ml-3 text-sm font-medium text-gray-600 group-hover:text-primary transition-colors">{{
                                            label
                                        }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4">
                            <button @click="toggleSection('age')" type="button"
                                    class="w-full flex justify-between items-center cursor-pointer mb-2">
                                <label class="text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer">Вік</label>
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                     :class="{'rotate-180': openSections.includes('age')}" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div v-show="openSections.includes('age')"
                                 class="grid grid-cols-2 gap-2 mt-3 animate-fade-in">
                                <label v-for="val in ['0+', '8+', '10+', '12+', '16+', '18+']" :key="val"
                                       class="flex items-center group cursor-pointer">
                                    <input type="checkbox" :value="val" v-model="filters.age" @change="fetchRooms(1)"
                                           class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer transition-colors bg-gray-50 checked:bg-primary">
                                    <span
                                        class="ml-2 text-sm font-medium text-gray-600 group-hover:text-primary transition-colors">{{
                                            val
                                        }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4">
                            <button @click="toggleSection('genres')" type="button"
                                    class="w-full flex justify-between items-center cursor-pointer mb-2">
                                <label class="text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer">Жанр</label>
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                     :class="{'rotate-180': openSections.includes('genres')}" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div v-show="openSections.includes('genres')" class="space-y-2 mt-3 animate-fade-in">
                                <label
                                    v-for="(label, val) in {'horror': 'Жахи', 'detective': 'Детектив', 'action': 'Екшн', 'mystic': 'Містика', 'adventure': 'Пригоди'}"
                                    :key="val"
                                    class="flex items-center group cursor-pointer">
                                    <input type="checkbox" :value="val" v-model="filters.genres" @change="fetchRooms(1)"
                                           class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer transition-colors bg-gray-50 checked:bg-primary">
                                    <span
                                        class="ml-3 text-sm font-medium text-gray-600 group-hover:text-primary transition-colors">{{
                                            label
                                        }}</span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="w-full lg:w-3/4">

                    <div v-if="isLoading"
                         class="text-center text-xl text-primary animate-pulse py-20 font-bold bg-white rounded-3xl border border-secondary shadow-sm">
                        Завантаження квестів
                    </div>

                    <div v-else-if="rooms.length === 0"
                         class="text-center py-20 text-gray-500 bg-white border border-secondary rounded-3xl font-medium shadow-sm">
                        За вашими критеріями не знайдено жодної кімнати
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                        <div
                            v-for="room in rooms"
                            :key="room.id"
                            @click="openRoom(room.slug)"
                            class="bg-white rounded-3xl shadow-sm border border-secondary hover:shadow-xl hover:-translate-y-2 transition-all duration-300 cursor-pointer overflow-hidden flex flex-col group">

                            <div class="h-56 w-full bg-gray-100 relative overflow-hidden">
                                <img
                                    v-if="getFirstImage(room.image_path)"
                                    :src="getFirstImage(room.image_path)"
                                    :alt="room.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                    Немає зображення
                                </div>

                                <div class="absolute top-4 left-4 flex gap-2">
                  <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider shadow-lg"
                        :class="room.difficulty === 'hard' || room.difficulty === 'ultra hard' ? 'bg-red-100 text-red-600' : (room.difficulty === 'medium' ? 'bg-yellow-100 text-yellow-600' : 'bg-green-100 text-green-600')">
                    {{ difficultyMap[room.difficulty] || room.difficulty }}
                  </span>
                                    <span v-if="room.age"
                                          class="bg-black/70 backdrop-blur-md text-white px-3 py-1 rounded-xl text-xs font-black tracking-wider shadow-lg border border-white/20">
                    {{ room.age }}
                  </span>
                                </div>
                            </div>

                            <div class="p-6 flex flex-col flex-grow">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="text-2xl font-black text-text leading-tight pr-4 group-hover:text-primary transition-colors line-clamp-2">
                                        {{ room.name }}
                                    </h3>

                                    <div
                                        class="flex items-center space-x-1 bg-yellow-50 px-2 py-1 rounded-lg shrink-0 border border-yellow-100">
                                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="text-sm font-black text-yellow-700">
                      {{ room.rating ? Number(room.rating).toFixed(1) : 'Новий' }}
                    </span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-4 mb-4 text-sm font-bold text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ room.duration_minutes }} хв
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        {{ room.min_players }}-{{ room.max_players }}
                                    </div>
                                    <div v-if="room.genre" class="flex items-center gap-1 truncate max-w-full">
                                        <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path>
                                        </svg>
                                        <span class="capitalize">{{ genreMap[room.genre] || room.genre }}</span>
                                    </div>
                                </div>

                                <p class="text-gray-500 text-sm line-clamp-3 mt-auto">
                                    {{ room.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-if="lastPage > 1" class="mt-12 flex justify-center items-center gap-2">
                        <button
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-4 py-2 rounded-xl font-bold transition-all border"
                            :class="currentPage === 1 ? 'border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:border-primary hover:bg-primary/5 cursor-pointer'">
                            Назад
                        </button>
                        <button
                            v-for="page in lastPage"
                            :key="page"
                            @click="changePage(page)"
                            class="w-10 h-10 rounded-xl font-bold transition-all flex items-center justify-center cursor-pointer border"
                            :class="page === currentPage ? 'bg-primary text-white border-primary shadow-md' : 'bg-white text-gray-600 border-secondary hover:border-primary hover:text-primary'">
                            {{ page }}
                        </button>
                        <button
                            @click="changePage(currentPage + 1)"
                            :disabled="currentPage === lastPage"
                            class="px-4 py-2 rounded-xl font-bold transition-all border"
                            :class="currentPage === lastPage ? 'border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:border-primary hover:bg-primary/5 cursor-pointer'">
                            Вперед
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
