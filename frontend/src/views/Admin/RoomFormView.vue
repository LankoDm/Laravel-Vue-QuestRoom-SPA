<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const isEditMode = computed(() => !!route.params.id);
const isLoading = ref(isEditMode.value);
const isSaving = ref(false);
const errorMessage = ref('');
const validationErrors = ref({});
const form = ref({
  name: '',
  slug: '',
  description: '',
  difficulty: 'medium',
  age: '12+',
  hint_phrase: '',
  genre: '',
  min_players: 2,
  max_players: 4,
  weekday_price: null,
  weekend_price: null,
  duration_minutes: 60,
  is_active: 1
});
const imageFiles = ref([]);
const imagePreviews = ref([]);
const handleImageUpload = (event) => {
  const files = Array.from(event.target.files);
  if (files.length > 0) {
    imageFiles.value = files;
    imagePreviews.value = files.map(file => URL.createObjectURL(file));
  }
};
const fetchRoom = async () => {
  if (!isEditMode.value) return;
  try {
    const response = await axios.get(`http://localhost:8080/api/rooms/${route.params.id}`);
    const data = response.data.data || response.data;
    form.value = {
      name: data.name,
      slug: data.slug,
      description: data.description,
      difficulty: data.difficulty,
      age: data.age || '12+',
      genre: data.genre || '',
      hint_phrase: data.hint_phrase || '',
      min_players: data.min_players,
      max_players: data.max_players,
      duration_minutes: data.duration_minutes,
      is_active: data.is_active,
      weekday_price: data.weekday_price / 100,
      weekend_price: data.weekend_price / 100,
    };
    if (data.image_path) {
      let parsedImages = [];
      if (Array.isArray(data.image_path)) {
        parsedImages = data.image_path;
      } else if (typeof data.image_path === 'string') {
        try {
          parsedImages = JSON.parse(data.image_path);
        } catch (e) {
          parsedImages = [data.image_path];
        }
      }
      imagePreviews.value = parsedImages;
    }
  } catch (error) {
    errorMessage.value = 'Не вдалося завантажити дані кімнати';
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};
const saveRoom = async () => {
  isSaving.value = true;
  errorMessage.value = '';
  validationErrors.value = {};
  try {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('slug', form.value.slug);
    formData.append('description', form.value.description);
    formData.append('difficulty', form.value.difficulty);
    formData.append('age', form.value.age);
    formData.append('genre', form.value.genre || '');
    formData.append('hint_phrase', form.value.hint_phrase || '');
    formData.append('min_players', form.value.min_players);
    formData.append('max_players', form.value.max_players);
    formData.append('duration_minutes', form.value.duration_minutes);
    formData.append('is_active', form.value.is_active ? 1 : 0);
    formData.append('weekday_price', Math.round(form.value.weekday_price * 100));
    formData.append('weekend_price', Math.round(form.value.weekend_price * 100));
    if (imageFiles.value.length > 0) {
      imageFiles.value.forEach(file => {
        formData.append('image_path[]', file);
      });
    }
    if (isEditMode.value) {
      formData.append('_method', 'PUT');
      await axios.post(`http://localhost:8080/api/rooms/${route.params.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      await axios.post('http://localhost:8080/api/rooms', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }
    router.push({ name: 'admin.rooms' });
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors;
      errorMessage.value = 'Будь ласка, виправте помилки у формі нижче.';
    } else {
      errorMessage.value = 'Сталася помилка при збереженні. Перевірте консоль.';
    }
  } finally {
    isSaving.value = false;
  }
};

onMounted(() => {
  fetchRoom();
});
</script>

<template>
  <div class="max-w-4xl mx-auto py-8">

    <div class="flex items-center gap-4 mb-8">
      <RouterLink :to="{ name: 'admin.rooms' }" class="p-2 bg-white rounded-xl shadow-sm border border-secondary text-gray-500 hover:text-primary transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      </RouterLink>
      <h1 class="text-3xl font-black text-text">
        {{ isEditMode ? 'Редагування кімнати' : 'Створення нової кімнати' }}
      </h1>
    </div>
    <div v-if="isLoading" class="text-center py-12 text-gray-500 animate-pulse">
      Завантаження даних
    </div>
    <form v-else @submit.prevent="saveRoom" class="space-y-8">
      <div v-if="errorMessage" class="p-4 bg-red-50 text-red-600 rounded-xl border border-red-200 font-bold flex items-center gap-2 shadow-sm">
        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        {{ errorMessage }}
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2 space-y-6 bg-white p-8 rounded-3xl shadow-sm border border-secondary">
          <h2 class="text-xl font-bold text-text mb-4">Основна інформація</h2>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Назва кімнати *</label>
            <input v-model="form.name" type="text" required
                   class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                   :class="validationErrors.name ? 'border-red-500 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
            <span v-if="validationErrors.name" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.name[0] }}</span>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Slug (URL) *</label>
            <input v-model="form.slug" type="text" required
                   class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                   :class="validationErrors.slug ? 'border-red-500 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary text-gray-500'">
            <span v-if="validationErrors.slug" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.slug[0] }}</span>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Опис квесту *</label>
            <textarea v-model="form.description" required rows="5"
                      class="w-full px-4 py-3 rounded-xl border focus:outline-none resize-none transition-colors"
                      :class="validationErrors.description ? 'border-red-500 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'"></textarea>
            <span v-if="validationErrors.description" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.description[0] }}</span>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Секретна фраза-підказка</label>
            <input v-model="form.hint_phrase" type="text" placeholder="Наприклад: 'Ключ знаходиться під килимком'"
                   class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                   :class="validationErrors.hint_phrase ? 'border-red-500 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
            <span v-if="validationErrors.hint_phrase" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.hint_phrase[0] }}</span>
          </div>
          <div class="pt-4 border-t border-secondary">
            <label class="block text-sm font-bold text-gray-700 mb-2">Галерея зображень (до 5 фото)</label>
            <input type="file" @change="handleImageUpload" accept="image/*" multiple
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-purple-500 cursor-pointer mb-4"/>
            <span v-if="validationErrors.image_path" class="text-xs text-red-500 font-bold mt-1 mb-2 block">{{ validationErrors.image_path[0] }}</span>
            <div v-if="imagePreviews.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <div v-for="(img, idx) in imagePreviews" :key="idx" class="w-full h-24 rounded-xl overflow-hidden border border-secondary bg-gray-100 shadow-sm">
                <img :src="img" class="w-full h-full object-cover" />
              </div>
            </div>
            <p v-if="isEditMode && imageFiles.length === 0 && imagePreviews.length > 0" class="text-xs text-gray-400 mt-3">
              * Завантаження нових фото повністю замінить поточну галерею.
            </p>
          </div>
        </div>
        <div class="space-y-6 bg-white p-8 rounded-3xl shadow-sm border border-secondary h-fit">
          <h2 class="text-xl font-bold text-text mb-4">Налаштування</h2>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Складність *</label>
            <select v-model="form.difficulty" required
                    class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                    :class="validationErrors.difficulty ? 'border-red-500 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
              <option value="easy">Легкий</option>
              <option value="medium">Середній</option>
              <option value="hard">Складний</option>
              <option value="ultra hard">Експерт</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Мінімальний вік *</label>
            <select v-model="form.age" required
                    class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                    :class="validationErrors.age ? 'border-red-500 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
              <option value="0+">Від 0 років (0+)</option>
              <option value="8+">Від 8 років (8+)</option>
              <option value="10+">Від 10 років (10+)</option>
              <option value="12+">Від 12 років (12+)</option>
              <option value="16+">Від 16 років (16+)</option>
              <option value="18+">Тільки для дорослих (18+)</option>
            </select>
            <span v-if="validationErrors.age" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.age[0] }}</span>
          </div>
          <div class="flex gap-4">
            <div class="flex-1">
              <label class="block text-sm font-bold text-gray-700 mb-2">Мін.</label>
              <input v-model="form.min_players" type="number" min="1" required
                     class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                     :class="validationErrors.min_players ? 'border-red-500 bg-red-50' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
            </div>
            <div class="flex-1">
              <label class="block text-sm font-bold text-gray-700 mb-2">Макс.</label>
              <input v-model="form.max_players" type="number" min="1" required
                     class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                     :class="validationErrors.max_players ? 'border-red-500 bg-red-50' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
            </div>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Жанр квесту</label>
            <select v-model="form.genre"
                    class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                    :class="validationErrors.genre ? 'border-red-500 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
              <option value="">Без жанру</option>
              <option value="horror">Жахи</option>
              <option value="detective">Детектив</option>
              <option value="action">Екшн</option>
              <option value="mystic">Містика</option>
              <option value="adventure">Пригоди</option>
            </select>
            <span v-if="validationErrors.genre" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.genre[0] }}</span>
          </div>
          <span v-if="validationErrors.min_players || validationErrors.max_players" class="text-xs text-red-500 font-bold block">Перевірте кількість гравців</span>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Тривалість (хв) *</label>
            <input v-model="form.duration_minutes" type="number" min="10" step="10" required
                   class="w-full px-4 py-3 rounded-xl border focus:outline-none transition-colors"
                   :class="validationErrors.duration_minutes ? 'border-red-500 bg-red-50' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
          </div>
          <div class="pt-4 border-t border-secondary">
            <h3 class="text-sm font-bold text-gray-700 mb-4">Ціна (в гривнях ₴)</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Будні дні</label>
                <input v-model="form.weekday_price" type="number" min="0" required placeholder="Напр. 600"
                       class="w-full px-4 py-3 rounded-xl border font-bold focus:outline-none transition-colors text-primary"
                       :class="validationErrors.weekday_price ? 'border-red-500 bg-red-50' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Вихідні дні</label>
                <input v-model="form.weekend_price" type="number" min="0" required placeholder="Напр. 800"
                       class="w-full px-4 py-3 rounded-xl border font-bold focus:outline-none transition-colors text-primary"
                       :class="validationErrors.weekend_price ? 'border-red-500 bg-red-50' : 'border-secondary bg-gray-50 focus:ring-2 focus:ring-primary'">
              </div>
            </div>
            <span v-if="validationErrors.weekday_price || validationErrors.weekend_price" class="text-xs text-red-500 font-bold mt-2 block">Перевірте ціну</span>
          </div>

          <div class="pt-4 border-t border-secondary flex items-center justify-between">
            <label class="text-sm font-bold text-gray-700">Активна кімната</label>
            <input v-model="form.is_active" type="checkbox" :true-value="1" :false-value="0" class="w-5 h-5 text-primary rounded focus:ring-primary cursor-pointer border-secondary">
          </div>

        </div>
      </div>
      <div class="flex justify-end pt-4">
        <button type="submit" :disabled="isSaving" class="bg-primary hover:bg-purple-500 text-white px-10 py-4 rounded-xl font-bold shadow-lg transition-colors disabled:opacity-70 text-lg">
          {{ isSaving ? 'Збереження' : (isEditMode ? 'Зберегти зміни' : 'Створити кімнату') }}
        </button>
      </div>
    </form>
  </div>
</template>