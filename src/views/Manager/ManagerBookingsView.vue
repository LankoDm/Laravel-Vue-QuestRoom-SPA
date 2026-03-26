<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const bookings = ref([]);
const isLoading = ref(true);
const searchQuery = ref('');
const fetchBookings = async () => {
  try {
    const response = await axios.get('http://localhost:8080/api/bookings');
    let data = response.data.data || response.data;
    bookings.value = data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  } catch (error) {
    console.error('Помилка завантаження:', error);
  } finally {
    isLoading.value = false;
  }
};
const filteredBookings = computed(() => {
  if (!searchQuery.value) return bookings.value;
  const query = searchQuery.value.toLowerCase();
  const queryDigits = query.replace(/\D/g, '');
  return bookings.value.filter(b => {
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
});
const formatPrice = (price) => price ? price / 100 : 0;
const formatDate = (dateString) => {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString('uk-UA', {
    day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
  });
};
const confirmBooking = async (id) => {
  try {
    await axios.patch(`http://localhost:8080/api/bookings/${id}/confirm`);
    const b = bookings.value.find(item => item.id === id);
    if (b) b.status = 'confirmed';
  } catch (error) {
    alert('Не вдалося підтвердити');
  }
};
const cancelBooking = async (id) => {
  if (!confirm('Скасувати це бронювання?')) return;
  try {
    await axios.patch(`http://localhost:8080/api/bookings/${id}/cancel`);
    const b = bookings.value.find(item => item.id === id);
    if (b) b.status = 'cancelled';
  } catch (error) {
    alert('Не вдалося скасувати');
  }
};
const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-700',
  confirmed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
  finished: 'bg-blue-100 text-blue-700'
};
onMounted(() => {
  fetchBookings();-
      window.Echo.channel('manager-channel')
          .listen('.booking.created', (e) => {
            const index = bookings.value.findIndex(b => b.id === e.booking.id);
            if (index !== -1) {
              bookings.value[index] = e.booking;
            } else {
              bookings.value.unshift(e.booking);
            }
          });
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
          <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <button @click="fetchBookings" class="p-3 bg-white text-primary shadow-sm border border-secondary hover:bg-secondary rounded-xl transition-colors shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </button>
      </div>
    </div>
    <div v-if="isLoading" class="py-20 text-center animate-pulse text-gray-400 font-bold">Завантаження</div>

    <div v-else class="bg-white rounded-3xl shadow-sm border border-secondary overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-secondary/30 border-b border-secondary text-gray-500 font-bold text-xs uppercase">
        <tr>
          <th class="p-4 pl-6">Час / ID</th>
          <th class="p-4">Клієнт</th>
          <th class="p-4">Кімната</th>
          <th class="p-4">Гравці / Сума</th>
          <th class="p-4">Статус</th>
          <th class="p-4 text-center">Дії</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
        <tr v-for="b in filteredBookings" :key="b.id" class="hover:bg-gray-50">
          <td class="p-4 pl-6">
            <div class="font-black text-primary">{{ formatDate(b.start_time) }}</div>
            <div class="text-xs text-gray-400">#{{ b.id }}</div>
          </td>
          <td class="p-4 font-bold text-text">
            {{ b.guest_name }}
            <div class="text-xs text-gray-500 font-medium">{{ b.guest_phone }}</div>
          </td>
          <td class="p-4 font-bold">{{ b.room?.name || 'Кімната #' + b.room_id }}</td>
          <td class="p-4">
            <div class="font-bold">{{ b.players_count }} гравців</div>
            <div class="text-xs text-gray-400">{{ formatPrice(b.total_price) }} ₴</div>
          </td>
          <td class="p-4">
              <span class="px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider" :class="statusClasses[b.status]">
                {{ b.status }}
              </span>
          </td>
          <td class="p-4 text-center">
            <div class="flex justify-center gap-2">
              <button v-if="b.status === 'pending'" @click="confirmBooking(b.id)" class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              </button>
              <button v-if="b.status !== 'cancelled' && b.status !== 'finished'" @click="cancelBooking(b.id)" class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
      <div v-if="filteredBookings.length === 0" class="p-8 text-center text-gray-500 font-medium">
        Бронювань не знайдено.
      </div>
    </div>
  </div>
</template>