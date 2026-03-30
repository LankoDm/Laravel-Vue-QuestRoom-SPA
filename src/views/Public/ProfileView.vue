<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';

const toast = useToastStore();
const authStore = useAuthStore();
const bookings = ref([]);
const isLoading = ref(true);
const selectedBooking = ref(null);
const isModalOpen = ref(false);
const isEditProfileModalOpen = ref(false);
const isChangePasswordModalOpen = ref(false);
const profileForm = ref({ name: '', phone: '' });
const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });
const openEditProfileModal = () => {
  profileForm.value.name = authStore.user?.name || '';
  profileForm.value.phone = authStore.user?.phone || '';
  isEditProfileModalOpen.value = true;
};
const submitProfileUpdate = async () => {
  if (profileForm.value.phone === '+380 ' || profileForm.value.phone === '+380') {
    profileForm.value.phone = '';
  }
  if (profileForm.value.phone && profileForm.value.phone.length < 19) {
    toast.warning('Будь ласка, введіть повний номер телефону (10 цифр).');
    return;
  }
  try {
    const response = await axios.put('http://localhost:8080/api/user/profile', profileForm.value);
    await authStore.fetchUser();
    toast.success('Профіль успішно оновлено!');
    isEditProfileModalOpen.value = false;
  } catch (error) {
    toast.error(error.response?.data?.message || 'Помилка оновлення профілю');
  }
};
const submitPasswordUpdate = async () => {
  try {
    await axios.put('http://localhost:8080/api/user/password', passwordForm.value);
    toast.success('Пароль успішно змінено!');
    isChangePasswordModalOpen.value = false;
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
  } catch (error) {
    toast.warning(error.response?.data?.message || 'Помилка зміни пароля. Перевірте правильність поточного пароля.');
  }
};
const handlePhoneInput = (event) => {
  let input = event.target.value.replace(/\D/g, '');
  if (!input.startsWith('380')) {
    input = '380' + input.replace(/^380/, '');
  }
  input = input.substring(0, 12);
  let formatted = '+380';
  if (input.length > 3) formatted += ' (' + input.substring(3, 5);
  if (input.length > 5) formatted += ') ' + input.substring(5, 8);
  if (input.length > 8) formatted += '-' + input.substring(8, 10);
  if (input.length > 10) formatted += '-' + input.substring(10, 12);
  profileForm.value.phone = formatted;
};
const fetchMyBookings = async () => {
  try {
    const response = await axios.get('http://localhost:8080/api/user/bookings');
    bookings.value = response.data.data || response.data;
  } catch (error) {
    console.error('Помилка завантаження бронювань:', error);
  } finally {
    isLoading.value = false;
  }
};
const viewDetails = async (id) => {
  try {
    const response = await axios.get(`http://localhost:8080/api/bookings/${id}`);
    selectedBooking.value = response.data.data || response.data;
    isModalOpen.value = true;
  } catch (error) {
    toast.error('Не вдалося завантажити деталі бронювання.');
  }
};
const activeBookings = computed(() => bookings.value.filter(b => ['pending', 'confirmed'].includes(b.status)));
const pastBookings = computed(() => bookings.value.filter(b => ['finished', 'cancelled'].includes(b.status)));
const formatPrice = (price) => price ? price / 100 : 0;
const formatDate = (dateString) => {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString('uk-UA', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  });
};
const isReviewModalOpen = ref(false);
const reviewForm = ref({
  room_id: null,
  room_name: '',
  rating: 5,
  message: ''
});
const openReviewModal = (booking) => {
  reviewForm.value.room_id = booking.room?.id;
  reviewForm.value.room_name = booking.room?.name;
  reviewForm.value.rating = 5;
  reviewForm.value.message = '';
  isReviewModalOpen.value = true;
};
const submitReview = async () => {
  try {
    await axios.post('http://localhost:8080/api/reviews', {
      room_id: reviewForm.value.room_id,
      rating: reviewForm.value.rating,
      message: reviewForm.value.message
    });
    toast.success('Дякуємо! Ваш відгук успішно надіслано на модерацію.');
    isReviewModalOpen.value = false;
  } catch (error) {
    toast.error(error.response?.data?.message || 'Помилка при відправці відгуку');
  }
};
const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-700',
  confirmed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
  finished: 'bg-blue-100 text-blue-700'
};
const statusNames = {
  pending: 'Очікує',
  confirmed: 'Підтверджено',
  cancelled: 'Скасовано',
  finished: 'Завершено'
};
const getFirstImage = (imagePath) => {
  if (!imagePath) return null;
  if (Array.isArray(imagePath)) return imagePath[0];
  if (typeof imagePath === 'string' && imagePath.startsWith('[')) {
    try { return JSON.parse(imagePath)[0]; } catch (e) { return imagePath; }
  }
  return imagePath;
};

onMounted(fetchMyBookings);
</script>

