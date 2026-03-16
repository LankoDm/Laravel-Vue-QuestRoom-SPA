<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const bookings = ref([]);
const isLoading = ref(true);
const fetchBookings = async () => {
  try {
    const response = await axios.get('http://localhost:8080/api/bookings');
    let data = response.data.data || response.data;
    bookings.value = data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  } catch (error) {
    console.error('Помилка завантаження бронювань:', error);
  } finally {
    isLoading.value = false;
  }
};
const formatPrice = (price) => price ? price / 100 : 0;
const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleString('uk-UA', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};
const deleteBooking = async (id) => {
  if (!confirm('Ви дійсно хочете ПОВНІСТЮ видалити це бронювання з бази даних? Цю дію неможливо скасувати.')) {
    return;
  }
  try {
    await axios.delete(`http://localhost:8080/api/bookings/${id}`);
    bookings.value = bookings.value.filter(b => b.id !== id);
  } catch (error) {
    console.error('Помилка видалення бронювання:', error);
    alert('Не вдалося видалити бронювання.');
  }
};
const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-700',
  confirmed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
  completed: 'bg-blue-100 text-blue-700'
};
const statusNames = {
  pending: 'Очікує',
  confirmed: 'Підтверджено',
  cancelled: 'Скасовано',
  completed: 'Завершено'
};

onMounted(() => {
  fetchBookings();
});
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-black text-text">Управління бронюваннями</h1>
    </div>
    <div v-if="isLoading" class="text-center py-12 text-gray-500 animate-pulse">
      Завантаження списку бронювань
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
            <th class="p-4">Статус</th>
            <th class="p-4 text-center">Дії</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
          <tr v-for="booking in bookings" :key="booking.id" class="hover:bg-gray-50 transition-colors">

            <td class="p-4 pl-6">
              <div class="font-bold text-text">#{{ booking.id }}</div>
              <div class="text-sm text-primary font-bold mt-1">{{ formatDate(booking.start_time) }}</div>
            </td>
            <td class="p-4">
              <div class="font-bold text-text">{{ booking.user?.name || 'ID: ' + booking.user_id }}</div>
              <div class="text-xs text-gray-400 mt-1">{{ booking.user?.email || '—' }}</div>
            </td>
            <td class="p-4">
              <div class="font-bold text-text">{{ booking.room?.name || 'ID: ' + booking.room_id }}</div>
            </td>
            <td class="p-4">
              <div class="font-bold text-text">{{ booking.players_count }} гравців</div>
              <div class="text-sm text-gray-500">{{ formatPrice(booking.total_price) }} ₴</div>
            </td>

            <td class="p-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold tracking-wide"
                      :class="statusClasses[booking.status] || 'bg-gray-100 text-gray-700'">
                  {{ statusNames[booking.status] || booking.status }}
                </span>
            </td>
            <td class="p-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="deleteBooking(booking.id)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Видалити бронювання повністю">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
      <div v-if="bookings.length === 0" class="p-8 text-center text-gray-500">
        Немає жодного бронювання.
      </div>
    </div>
  </div>
</template>