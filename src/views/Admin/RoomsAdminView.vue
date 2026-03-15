<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
// import { useRouter } from 'vue-router';

const rooms = ref([]);
const isLoading = ref(true);
const fetchRooms = async () => {
  try {
    const response = await axios.get('http://localhost:8080/api/rooms?show_all=true');
    rooms.value = response.data.data;
  } catch (error) {
    console.error('Помилка завантаження кімнат:', error);
  } finally {
    isLoading.value = false;
  }
};
const toggleStatus = async (room) => {
  const originalStatus = room.is_active;
  room.is_active = room.is_active ? 0 : 1;

  try {
    await axios.patch(`http://localhost:8080/api/rooms/${room.id}/toggle-status`, {
      is_active: room.is_active
    });
  } catch (error) {
    console.error('Помилка оновлення статусу:', error);
    room.is_active = originalStatus;
    alert('Не вдалося змінити статус. Перевірте підключення або бекенд.');
  }
};
const deleteRoom = async (id) => {
  if (!confirm('Ви дійсно хочете видалити цю кімнату? Цю дію неможливо скасувати.')) {
    return;
  }

  try {
    await axios.delete(`http://localhost:8080/api/rooms/${id}`);
    rooms.value = rooms.value.filter(room => room.id !== id);
  } catch (error) {
    console.error('Помилка видалення кімнати:', error);
    alert('Не вдалося видалити кімнату. Можливо, до неї прив\'язані бронювання.');
  }
};
const formatPrice = (price) => price ? price / 100 : 0;

onMounted(() => {
  fetchRooms();
});
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-black text-text">Управління кімнатами</h1>
      <button class="bg-primary hover:bg-purple-500 text-white px-6 py-3 rounded-xl font-bold shadow-md transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Додати кімнату
      </button>
    </div>

    <div v-if="isLoading" class="text-center py-12 text-gray-500 animate-pulse">
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
                <span>Рейтинг: {{ room.reviews_avg_rating ? Number(room.reviews_avg_rating).toFixed(1) : '—' }}</span>
                <span>Відгуків: {{ room.reviews_count || 0 }}</span>
              </div>
            </td>
            <td class="p-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                      :class="room.difficulty === 'hard' ? 'bg-red-100 text-red-600' : (room.difficulty === 'medium' ? 'bg-yellow-100 text-yellow-600' : 'bg-green-100 text-green-600')">
                  {{ room.difficulty }}
                </span>
            </td>
            <td class="p-4">
              <div class="font-bold text-text">{{ formatPrice(room.weekday_price) }} ₴</div>
              <div class="text-xs text-gray-500">{{ formatPrice(room.weekend_price) }} ₴</div>
            </td>

            <td class="p-4 text-center align-middle">
              <button
                  @click="toggleStatus(room)"
                  :class="room.is_active ? 'bg-primary' : 'bg-gray-200'"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 cursor-pointer"
                  :title="room.is_active ? 'Вимкнути кімнату' : 'Увімкнути кімнату'"
              >
                  <span
                      :class="room.is_active ? 'translate-x-6' : 'translate-x-1'"
                      class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-300 shadow-sm"
                  />
              </button>
            </td>

            <td class="p-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Редагувати">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </button>
                <button @click="deleteRoom(room.id)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Видалити">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>