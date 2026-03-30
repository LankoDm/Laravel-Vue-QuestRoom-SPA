<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastStore } from '@/stores/toast';

const bookings = ref([]);
const selectedStatuses = ref(['pending', 'confirmed', 'finished', 'cancelled']);
const dateMode = ref('all');
const customDate = ref('');
const isLoading = ref(true);
const searchQuery = ref('');
const toast = useToastStore();
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
const getLocalYYYYMMDD = (dateObj) => {
  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day = String(dateObj.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};
const filteredBookings = computed(() => {
  let result = bookings.value.filter(b => selectedStatuses.value.includes(b.status));
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
    toast.error('Не вдалося видалити бронювання.');
  }
};
const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-700',
  confirmed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
  finished: 'bg-blue-100 text-blue-700' //змінив оскільки забув що finished
};
const statusNames = {
  pending: 'Очікує',
  confirmed: 'Підтверджено',
  cancelled: 'Скасовано',
  finished: 'Завершено' // тут також змінив
};

onMounted(() => {
  fetchBookings();
});
</script>

<template>
  <div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
      <h1 class="text-3xl font-black text-text">Управління бронюваннями</h1>

      <div class="relative w-full md:w-80">
        <input v-model="searchQuery" type="text" placeholder="Пошук (ім'я, телефон, email, ID)..."
               class="w-full pl-10 pr-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-white font-medium shadow-sm">
        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      </div>
    </div>
    <div class="flex flex-wrap gap-4 mb-6 bg-white p-4 rounded-2xl border border-secondary shadow-sm inline-flex">
      <span class="text-sm font-bold text-gray-400 uppercase tracking-wider mr-2">Показати:</span>

      <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
        <input type="checkbox" value="pending" v-model="selectedStatuses" class="w-4 h-4 text-yellow-500 rounded border-gray-300 focus:ring-yellow-500">
        <span class="text-sm font-bold text-yellow-700">Очікує</span>
      </label>

      <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
        <input type="checkbox" value="confirmed" v-model="selectedStatuses" class="w-4 h-4 text-green-500 rounded border-gray-300 focus:ring-green-500">
        <span class="text-sm font-bold text-green-700">Підтверджено</span>
      </label>

      <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
        <input type="checkbox" value="finished" v-model="selectedStatuses" class="w-4 h-4 text-blue-500 rounded border-gray-300 focus:ring-blue-500">
        <span class="text-sm font-bold text-blue-700">Завершено</span>
      </label>

      <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
        <input type="checkbox" value="cancelled" v-model="selectedStatuses" class="w-4 h-4 text-red-500 rounded border-gray-300 focus:ring-red-500">
        <span class="text-sm font-bold text-red-700">Скасовано</span>
      </label>
    </div>
    <div class="flex flex-wrap items-center gap-4 bg-white p-4 rounded-2xl border border-secondary shadow-sm">
      <span class="text-sm font-bold text-gray-400 uppercase tracking-wider mr-2">Дата:</span>

      <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
        <input type="radio" value="all" v-model="dateMode" class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
        <span class="text-sm font-bold text-text">Всі дні</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
        <input type="radio" value="today" v-model="dateMode" class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
        <span class="text-sm font-bold text-text">За сьогодні</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
        <input type="radio" value="custom" v-model="dateMode" class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
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
            <th class="p-4">Статус</th>
            <th class="p-4 text-center">Дії</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
          <tr v-for="booking in filteredBookings" :key="booking.id" class="hover:bg-gray-50 transition-colors">
            <td class="p-4 pl-6">
              <div class="font-bold text-text">#{{ booking.id }}</div>
              <div class="text-sm text-primary font-bold mt-1">{{ formatDate(booking.start_time) }}</div>
            </td>
            <td class="p-4">
              <div class="font-bold text-text">{{ booking.guest_name || booking.user?.name || 'Клієнт' }}</div>
              <div class="text-xs text-primary font-bold mt-1">{{ booking.guest_phone || 'Телефон не вказано' }}</div>
              <div class="text-xs text-gray-400 mt-0.5">{{ booking.guest_email || booking.user?.email || 'Email не вказано' }}</div>
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
              <button @click="deleteBooking(booking.id)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Видалити">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
      <div v-if="filteredBookings.length === 0" class="p-8 text-center text-gray-500 font-medium">
        За вашим запитом нічого не знайдено.
      </div>
    </div>
  </div>
</template>