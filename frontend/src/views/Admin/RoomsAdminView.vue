<script setup>
import {ref, onMounted} from 'vue';
import axios from 'axios';
import {useToastStore} from '@/stores/toast';
import {useFormatters} from '@/composables/useFormatters';
import {useRoomHelpers} from '@/composables/useRoomHelpers';

const toast = useToastStore();

// Import shared logic
const {formatPrice} = useFormatters();
const {difficultyMap} = useRoomHelpers();

// State
const rooms = ref([]);
const isLoading = ref(true);
const searchQuery = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
let searchTimeout = null;

/**
 * Fetch paginated rooms from the server.
 * @param {number} page
 */
const fetchRooms = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {page, show_all: 1};
        if (searchQuery.value) params.search = searchQuery.value;

        const response = await axios.get('http://localhost:8080/api/rooms', {params});
        if (response.data && response.data.data) {
            rooms.value = response.data.data;
            currentPage.value = response.data.current_page;
            lastPage.value = response.data.last_page;
        } else {
            rooms.value = response.data || [];
            currentPage.value = 1;
            lastPage.value = 1;
        }
    } catch (error) {
        console.error('Помилка завантаження кімнат:', error);
    } finally {
        isLoading.value = false;
    }
};

/**
 * Handle search input with debounce.
 */
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchRooms(1), 500);
};

/**
 * Change current pagination page.
 */
const changePage = (page) => {
    if (page >= 1 && page <= lastPage.value) fetchRooms(page);
};

/**
 * Toggle room active status directly in DB.
 */
const toggleStatus = async (room) => {
    const originalStatus = room.is_active;
    room.is_active = room.is_active ? 0 : 1;

    try {
        await axios.patch(`http://localhost:8080/api/rooms/${room.id}/toggle-status`, {is_active: room.is_active});
        toast.success('Статус успішно змінено');
    } catch (error) {
        room.is_active = originalStatus;
        toast.error('Не вдалося змінити статус.');
    }
};

/**
 * Delete a room permanently.
 */
const deleteRoom = async (id) => {
    if (!confirm('Ви дійсно хочете видалити цю кімнату? Цю дію неможливо скасувати.')) return;

    try {
        await axios.delete(`http://localhost:8080/api/rooms/${id}`);
        rooms.value = rooms.value.filter(room => room.id !== id);
        toast.success('Кімнату видалено');
    } catch (error) {
        toast.error('Не вдалося видалити кімнату. Можливо, до неї прив\'язані бронювання.');
    }
};

onMounted(() => fetchRooms());
</script>

<template>
    <div>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <h1 class="text-3xl font-black text-text">Управління кімнатами</h1>

            <div class="flex gap-4 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <input v-model="searchQuery" @input="onSearchInput" type="text" placeholder="Пошук за назвою..."
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-white font-medium">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <RouterLink :to="{ name: 'admin.rooms.create' }"
                            class="bg-primary hover:bg-purple-500 text-white px-6 py-3 rounded-xl font-bold shadow-md transition-colors flex items-center justify-center gap-2 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="hidden sm:inline">Додати кімнату</span>
                </RouterLink>
            </div>
        </div>

        <div v-if="isLoading" class="text-center py-12 text-gray-500 animate-pulse font-bold">
            Завантаження списку кімнат...
        </div>

        <div v-else class="bg-white rounded-3xl shadow-sm border border-secondary overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-secondary/30 border-b border-secondary text-gray-500 font-bold uppercase text-xs tracking-wider">
                        <th class="p-4 pl-6">Назва</th>
                        <th class="p-4">Складність</th>
                        <th class="p-4">Будні / Вихідні</th>
                        <th class="p-4 text-center">Статус</th>
                        <th class="p-4 text-center">Дії</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <tr v-for="room in rooms" :key="room.id" class="hover:bg-gray-50 transition-colors">
                        <td class="p-4 pl-6">
                            <div class="font-bold text-text">{{ room.name }}</div>
                            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                                <span>Рейтинг: {{
                                        room.reviews_avg_rating ? Number(room.reviews_avg_rating).toFixed(1) : '—'
                                    }}</span>
                                <span>Відгуків: {{ room.reviews_count || 0 }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                  <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                      :class="room.difficulty === 'hard' || room.difficulty === 'ultra hard' ? 'bg-red-100 text-red-600' : (room.difficulty === 'medium' ? 'bg-yellow-100 text-yellow-600' : 'bg-green-100 text-green-600')">
                    {{ difficultyMap[room.difficulty] || room.difficulty }}
                  </span>
                        </td>
                        <td class="p-4">
                            <div class="font-bold text-text">{{ formatPrice(room.weekday_price) }} ₴</div>
                            <div class="text-xs text-gray-500">{{ formatPrice(room.weekend_price) }} ₴</div>
                        </td>
                        <td class="p-4 text-center align-middle">
                            <button @click="toggleStatus(room)" :class="room.is_active ? 'bg-primary' : 'bg-gray-200'"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none cursor-pointer"
                                    :title="room.is_active ? 'Вимкнути кімнату' : 'Увімкнути кімнату'">
                    <span :class="room.is_active ? 'translate-x-6' : 'translate-x-1'"
                          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-300 shadow-sm"/>
                            </button>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <RouterLink :to="{ name: 'admin.rooms.edit', params: { id: room.id } }"
                                            class="block p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                            title="Редагувати">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </RouterLink>
                                <button @click="deleteRoom(room.id)"
                                        class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                        title="Видалити">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="rooms.length === 0" class="p-8 text-center text-gray-500 font-medium">
                За вашим запитом нічого не знайдено.
            </div>

            <div v-if="lastPage > 1"
                 class="p-6 bg-gray-50 border-t border-gray-100 flex justify-center items-center gap-2">
                <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                        class="px-4 py-2 rounded-xl font-bold transition-all border"
                        :class="currentPage === 1 ? 'border-gray-200 text-gray-400 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:border-primary cursor-pointer'">
                    Назад
                </button>
                <button v-for="page in lastPage" :key="page" @click="changePage(page)"
                        class="w-10 h-10 rounded-xl font-bold transition-all flex items-center justify-center cursor-pointer border"
                        :class="page === currentPage ? 'bg-primary text-white border-primary shadow-md' : 'bg-white text-gray-600 border-secondary hover:border-primary'">
                    {{ page }}
                </button>
                <button @click="changePage(currentPage + 1)" :disabled="currentPage === lastPage"
                        class="px-4 py-2 rounded-xl font-bold transition-all border"
                        :class="currentPage === lastPage ? 'border-gray-200 text-gray-400 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:border-primary cursor-pointer'">
                    Вперед
                </button>
            </div>
        </div>
    </div>
</template>
