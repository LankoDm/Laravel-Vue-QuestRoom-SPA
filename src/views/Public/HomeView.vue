<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const currentPage = ref(1);
const lastPage = ref(1);
const rooms = ref([]);
const isLoading = ref(true);
const router = useRouter();
//створив об'єкт який зберігає значення всіх фільтрів
const filters = ref({
  search: '',
  difficulty: '',
  players_count: '',
  sort: ''
});
let searchTimeout = null;
const fetchRooms = async (page = 1) => {
  isLoading.value = true;
  try {
    //формую об'єкт параметрів для запиту і додаємо сторінку
    const params = { page };
    //перевірки щоб додавати тільки ті фільтри, які користувач заповнив
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.difficulty) params.difficulty = filters.value.difficulty;
    if (filters.value.players_count) params.players_count = filters.value.players_count;
    if (filters.value.sort) params.sort = filters.value.sort;
    //запит с параметрами
    const response = await axios.get('http://localhost:8080/api/rooms', { params });
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
    console.error('Помилка завантаження даних:', error);
  } finally {
    isLoading.value = false;
  }
};
const changePage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    fetchRooms(page);
    // Прокручуємо екран трохи вгору до списку квестів
    window.scrollTo({ top: 400, behavior: 'smooth' });
  }
};
//функція яка викликається, коли людина натискає клавішу в полі пошуку
const onInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchRooms(1);
  }, 500);
};
//функція для очищення всіх полів і для завантаження повного списку кімнат
const resetFilters = () => {
  filters.value = { search: '', difficulty: '', players_count: '', sort: '' };
  fetchRooms(1);
};
const openRoom = (slug) => {
  router.push({ name: 'room.show', params: { slug: slug } });
};
//далі додам можливість завантаження декількох фото, зараз тільки одна і тут беперться перша
const getFirstImage = (imagePath) => {
  if (!imagePath) return null;
  if (Array.isArray(imagePath)) return imagePath[0];
  if (typeof imagePath === 'string' && imagePath.startsWith('[')) {
    try { return JSON.parse(imagePath)[0]; } catch (e) { return imagePath; }
  }
  return imagePath;
};

onMounted(() => {
  fetchRooms();
});
</script>

