<script setup>
import {ref, onMounted} from 'vue';
import axios from 'axios';
import {useToastStore} from '@/stores/toast';
import {useFormatters} from '@/composables/useFormatters';
import {useBookingsManager} from '@/composables/useBookingsManager';
import PaginationControls from '@/components/UI/PaginationControls.vue';

const toast = useToastStore();
const isLoading = ref(true);

// Initialize Composables
const {formatPrice, formatFullDate} = useFormatters();

/**
 * Fetch bookings from the server based on current filters.
 */
const fetchBookings = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/bookings', { params: buildQueryParams() });
        bookings.value = response.data.data;
        setPaginationData(response.data.meta || response.data);
    } catch (error) {
        console.error('Помилка завантаження бронювань:', error);
        toast.error('Не вдалося завантажити бронювання');
    } finally {
        isLoading.value = false;
    }
};

const {
    searchQuery, selectedStatuses, dateMode, customDate, currentPage, itemsPerPage,
    totalItems, totalPages, bookings, buildQueryParams, setPaginationData,
    statusNames, statusClasses
} = useBookingsManager(fetchBookings);

/**
 * Permanently delete a booking from the database.
 */
const deleteBooking = async (id) => {
    if (!confirm('Ви дійсно хочете ПОВНІСТЮ видалити це бронювання з бази даних? Цю дію неможливо скасувати.')) {
        return;
    }
    try {
        await axios.delete(`/bookings/${id}`);
        bookings.value = bookings.value.filter(b => b.id !== id);
        toast.success('Бронювання успішно видалено');
    } catch (error) {
        console.error('Помилка видалення бронювання:', error);
        toast.error('Не вдалося видалити бронювання.');
    }
};

/**
 * Update the admin note for a specific booking.
 */
const updateAdminNote = async (id, note) => {
    try {
        await axios.patch(`/bookings/${id}/note`, {admin_note: note});
        toast.success('Нотатку збережено');
    } catch (error) {
        toast.error('Не вдалося зберегти нотатку');
        console.error(error);
    }
};

onMounted(() => {
    fetchBookings();
});
</script>

<template>
    <div>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <h1 class="text-3xl font-black text-text">Управління бронюваннями</h1>
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <input v-model="searchQuery" type="text" placeholder="Пошук клієнта..."
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-white font-medium shadow-sm">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <button @click="fetchBookings"
                        class="p-3 bg-white text-primary shadow-sm border border-secondary hover:bg-secondary rounded-xl transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-4 mb-6 bg-white p-4 rounded-2xl border border-secondary shadow-sm inline-flex">
            <span class="text-sm font-bold text-gray-400 uppercase tracking-wider mr-2">Показати:</span>
            <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
                <input type="checkbox" value="pending" v-model="selectedStatuses"
                       class="w-4 h-4 text-yellow-500 rounded border-gray-300 focus:ring-yellow-500">
                <span class="text-sm font-bold text-yellow-700">Очікує</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
                <input type="checkbox" value="confirmed" v-model="selectedStatuses"
                       class="w-4 h-4 text-green-500 rounded border-gray-300 focus:ring-green-500">
                <span class="text-sm font-bold text-green-700">Підтверджено</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
                <input type="checkbox" value="finished" v-model="selectedStatuses"
                       class="w-4 h-4 text-blue-500 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm font-bold text-blue-700">Завершено</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
                <input type="checkbox" value="cancelled" v-model="selectedStatuses"
                       class="w-4 h-4 text-red-500 rounded border-gray-300 focus:ring-red-500">
                <span class="text-sm font-bold text-red-700">Скасовано</span>
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-4 bg-white p-4 rounded-2xl border border-secondary shadow-sm">
            <span class="text-sm font-bold text-gray-400 uppercase tracking-wider mr-2">Дата:</span>
            <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
                <input type="radio" value="all" v-model="dateMode"
                       class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                <span class="text-sm font-bold text-text">Всі дні</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
                <input type="radio" value="today" v-model="dateMode"
                       class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                <span class="text-sm font-bold text-text">За сьогодні</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
                <input type="radio" value="custom" v-model="dateMode"
                       class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                <span class="text-sm font-bold text-text">Обрати день:</span>
            </label>
            <input v-if="dateMode === 'custom'" type="date" v-model="customDate"
                   class="px-3 py-1.5 rounded-lg border border-secondary text-sm font-bold text-text outline-none focus:border-primary focus:ring-1 focus:ring-primary">
        </div>

        <div v-if="isLoading" class="text-center py-12 text-gray-500 animate-pulse font-bold">
            Завантаження списку бронювань...
        </div>

        <div v-else class="bg-white rounded-3xl shadow-sm border border-secondary overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-secondary/30 border-b border-secondary text-gray-500 font-bold uppercase text-xs tracking-wider">
                        <th class="p-4 pl-6">ID / Гра</th>
                        <th class="p-4">Клієнт</th>
                        <th class="p-4">Кімната</th>
                        <th class="p-4">Гравці / Сума</th>
                        <th class="p-4">Нотатка</th>
                        <th class="p-4">Статус</th>
                        <th class="p-4 text-center">Дії</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <tr v-for="booking in bookings" :key="booking.id"
                        class="hover:bg-gray-50 transition-colors">
                        <td class="p-4 pl-6">
                            <div class="font-bold text-text">#{{ booking.id }}</div>
                            <div class="text-sm text-primary font-bold mt-1">{{
                                    formatFullDate(booking.start_time)
                                }}
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="font-bold text-text">{{
                                    booking.guest_name || booking.user?.name || 'Клієнт'
                                }}
                            </div>
                            <div class="text-xs text-primary font-bold mt-1">
                                {{ booking.guest_phone || 'Телефон не вказано' }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ booking.guest_email || booking.user?.email || 'Email не вказано' }}
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="font-bold text-text">{{ booking.room?.name || 'ID: ' + booking.room_id }}</div>
                        </td>
                        <td class="p-4">
                            <div class="font-bold text-text">{{ booking.players_count }} гравців</div>
                            <div class="text-sm text-gray-500">{{ formatPrice(booking.total_price) }} ₴</div>
                        </td>
                        <td class="p-4 min-w-[200px] align-top">
              <textarea
                  v-model="booking.admin_note"
                  @blur="updateAdminNote(booking.id, booking.admin_note)"
                  placeholder="Нотатка адміна..."
                  class="w-full text-xs p-2 rounded-lg border border-secondary focus:ring-1 focus:ring-primary outline-none transition-colors bg-gray-50 hover:bg-white resize-none h-12"
              ></textarea>
                        </td>
                        <td class="p-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold tracking-wide"
                      :class="statusClasses[booking.status] || 'bg-gray-100 text-gray-700'">
                  {{ statusNames[booking.status] || booking.status }}
                </span>
                        </td>
                        <td class="p-4 text-center">
                            <button @click="deleteBooking(booking.id)"
                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Видалити">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <PaginationControls
                v-model:current-page="currentPage"
                :total-pages="totalPages"
                :total-items="totalItems"
                :items-per-page="itemsPerPage"
            />
        </div>

        <div v-if="totalItems === 0" class="p-8 text-center text-gray-500 font-medium">
            За вашим запитом нічого не знайдено.
        </div>
    </div>
</template>