<template>
  <div class="py-10 px-6 max-w-6xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

      <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-secondary text-center sticky top-28">
          <div class="w-32 h-32 mx-auto rounded-full bg-primary/10 flex items-center justify-center text-primary text-5xl font-black mb-6 border-4 border-white shadow-lg">
            {{ authStore.user?.name?.charAt(0) || 'U' }}
          </div>
          <h1 class="text-2xl font-black text-text mb-1">{{ authStore.user?.name }}</h1>
          <p class="text-gray-500 font-medium mb-6">{{ authStore.user?.email }}</p>
          <p class="text-gray-500 font-medium mb-6 text-sm">{{ authStore.user?.phone || 'Телефон не вказано' }}</p>

          <div class="bg-gray-50 p-4 rounded-2xl text-left border border-gray-100 space-y-3">
            <div>
              <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Дата реєстрації</span>
              <span class="font-bold text-text">{{ new Date(authStore.user?.created_at).toLocaleDateString('uk-UA') }}</span>
            </div>
          </div>
          <button @click="openEditProfileModal" class="w-full cursor-pointer bg-gray-100 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-200 transition-colors">
            Редагувати профіль
          </button>
          <button @click="isChangePasswordModalOpen = true" class="w-full cursor-pointer bg-gray-100 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-200 transition-colors">
            Змінити пароль
          </button>
          <button @click="authStore.logout" class="w-full cursor-pointer mt-6 bg-red-50 text-red-600 font-bold py-3 rounded-xl hover:bg-red-600 hover:text-white transition-colors">
            Вийти з акаунта
          </button>
        </div>
      </div>

      <div class="lg:col-span-2">
        <h2 class="text-3xl font-black text-text mb-8">Мої бронювання</h2>

        <div v-if="isLoading" class="text-center py-20 text-gray-400 animate-pulse font-bold">
          Завантаження історії
        </div>

        <div v-else class="space-y-10">
          <section>
            <h3 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-green-500"></span> Актуальні
            </h3>
            <div v-if="activeBookings.length === 0" class="bg-white p-8 rounded-3xl border border-secondary text-gray-500 text-center">
              У вас немає запланованих ігор.
            </div>
            <div class="space-y-4">
              <div v-for="booking in activeBookings" :key="booking.id" class="bg-white p-5 rounded-2xl border border-secondary flex flex-col sm:flex-row justify-between sm:items-center gap-4 hover:border-primary transition-colors">
                <div class="flex items-center gap-4">
                  <img v-if="booking.room?.image_path" :src="getFirstImage(booking.room.image_path)" class="w-16 h-16 rounded-xl object-cover shrink-0" />
                  <div>
                    <h4 class="font-bold text-lg text-text">{{ booking.room?.name }}</h4>
                    <p class="text-sm font-bold text-primary">{{ formatDate(booking.start_time) }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-4 sm:ml-auto">
                  <span class="px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider" :class="statusClasses[booking.status]">
                    {{ statusNames[booking.status] }}
                  </span>
                  <button @click="viewDetails(booking.id)" class="text-gray-400 hover:text-primary p-2 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                  </button>
                </div>
              </div>
            </div>
          </section>

          <section v-if="pastBookings.length > 0">
            <h3 class="text-xl font-bold text-gray-500 mb-4 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-gray-300"></span> Історія
            </h3>
            <div class="space-y-4">
              <div v-for="booking in pastBookings" :key="booking.id" class="bg-white p-5 rounded-2xl border border-secondary flex flex-col sm:flex-row justify-between sm:items-center gap-4 opacity-70 hover:opacity-100 transition-opacity">
                <div class="flex items-center gap-4">
                  <img v-if="booking.room?.image_path" :src="getFirstImage(booking.room.image_path)" class="w-12 h-12 rounded-xl object-cover shrink-0 grayscale" />
                  <div>
                    <h4 class="font-bold text-text">{{ booking.room?.name }}</h4>
                    <p class="text-xs text-gray-500">{{ formatDate(booking.start_time) }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-4 sm:ml-auto">
                  <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="statusClasses[booking.status]">
                    {{ statusNames[booking.status] }}
                  </span>
                  <button v-if="booking.status === 'finished'" @click="openReviewModal(booking)" class="text-sm font-bold text-primary hover:underline cursor-pointer">
                    Залишити відгук
                  </button>
                  <button @click="viewDetails(booking.id)" class="text-gray-400 hover:text-primary p-2 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                  </button>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
    <div v-if="isEditProfileModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click="isEditProfileModalOpen = false">
      <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-8" @click.stop>
        <h3 class="text-2xl font-black mb-6 text-text">Редагування профілю</h3>
        <form @submit.prevent="submitProfileUpdate" class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Ім'я</label>
            <input v-model="profileForm.name" type="text" required class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Телефон</label>
            <input
                v-model="profileForm.phone"
                @input="handlePhoneInput"
                @focus="profileForm.phone = profileForm.phone || '+380 '"
                type="tel"
                maxlength="19"
                placeholder="+380 (99) 000-00-00"
                class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium tracking-wide">
          </div>
          <div class="flex gap-4 pt-4">
            <button type="button" @click="isEditProfileModalOpen = false" class="flex-1 px-4 py-3 font-bold text-gray-400 hover:text-text transition-colors cursor-pointer">Скасувати</button>
            <button type="submit" class="flex-1 bg-primary text-white font-black py-3 rounded-xl shadow-lg hover:bg-purple-600 transition-all cursor-pointer">Зберегти</button>
          </div>
        </form>
      </div>
    </div>
    <div v-if="isChangePasswordModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click="isChangePasswordModalOpen = false">
      <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-8" @click.stop>
        <h3 class="text-2xl font-black mb-6 text-text">Зміна пароля</h3>
        <form @submit.prevent="submitPasswordUpdate" class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Поточний пароль</label>
            <input v-model="passwordForm.current_password" type="password" required class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Новий пароль</label>
            <input v-model="passwordForm.password" type="password" required minlength="8" class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Підтвердіть пароль</label>
            <input v-model="passwordForm.password_confirmation" type="password" required minlength="8" class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
          </div>
          <div class="flex gap-4 pt-4">
            <button type="button" @click="isChangePasswordModalOpen = false" class="flex-1 px-4 py-3 font-bold text-gray-400 hover:text-text transition-colors cursor-pointer">Скасувати</button>
            <button type="submit" class="flex-1 bg-primary text-white font-black py-3 rounded-xl shadow-lg hover:bg-purple-600 transition-all cursor-pointer">Оновити</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isModalOpen && selectedBooking" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click="isModalOpen = false">
      <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden" @click.stop>
        <div class="bg-primary p-6 text-white flex justify-between items-center">
          <h3 class="text-xl font-black">Деталі бронювання #{{ selectedBooking.id }}</h3>
          <button @click="isModalOpen = false" class="text-white/70 hover:text-white cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <div class="p-6 space-y-4 text-text">
          <div class="flex justify-between border-b border-gray-100 pb-3">
            <span class="text-gray-500">Кімната</span>
            <span class="font-bold">{{ selectedBooking.room?.name }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-100 pb-3">
            <span class="text-gray-500">Дата гри</span>
            <span class="font-bold text-primary">{{ formatDate(selectedBooking.start_time) }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-100 pb-3">
            <span class="text-gray-500">Ім'я гостя</span>
            <span class="font-bold">{{ selectedBooking.guest_name }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-100 pb-3">
            <span class="text-gray-500">Телефон</span>
            <span class="font-bold">{{ selectedBooking.guest_phone }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-100 pb-3">
            <span class="text-gray-500">Спосіб оплати</span>
            <span class="font-bold uppercase">{{ selectedBooking.payment_method }}</span>
          </div>
          <div class="flex justify-between pt-2">
            <span class="text-gray-500">Сума</span>
            <span class="font-black text-xl">{{ formatPrice(selectedBooking.total_price) }} ₴</span>
          </div>
          <div v-if="selectedBooking.ticket_url" class="pt-4 mt-2 border-t border-gray-100">
            <a :href="selectedBooking.ticket_url" target="_blank"
               class="w-full bg-primary hover:bg-purple-600 text-white font-bold py-3 rounded-xl transition-all shadow-md flex justify-center items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
              </svg>
              Завантажити квиток (PDF)
            </a>
          </div>
        </div>
      </div>
    </div>

    <div v-if="isReviewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click="isReviewModalOpen = false">
      <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-8" @click.stop>
        <h3 class="text-2xl font-black mb-2 text-text">Ваші враження від «{{ reviewForm.room_name }}»</h3>
        <p class="text-gray-500 mb-6 font-medium">Ваш відгук допоможе іншим обрати найкращий квест!</p>

        <div class="flex justify-center gap-2 mb-8">
          <svg v-for="star in 5" :key="star"
               @click="reviewForm.rating = star"
               class="w-10 h-10 star-rating transition-all"
               :class="star <= reviewForm.rating ? 'text-yellow-400 fill-current' : 'text-gray-200 fill-current'"
               viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
        </div>

        <textarea v-model="reviewForm.message" rows="4" placeholder="Що вам найбільше сподобалось?"
                  class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none mb-6 resize-none bg-gray-50 text-text font-medium"></textarea>

        <div class="flex gap-4">
          <button @click="isReviewModalOpen = false" class="flex-1 px-4 py-3 font-bold text-gray-400 hover:text-text transition-colors cursor-pointer">Скасувати</button>
          <button @click="submitReview" class="flex-1 bg-primary text-white font-black py-3 rounded-xl shadow-lg hover:bg-purple-600 transition-all cursor-pointer">Надіслати</button>
        </div>
      </div>
    </div>

  </div>
</template>
<style scoped>
.star-rating {
  cursor: pointer;
}
.star-rating:hover {
  transform: scale(1.1);
}
</style>