<template>
  <div>
    <div class="bg-primary text-white py-20 px-6 relative overflow-hidden">
      <div class="absolute inset-0 bg-black/20 z-10"></div>
      <div class="max-w-6xl mx-auto relative z-20 text-center">
        <h1 class="text-5xl md:text-6xl font-black mb-6 tracking-tight">
          Вирвись із реальності
        </h1>
        <!--дописати інформативне щось-->
        <p class="text-xl md:text-2xl font-medium opacity-90 max-w-2xl mx-auto mb-10">
          ...
        </p>
      </div>
      <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl z-0"></div>
      <div class="absolute top-10 -right-10 w-48 h-48 bg-purple-500/40 rounded-full blur-3xl z-0"></div>
    </div>

    <div class="py-16 px-6 max-w-6xl mx-auto">

      <div class="mb-10">
        <h2 class="text-3xl font-black text-text mb-2">Наші квести</h2>
        <p class="text-gray-500 font-medium">Оберіть свою наступну пригоду</p>
      </div>

      <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary mb-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">

          <div class="lg:col-span-1">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Пошук</label>
            <input v-model="filters.search" @input="onInput" type="text" placeholder="Назва кімнати"
                   class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 text-text font-medium">
          </div>

          <div class="lg:col-span-1">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Для скількох?</label>
            <input v-model="filters.players_count" @input="onInput" type="number" min="1" placeholder="К-ть людей"
                   class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 text-text font-medium">
          </div>

          <div class="lg:col-span-1">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Складність</label>
            <div class="relative">
              <select v-model="filters.difficulty" @change="fetchRooms(1)"
                      class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 appearance-none font-medium cursor-pointer">
                <option value="">Всі складності</option>
                <option value="easy">Легкий</option>
                <option value="medium">Середній</option>
                <option value="hard">Складний</option>
                <option value="ultra hard">Експерт</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
              </div>
            </div>
          </div>

          <div class="lg:col-span-1">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Сортування</label>
            <div class="relative">
              <select v-model="filters.sort" @change="fetchRooms(1)"
                      class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 appearance-none font-medium cursor-pointer">
                <option value="">Нові</option>
                <option value="rating_desc">Рейтинг (Високий)</option>
                <option value="rating_asc">Рейтинг (Низький)</option>
                <option value="difficulty_asc">Спочатку легкі</option>
                <option value="difficulty_desc">Спочатку складні</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
              </div>
            </div>
          </div>

          <div class="lg:col-span-1 flex items-end">
            <button @click="resetFilters"
                    class="w-full h-[48px] bg-red-50 text-red-600 font-bold rounded-xl hover:bg-red-600 hover:text-white transition-colors cursor-pointer flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              Скинути
            </button>
          </div>

        </div>
      </div>
      <div v-if="isLoading" class="text-center text-xl text-primary animate-pulse py-20 font-bold">
        Завантаження квестів
      </div>

      <div v-else-if="rooms.length === 0" class="text-center py-20 text-gray-500 border border-secondary rounded-3xl font-medium">
        За вашими критеріями не знайдено жодної кімнати
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
            v-for="room in rooms"
            :key="room.id"
            @click="openRoom(room.slug)"
            class="bg-white rounded-3xl shadow-sm border border-secondary hover:shadow-xl hover:-translate-y-2 transition-all duration-300 cursor-pointer overflow-hidden flex flex-col group">

          <div class="h-56 w-full bg-gray-100 relative overflow-hidden">
            <img
                v-if="getFirstImage(room.image_path)"
                :src="getFirstImage(room.image_path)"
                :alt="room.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
              Немає зображення
            </div>

            <div class="absolute top-4 left-4 bg-primary text-white px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider shadow-lg">
              {{ room.difficulty }}
            </div>
          </div>

          <div class="p-6 flex flex-col flex-grow">
            <div class="flex justify-between items-start mb-3">
              <h3 class="text-2xl font-black text-text leading-tight pr-4 group-hover:text-primary transition-colors">
                {{ room.name }}
              </h3>

              <div class="flex items-center space-x-1 bg-yellow-50 px-2 py-1 rounded-lg shrink-0 border border-yellow-100">
                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-sm font-black text-yellow-700">
                  {{ room.reviews_avg_rating ? Number(room.reviews_avg_rating).toFixed(1) : 'Новий' }}
                </span>
              </div>
            </div>

            <div class="flex gap-4 mb-4 text-sm font-bold text-gray-500">
              <div class="flex items-center gap-1">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ room.duration_minutes }} хв
              </div>
              <div class="flex items-center gap-1">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                {{ room.min_players }}-{{ room.max_players }}
              </div>
            </div>

            <p class="text-gray-500 text-sm line-clamp-3 mt-auto">
              {{ room.description }}
            </p>
          </div>
        </div>
      </div>
      <div v-if="lastPage > 1" class="mt-12 flex justify-center items-center gap-2">
        <button
            @click="changePage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-4 py-2 rounded-xl font-bold transition-all border"
            :class="currentPage === 1 ? 'border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:border-primary hover:bg-primary/5 cursor-pointer'">
          Назад
        </button>
        <button
            v-for="page in lastPage"
            :key="page"
            @click="changePage(page)"
            class="w-10 h-10 rounded-xl font-bold transition-all flex items-center justify-center cursor-pointer border"
            :class="page === currentPage ? 'bg-primary text-white border-primary shadow-md' : 'bg-white text-gray-600 border-secondary hover:border-primary hover:text-primary'">
          {{ page }}
        </button>
        <button
            @click="changePage(currentPage + 1)"
            :disabled="currentPage === lastPage"
            class="px-4 py-2 rounded-xl font-bold transition-all border"
            :class="currentPage === lastPage ? 'border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed' : 'border-secondary text-primary bg-white hover:border-primary hover:bg-primary/5 cursor-pointer'">
          Вперед
        </button>
      </div>
    </div>
  </div>
</template>