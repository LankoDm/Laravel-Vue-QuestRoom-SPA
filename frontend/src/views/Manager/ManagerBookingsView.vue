<script setup>
import {ref, onMounted} from 'vue';
import axios from 'axios';
import {useToastStore} from '@/stores/toast';
import {useFormatters} from '@/composables/useFormatters';
import {useBookingsManager} from '@/composables/useBookingsManager';
import PaginationControls from "@/components/UI/PaginationControls.vue";

const toast = useToastStore();
const isLoading = ref(true);
const bookings = ref([]);
const newBookingsQueue = ref([]);
// Initialize Composables
const {formatPrice, formatDateTime, formatTime} = useFormatters();
const {
    searchQuery, selectedStatuses, dateMode, customDate, currentPage, itemsPerPage,
    filteredBookings, paginatedBookings, totalPages, statusNames, statusClasses, paymentNames
} = useBookingsManager(bookings);

/**
 * Fetch all bookings from the server.
 */
const fetchBookings = async () => {
    try {
        const response = await axios.get('http://localhost:8080/api/bookings');
        let data = response.data.data || response.data;
        // Sort descending by creation date
        bookings.value = data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    } catch (error) {
        console.error('Error fetching bookings:', error);
    } finally {
        isLoading.value = false;
    }
};

/**
 * Merge newly received WebSocket bookings into the main list.
 */
const applyNewBookings = () => {
    bookings.value = [...newBookingsQueue.value, ...bookings.value];
    newBookingsQueue.value = [];
};

/**
 * Mark booking as finished.
 * @param {number} id - Booking ID
 */
const finishBooking = async (id) => {
    if (!confirm('Позначити це бронювання як завершене?')) return;
    try {
        await axios.patch(`http://localhost:8080/api/bookings/${id}/finish`);
        const b = bookings.value.find(item => item.id === id);
        if (b) b.status = 'finished';
    } catch (error) {
        toast.error('Не вдалося змінити статус на завершено');
    }
};

/**
 * Confirm a pending booking.
 * @param {number} id - Booking ID
 */
const confirmBooking = async (id) => {
    if (!confirm('Підтвердити замовлення?')) return;
    try {
        await axios.patch(`http://localhost:8080/api/bookings/${id}/confirm`);
        const b = bookings.value.find(item => item.id === id);
        if (b) b.status = 'confirmed';
    } catch (error) {
        toast.error('Не вдалося підтвердити');
    }
};

/**
 * Cancel a booking.
 * @param {number} id - Booking ID
 */
const cancelBooking = async (id) => {
    if (!confirm('Скасувати це бронювання?')) return;
    try {
        await axios.patch(`http://localhost:8080/api/bookings/${id}/cancel`);
        const b = bookings.value.find(item => item.id === id);
        if (b) b.status = 'cancelled';
    } catch (error) {
        toast.error('Не вдалося скасувати');
    }
};
onMounted(() => {
    fetchBookings();

    //Initialize WebSockets listening for booking events
    if (window.Echo) {
        window.Echo.channel('manager-channel')
            .listen('.booking.created', (e) => {
                const index = bookings.value.findIndex(b => b.id === e.booking.id);
                if (index !== -1) {
                    bookings.value.splice(index, 1, {...bookings.value[index], ...e.booking});
                } else {
                    newBookingsQueue.value.push(e.booking);
                }
            })
            .listen('.booking.updated', (e) => {
                const index = bookings.value.findIndex(b => b.id === e.booking.id);
                if (index !== -1) {
                    bookings.value.splice(index, 1, {...bookings.value[index], ...e.booking});
                }
            });
    }
});
</script>

<template>
    <div>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <h1 class="text-3xl font-black text-text">Актуальні бронювання</h1>
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
        <div v-if="newBookingsQueue.length > 0" class="mb-4 flex justify-center">
            <button @click="applyNewBookings"
                    class="flex items-center gap-2 bg-blue-50 text-blue-600 border border-blue-200 px-6 py-2.5 rounded-full font-bold shadow-sm hover:bg-blue-600 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Завантажити нові бронювання ({{ newBookingsQueue.length }})
            </button>
        </div>
        <div v-if="isLoading" class="py-20 text-center animate-pulse text-gray-400 font-bold">Завантаження</div>

        <div v-else class="bg-white rounded-3xl shadow-sm border border-secondary overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-secondary/30 border-b border-secondary text-gray-500 font-bold text-xs uppercase">
                    <tr>
                        <th class="p-4 pl-6">Час / ID</th>
                        <th class="p-4">Клієнт</th>
                        <th class="p-4">Кімната</th>
                        <th class="p-4">Гравці / Оплата</th>
                        <th class="p-4">Статус</th>
                        <th class="p-4 text-center">Дії</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <template v-for="b in paginatedBookings" :key="b.id">
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 pl-6 min-w-[140px]">
                                <div class="text-xs text-gray-500 font-bold mb-0.5">{{
                                        formatDateTime(b.start_time)
                                    }}
                                </div>
                                <div class="font-black text-primary text-sm">{{ formatTime(b.start_time) }} - {{
                                        formatTime(b.end_time)
                                    }}
                                </div>
                                <div class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider">#{{ b.id }}</div>
                            </td>
                            <td class="p-4 font-bold text-text">
                                {{ b.guest_name }}
                                <div class="text-xs text-gray-500 font-medium">{{ b.guest_phone }}</div>
                            </td>
                            <td class="p-4 font-bold">{{ b.room?.name || 'Кімната #' + b.room_id }}</td>
                            <td class="p-4">
                                <div class="font-bold text-text">{{ b.players_count }} гравців</div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500 font-bold">{{
                                            formatPrice(b.total_price)
                                        }} ₴</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-black uppercase tracking-wider"
                                          :class="b.payment_method === 'card' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100'">
                  {{ paymentNames[b.payment_method] || b.payment_method || 'Не вказано' }}
                </span>
                                </div>
                            </td>
                            <td class="p-4">
              <span class="px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider"
                    :class="statusClasses[b.status]">
                {{ statusNames[b.status] || b.status }}
              </span>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button v-if="b.status === 'pending'" @click="confirmBooking(b.id)"
                                            class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm"
                                            title="Підтвердити">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button v-if="b.status === 'confirmed'" @click="finishBooking(b.id)"
                                            class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                            title="Позначити як завершене">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                    <button v-if="b.status !== 'cancelled' && b.status !== 'finished'"
                                            @click="cancelBooking(b.id)"
                                            class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                            title="Скасувати">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="b.comment" class="bg-yellow-50/50">
                            <td colspan="6" class="p-3 pl-6 border-t border-dashed border-yellow-200">
                                <div class="flex items-start gap-2 text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-yellow-500 shrink-0 mt-0.5" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                    </svg>
                                    <div>
                                        <span
                                            class="font-bold text-yellow-700 uppercase text-[10px] tracking-wider mr-2">Коментар клієнта:</span>
                                        <span class="italic text-yellow-800">"{{ b.comment }}"</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
                <PaginationControls
                    v-model:current-page="currentPage"
                    :total-pages="totalPages"
                    :total-items="filteredBookings.length"
                    :items-per-page="itemsPerPage"
                />
                <div v-if="filteredBookings.length === 0" class="p-8 text-center text-gray-500 font-medium">
                    Бронювань не знайдено.
                </div>
            </div>
        </div>
    </div>
</template>
