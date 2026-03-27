<script setup>
import {ref, onMounted, computed} from 'vue';
import axios from 'axios';
import {Bar, Doughnut} from 'vue-chartjs';
import {
  Chart as ChartJS, Title, Tooltip, Legend, BarElement,
  CategoryScale, LinearScale, ArcElement
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);
const stats = ref(null);
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
const formatPrice = (price) => {
  return new Intl.NumberFormat('uk-UA').format(price || 0) + ' ₴';
};
const revenueChartData = computed(() => {
  if (!stats.value) return {labels: [], datasets: []};
  return {
    labels: stats.value.revenue_chart.map(item => item.date),
    datasets: [{
      label: 'Дохід',
      backgroundColor: '#a855f7',
      borderRadius: 6,
      data: stats.value.revenue_chart.map(item => item.revenue)
    }]
  };
});
const revenueChartOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: {legend: {display: false}},
  scales: {
    y: {beginAtZero: true, grid: {color: '#f3f4f6'}},
    x: {grid: {display: false}}
  }
};
const roomsChartData = computed(() => {
  if (!stats.value) return {labels: [], datasets: []};
  return {
    labels: stats.value.rooms_chart.map(item => item.name),
    datasets: [{
      backgroundColor: ['#a855f7', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899'],
      borderWidth: 0,
      data: stats.value.rooms_chart.map(item => item.count)
    }]
  };
});
const roomsChartOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: {legend: {position: 'right'}}
};
onMounted(() => {
  fetchStats();
});
</script>

<template>
  <div>
    <h1 class="text-3xl font-black text-text mb-8">Огляд системи</h1>

    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-4 gap-6 animate-pulse">
      <div v-for="i in 4" :key="i" class="bg-gray-100 h-32 rounded-3xl"></div>
    </div>
    <div v-else class="space-y-8 animate-fade-in">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
            class="bg-white p-6 rounded-3xl shadow-sm border border-secondary flex flex-col justify-center transition-transform hover:-translate-y-1">
          <span class="text-gray-500 font-medium mb-2 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path
                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            Всього кімнат
          </span>
          <span class="text-4xl font-black text-text">{{ stats.total_rooms }}</span>
        </div>
        <div
            class="bg-primary p-6 rounded-3xl shadow-md border border-purple-400 flex flex-col justify-center transition-transform hover:-translate-y-1 text-white">
          <span class="text-purple-100 font-medium mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round"
                                                                                             stroke-linejoin="round"
                                                                                             stroke-width="2"
                                                                                             d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"></path></svg>
            Ігор на сьогодні
          </span>
          <span class="text-4xl font-black">{{ stats.bookings_today }}</span>
        </div>
        <div
            class="bg-white p-6 rounded-3xl shadow-sm border border-secondary flex flex-col justify-center transition-transform hover:-translate-y-1">
          <span class="text-gray-500 font-medium mb-2 flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path
                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
            Нові відгуки
          </span>
          <span class="text-4xl font-black text-text">{{ stats.new_reviews }}</span>
        </div>
        <div
            class="bg-white p-6 rounded-3xl shadow-sm border border-secondary flex flex-col justify-center transition-transform hover:-translate-y-1">
          <span class="text-gray-500 font-medium mb-2 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path
                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Загальний дохід
          </span>
          <span class="text-4xl font-black text-green-600">{{ formatPrice(stats.total_revenue) }}</span>
        </div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary lg:col-span-2">
          <h3 class="text-lg font-bold text-text mb-6">Дохід за останні 7 днів</h3>
          <div class="h-72">
            <Bar :data="revenueChartData" :options="revenueChartOptions"/>
          </div>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary lg:col-span-1">
          <h3 class="text-lg font-bold text-text mb-6">Популярність кімнат</h3>
          <div class="h-72 flex justify-center">
            <Doughnut :data="roomsChartData" :options="roomsChartOptions"/>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-fade-in {
  animation: fadeIn 0.4s ease-out forwards;
}
</style>