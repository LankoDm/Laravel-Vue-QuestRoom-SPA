<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({
  total_rooms: 0,
  bookings_today: 0,
  new_reviews: 0
});
const isLoading = ref(true);
const fetchStats = async () => {
  try {
    const response = await axios.get('http://localhost:8080/api/admin/stats');
    stats.value = response.data;
  } catch (error) {
    console.error('Помилка завантаження статистики:', error);
  } finally {
    isLoading.value = false;
  }
};
onMounted(() => {
  fetchStats();
});
</script>

<template>
  <div>
    <h1 class="text-3xl font-black text-text mb-8">Огляд системи</h1>

    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
      <div class="bg-gray-100 h-32 rounded-3xl"></div>
      <div class="bg-gray-100 h-32 rounded-3xl"></div>
      <div class="bg-gray-100 h-32 rounded-3xl"></div>
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary flex flex-col justify-center transition-transform hover:-translate-y-1">
        <span class="text-gray-500 font-medium mb-2 flex items-center gap-2">
          <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
          Всього кімнат
        </span>
        <span class="text-5xl font-black text-text">{{ stats.total_rooms }}</span>
      </div>
      <div class="bg-primary p-6 rounded-3xl shadow-md border border-purple-400 flex flex-col justify-center transition-transform hover:-translate-y-1 text-white">
        <span class="text-purple-100 font-medium mb-2 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"></path></svg>
          Ігор на сьогодні
        </span>
        <span class="text-5xl font-black">{{ stats.bookings_today }}</span>
      </div>
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary flex flex-col justify-center transition-transform hover:-translate-y-1">
        <span class="text-gray-500 font-medium mb-2 flex items-center gap-2">
          <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
          Нові відгуки
        </span>
        <span class="text-5xl font-black text-text">{{ stats.new_reviews }}</span>
      </div>

    </div>
  </div>
</template>