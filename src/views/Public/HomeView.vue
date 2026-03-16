<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const rooms = ref([]);
const isLoading = ref(true);
const router = useRouter();
const fetchRooms = async () => {
  try {
    const response = await axios.get('http://localhost:8080/api/rooms');
    rooms.value = response.data.data;
  } catch (error) {
    console.error('Помилка завантаження даних:', error);
  } finally {
    isLoading.value = false;
  }
};
const openRoom = (slug) => {
  router.push({ name: 'room.show', params: { slug: slug } });
};

onMounted(() => {
  fetchRooms();
});
</script>

<template>
  <div class="py-10 px-6">
    <div class="max-w-6xl mx-auto">

      <h1 class="text-4xl font-bold text-primary mb-8 text-center">
        Каталог квест-кімнат
      </h1>

      <div v-if="isLoading" class="text-center text-xl text-primary animate-pulse">
        Завантаження даних
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
            v-for="room in rooms"
            :key="room.id"
            @click="openRoom(room.slug)"
            class="bg-white rounded-2xl shadow-sm border border-secondary hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden flex flex-col">

          <div class="h-56 w-full bg-gray-100 relative">
            <img
                v-if="room.image_path"
                :src="room.image_path"
                :alt="room.name"
                class="w-full h-full object-cover"/>
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
              Немає зображення
            </div>
            <div class="absolute top-4 left-4 bg-primary text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
              {{ room.difficulty }}
            </div>
          </div>

          <div class="p-6 flex flex-col flex-grow">

            <div class="flex justify-between items-start mb-3">
              <h3 class="text-xl font-bold text-text leading-tight pr-4">
                {{ room.name }}
              </h3>

              <div class="flex items-center space-x-1 bg-secondary px-2 py-1 rounded-lg shrink-0">
                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-sm font-bold text-text">
                  {{ room.reviews_avg_rating ? Number(room.reviews_avg_rating).toFixed(1) : '—' }}
                </span>
              </div>
            </div>
            <p class="text-gray-500 text-sm line-clamp-3">
              {{ room.description }}
            </p>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>