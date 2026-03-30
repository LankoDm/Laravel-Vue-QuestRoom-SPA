<script setup>
import {ref, onMounted, computed, watch, onUnmounted} from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';

const toast = useToastStore();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const room = ref(null);
const isLoading = ref(true);
const reviews = ref([]);
const fetchReviews = async () => {
  try {
    const response = await axios.get(`http://localhost:8080/api/rooms/${room.value.id}/reviews`);
    reviews.value = response.data.data || response.data;
  } catch (error) {
    console.error('Помилка завантаження відгуків:', error);
  }
};
const calendarSection = ref(null);
const bookingPanel = ref(null);
const holdToken = ref(Math.random().toString(36).substring(2, 15));
const selectedSlot = ref(null);
const selectedPlayers = ref(null);
const isModalOpen = ref(false);
const isSubmitting = ref(false);
const validationErrors = ref({});
const errorMessage = ref('');
const timeLeft = ref(600);
const timerInterval = ref(null);
const bookingForm = ref({
  name: '',
  phone: '',
  email: '',
  comment: '',
  payment_method: 'cash'
});
watch(() => authStore.user, (user) => {
  if (user) {
    bookingForm.value.name = user.name || '';
    bookingForm.value.email = user.email || '';
    bookingForm.value.phone = user.phone || '';
  }
}, { immediate: true });

const formatPrice = (kopecks) => {
  return kopecks ? kopecks / 100 : 0;
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
  bookingForm.value.phone = formatted;
};

const fetchRoom = async () => {
  try {
    const response = await axios.get(`http://localhost:8080/api/rooms/${route.params.slug}`);
    const fetchedRoom = response.data.data || response.data;
    if (fetchedRoom.is_active === 0 || fetchedRoom.is_active === false) {
      router.replace({ name: 'not-found' });
      return;
    }
    room.value = fetchedRoom;
    selectedPlayers.value = room.value.min_players;
    await fetchReviews();
    isLoading.value = false;
  } catch (error) {
    console.error('Помилка завантаження кімнати:', error);
    if (error.response?.status === 404) {
      router.replace({ name: 'not-found' });
    } else {
      isLoading.value = false;
    }
  }
};
const parsedImages = computed(() => {
  if (!room.value || !room.value.image_path) return [];
  if (Array.isArray(room.value.image_path)) return room.value.image_path;
  if (typeof room.value.image_path === 'string' && room.value.image_path.startsWith('[')) {
    try { return JSON.parse(room.value.image_path); } catch (e) { return [room.value.image_path]; }
  }
  return [room.value.image_path];
});

const schedule = computed(() => {
  if (!room.value || !selectedPlayers.value) return [];

  const days = [];
  const today = new Date();
  const now = new Date();
  const ukrDays = ['НД', 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ'];

  const duration = room.value.duration_minutes || 60;
  const prepTime = 30;
  const step = duration + prepTime;
  const extraPlayers = selectedPlayers.value - room.value.min_players;
  const surcharge = extraPlayers > 0 ? extraPlayers * 100 : 0;

  const timeSlots = [];
  let currentMinutes = 8 * 60;
  const endMinutes = 23 * 60;

  while (currentMinutes <= endMinutes) {
    const hours = Math.floor(currentMinutes / 60);
    const mins = currentMinutes % 60;
    const timeString = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
    timeSlots.push(timeString);
    currentMinutes += step;
  }

  for (let i = 0; i < 7; i++) {
    const d = new Date(today);
    d.setDate(today.getDate() + i);
    const isWeekend = d.getDay() === 0 || d.getDay() === 6;
    const basePrice = isWeekend ? formatPrice(room.value.weekend_price) : formatPrice(room.value.weekday_price);
    const backendDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    const slots = timeSlots.map(time => {
      const [hour, minute] = time.split(':').map(Number);
      const slotDateObj = new Date(d);
      slotDateObj.setHours(hour, minute, 0, 0);
      const slotStartTime = slotDateObj.getTime();
      const slotEndTime = slotStartTime + (duration * 60 * 1000);
      const isPast = slotStartTime < now.getTime();
      const isBooked = room.value.bookings?.some(b => {
        const bStart = new Date(b.start_time.replace(' ', 'T')).getTime();
        const bEnd = new Date(b.end_time.replace(' ', 'T')).getTime();
        return slotStartTime < bEnd && slotEndTime > bStart;
      }) || false;

      const isLate = hour >= 21;
      const slotBase = basePrice + (isLate ? 200 : 0);
      const finalPrice = slotBase + surcharge;

      return { time, price: finalPrice, basePrice: slotBase, isPast, isBooked };
    });

    days.push({
      dateStr: `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')} ${ukrDays[d.getDay()]}`,
      backendDate,
      isWeekend,
      slots
    });
  }
  return days;
});
const currentPrice = computed(() => {
  if (!selectedSlot.value) return 0;
  const extraPlayers = selectedPlayers.value - room.value.min_players;
  const surcharge = extraPlayers > 0 ? extraPlayers * 100 : 0;
  return selectedSlot.value.basePrice + surcharge;
});

const scrollToCalendar = () => {
  if (calendarSection.value) {
    calendarSection.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

const selectSlot = (day, slot) => {
  selectedSlot.value = {
    date: day.dateStr,
    backendDate: day.backendDate,
    time: slot.time,
    basePrice: slot.basePrice
  };

  setTimeout(() => {
    if (bookingPanel.value) {
      bookingPanel.value.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, 50);
};

const formattedTimeLeft = computed(() => {
  const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0');
  const s = (timeLeft.value % 60).toString().padStart(2, '0');
  return `${m}:${s}`;
});

const startTimer = () => {
  timeLeft.value = 600;
  clearInterval(timerInterval.value);
  timerInterval.value = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--;
    } else {
      closeModalWithTimeout();
    }
  }, 1000);
};

const closeModalWithTimeout = () => {
  clearInterval(timerInterval.value);
  isModalOpen.value = false;
  toast.info('Час на оформлення вичерпано. Будь ласка, оберіть час ще раз.');
};

const openBookingModal = async () => {
  isSubmitting.value = true;
  try {
    const backendStartTime = `${selectedSlot.value.backendDate} ${selectedSlot.value.time}:00`;
    await axios.post('http://localhost:8080/api/bookings/hold', {
      room_id: room.value.id,
      start_time: backendStartTime,
      hold_token: holdToken.value
    });
    isModalOpen.value = true;
    startTimer();
  } catch (error) {
    if (error.response?.status === 409) {
      toast.info('Хтось інший щойно почав бронювати цей час! Будь ласка, оберіть інший слот.');
      selectedSlot.value = null;
    } else {
      toast.error('Помилка сервера. Спробуйте ще раз.');
    }
  } finally {
    isSubmitting.value = false;
  }
};

const closeBookingModal = () => {
  clearInterval(timerInterval.value);
  isModalOpen.value = false;
  validationErrors.value = {};
  errorMessage.value = '';
};
const submitBooking = async () => {
  isSubmitting.value = true;
  validationErrors.value = {};
  errorMessage.value = '';
  try {
    const payload = {
      room_id: room.value.id,
      start_time: `${selectedSlot.value.backendDate} ${selectedSlot.value.time}:00`,
      duration_minutes: room.value.duration_minutes,
      players_count: selectedPlayers.value,
      total_price: currentPrice.value * 100,
      guest_name: bookingForm.value.name,
      guest_phone: bookingForm.value.phone,
      guest_email: bookingForm.value.email,
      comment: bookingForm.value.comment,
      payment_method: bookingForm.value.payment_method,
      hold_token: holdToken.value
    };
    const response = await axios.post('http://localhost:8080/api/bookings', payload);
    const createdBooking = response.data;
    clearInterval(timerInterval.value);
    if (bookingForm.value.payment_method === 'card') {
      const paymentResponse = await axios.post(`http://localhost:8080/api/bookings/${createdBooking.id}/pay`);
      window.location.href = paymentResponse.data.url;
      return;
    }
    isModalOpen.value = false;
    selectedSlot.value = null;
    toast.success('Бронювання успішно створено! Очікуйте дзвінка менеджера.');
  } catch (error) {
    console.error('Помилка бронювання:', error);
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors;
      errorMessage.value = 'Будь ласка, перевірте правильність заповнення форми.';
    } else {
      errorMessage.value = 'Помилка при створенні бронювання. Спробуйте пізніше.';
    }
  } finally {
    isSubmitting.value = false;
  }
};
const gameStage = ref(0);
const currentLevel = ref(1);
const markerPos = ref(0);
const targetStart = ref(40);
const targetWidth = ref(20);
const failedHack = ref(false);
const successHit = ref(false);
const displayedHint = ref('************************');
let speed = 0.4;
let dir = 1;
let reqId = null;
let lastTimestamp = 0;
const startMiniGame = () => {
  gameStage.value = 1;
  currentLevel.value = 1;
  initLevel(1);
  lastTimestamp = 0;
  cancelAnimationFrame(reqId);
  reqId = requestAnimationFrame(gameLoop);
};
const initLevel = (lvl) => {
  targetWidth.value = 32 - (lvl * 6);
  targetStart.value = 10 + Math.random() * (80 - targetWidth.value);
  speed = 0.3 + (lvl * 0.3);
};
const gameLoop = (timestamp) => {
  if (gameStage.value !== 1) return;
  if (!lastTimestamp) lastTimestamp = timestamp;
  const deltaTime = timestamp - lastTimestamp;
  lastTimestamp = timestamp;
  markerPos.value += (speed * deltaTime * 0.1) * dir;
  if (markerPos.value >= 100) { markerPos.value = 100; dir = -1; }
  if (markerPos.value <= 0) { markerPos.value = 0; dir = 1; }
  reqId = requestAnimationFrame(gameLoop);
};
const tryHack = () => {
  const tolerance = 2.5;
  const isHit = markerPos.value >= (targetStart.value - tolerance) &&
      markerPos.value <= (targetStart.value + targetWidth.value + tolerance);
  if (isHit) {
    successHit.value = true;
    setTimeout(() => { successHit.value = false; }, 200);
    if (currentLevel.value < 3) {
      currentLevel.value++;
      initLevel(currentLevel.value);
    } else {
      gameStage.value = 2;
      cancelAnimationFrame(reqId);
      triggerDecoder();
    }
  } else {
    failedHack.value = true;
    setTimeout(() => { failedHack.value = false; }, 300);
    currentLevel.value = 1;
    initLevel(1);
  }
};
const triggerDecoder = () => {
  const targetText = room.value?.hint_phrase || 'Таємниця відсутня';
  const characters = 'АБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯ0123456789@#$%&*!?><';
  let iterations = 0;
  const interval = setInterval(() => {
    displayedHint.value = targetText.split('').map((char, index) => {
      if (index < iterations) return char;
      if (char === ' ') return ' ';
      return characters[Math.floor(Math.random() * characters.length)];
    }).join('');
    if (iterations >= targetText.length) {
      clearInterval(interval);
      gameStage.value = 3;
      displayedHint.value = targetText;
    }
    iterations += 1 / 3;
  }, 40);
};

onMounted(() => {
  fetchRoom();
});

onUnmounted(() => {
  clearInterval(timerInterval.value);
});
</script>

<template>
  <div class="py-10 px-6 max-w-6xl mx-auto relative">

    <div v-if="isLoading" class="text-center text-xl text-primary animate-pulse py-20">
      Завантаження даних кімнати
    </div>
    <div v-else-if="room" class="space-y-16">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

        <div class="lg:col-span-2 space-y-8">

          <div class="w-full flex overflow-x-auto snap-x snap-mandatory gap-4 pb-4 custom-scrollbar">
            <template v-if="parsedImages.length > 0">
              <div v-for="(img, idx) in parsedImages" :key="idx" class="snap-center shrink-0 w-full md:w-4/5 h-[400px] rounded-3xl overflow-hidden relative shadow-sm">
                <img :src="img" :alt="room.name" class="w-full h-full object-cover" />
              </div>
            </template>
            <template v-else>
              <div class="snap-center shrink-0 w-full h-[400px] rounded-3xl bg-secondary flex items-center justify-center text-gray-400">
                Немає зображень
              </div>
            </template>
          </div>

          <div>
            <div class="flex items-center space-x-3 mb-2">
              <span class="bg-primary text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm">
                {{ room.difficulty }}
              </span>
            </div>
            <h1 class="text-4xl font-black text-text mb-4">{{ room.name }}</h1>
            <div class="flex items-center space-x-6 text-gray-500 font-medium">
              <div class="flex items-center space-x-1 bg-secondary px-2 py-1 rounded-lg">
                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                <span class="text-sm font-bold text-text">{{ room.reviews_avg_rating ? Number(room.reviews_avg_rating).toFixed(1) : '—' }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>{{ room.min_players }} - {{ room.max_players }} гравців</span>
              </div>
              <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ room.duration_minutes }} хв гри</span>
              </div>
            </div>
          </div>
          <div class="prose max-w-none text-text leading-relaxed">
            <h2 class="text-2xl font-bold mb-4">Про квест</h2>
            <p class="whitespace-pre-line">{{ room.description }}</p>
          </div>
          <div v-if="room.hint_phrase" class="bg-gray-50 p-8 rounded-3xl mt-12 shadow-sm border transition-colors duration-300 relative overflow-hidden"
               :class="failedHack ? 'bg-red-50 border-red-200' : 'border-secondary'">
            <div class="absolute -right-10 -top-10 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
            <div class="text-center mb-8 relative z-10">
              <h2 class="text-2xl font-black text-text mb-2 flex items-center justify-center gap-2">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Секретний смарт-замок
              </h2>
              <p class="text-gray-500 font-medium text-sm md:text-base">
                Зупиніть сканер у зеленій зоні, щоб розблокувати приховану підказку.
              </p>
            </div>
            <div class="flex flex-col items-center gap-6 relative z-10 w-full max-w-xl mx-auto">
              <div v-if="gameStage === 0" class="py-6">
                <button @click="startMiniGame" class="bg-primary hover:bg-purple-600 text-white font-black uppercase tracking-widest py-4 px-12 rounded-xl transition-all shadow-md hover:shadow-lg hover:-translate-y-1">
                  Розблокувати
                </button>
              </div>
              <div v-else-if="gameStage === 1" class="w-full space-y-8 animate-fade-in">
                <div class="flex justify-between items-center px-2">
                  <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Рівень захисту:</span>
                  <div class="flex gap-2">
                    <div v-for="i in 3" :key="i" class="w-8 h-2 rounded-full transition-colors duration-300"
                         :class="i < currentLevel ? 'bg-green-400 shadow-sm' : i === currentLevel ? 'bg-primary animate-pulse' : 'bg-gray-200'">
                    </div>
                  </div>
                </div>
                <div class="relative w-full h-12 bg-white rounded-xl overflow-hidden border-2 transition-colors duration-200 shadow-inner"
                     :class="failedHack ? 'border-red-300' : successHit ? 'border-green-400' : 'border-secondary'">
                  <div class="absolute h-full bg-green-100 border-x-2 border-green-400 transition-all duration-300"
                       :style="{ left: `${targetStart}%`, width: `${targetWidth}%` }">
                  </div>
                  <div class="absolute h-full w-2 bg-primary shadow-[0_0_8px_rgba(168,85,247,0.6)] z-10 -ml-1 transition-opacity"
                       :class="{'opacity-0': successHit}"
                       :style="{ left: `${markerPos}%` }">
                  </div>
                  <div v-if="successHit" class="absolute inset-0 bg-green-50 animate-pulse"></div>
                </div>
                <button @click="tryHack" class="w-full bg-primary hover:bg-purple-600 text-white font-black text-xl tracking-widest py-5 rounded-xl transition-all active:scale-95 shadow-md">
                  ЗУПИНИТИ
                </button>
              </div>
              <div v-else class="w-full bg-white p-8 rounded-3xl border border-secondary min-h-[140px] flex flex-col items-center justify-center relative overflow-hidden shadow-sm animate-fade-in">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-primary/5 to-transparent opacity-50 animate-scan pointer-events-none"></div>
                <p class="font-mono text-xl md:text-2xl break-words text-center leading-relaxed tracking-wider transition-colors duration-300 relative z-10"
                   :class="gameStage === 3 ? 'text-primary font-black drop-shadow-sm' : 'text-gray-400 font-medium'">
                  {{ displayedHint }}
                </p>
                <div v-if="gameStage === 3" class="mt-4 bg-green-50 text-green-600 text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg animate-fade-in flex items-center gap-2 border border-green-200">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  Підказку розблоковано
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="lg:col-span-1" ref="bookingPanel">
          <div class="sticky top-28 bg-white p-8 rounded-3xl shadow-lg border border-secondary flex flex-col items-center text-center transition-all duration-300">

            <div v-if="selectedSlot" class="w-full flex flex-col items-center animate-fade-in">
              <p class="text-gray-500 font-medium mb-1">Ви обрали час:</p>
              <p class="text-2xl font-bold text-text mb-2">
                {{ selectedSlot.date }} о <span class="text-primary">{{ selectedSlot.time }}</span>
              </p>

              <div class="bg-secondary/30 text-primary font-bold px-4 py-2 rounded-full mb-6 text-sm">
                Гравців: {{ selectedPlayers }}
              </div>

              <p class="text-sm text-gray-500">До сплати</p>
              <p class="text-4xl font-black text-text mb-8">{{ currentPrice }} ₴</p>

              <button @click="openBookingModal" :disabled="isSubmitting" class="w-full bg-primary hover:bg-purple-500 text-white font-bold py-4 rounded-xl transition-colors shadow-lg shadow-primary/30 disabled:opacity-70">
                <span v-if="isSubmitting">Перевірка часу</span>
                <span v-else>Перейти до бронювання</span>
              </button>

              <button @click="selectedSlot = null" class="mt-4 text-sm text-gray-400 hover:text-primary transition-colors">
                Обрати інший час
              </button>
            </div>
            <div v-else class="w-full">
              <h3 class="text-xl font-bold text-text mb-6">Вартість гри</h3>
              <div class="flex justify-between items-end mb-4">
                <span class="text-gray-500 font-medium">У будні</span>
                <span class="text-2xl font-bold text-text">{{ formatPrice(room.weekday_price) }} ₴</span>
              </div>
              <div class="flex justify-between items-end mb-6 border-t border-gray-100 pt-4">
                <span class="text-gray-500 font-medium">У вихідні</span>
                <span class="text-2xl font-black text-primary">{{ formatPrice(room.weekend_price) }} ₴</span>
              </div>

              <div class="mb-8 text-sm text-left text-gray-500 bg-secondary/30 p-4 rounded-xl border border-secondary">
                <p class="flex items-start gap-2">
                  <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  <span>Вечірні сеанси (з 21:00) дорожчі на <span class="font-bold text-primary">200 ₴</span></span>
                </p>
              </div>

              <button @click="scrollToCalendar" class="w-full bg-primary hover:opacity-90 text-white font-bold py-4 rounded-xl transition-opacity duration-300 shadow-md">
                Вибрати час
              </button>
            </div>

          </div>
        </div>

      </div>

      <div ref="calendarSection" class="bg-white rounded-3xl shadow-sm border border-secondary p-8 scroll-mt-28">

        <div class="mb-8 border-b border-secondary pb-6">
          <h2 class="text-3xl font-bold text-text mb-6">Оберіть кількість гравців та час гри</h2>

          <div>
            <p class="text-sm font-bold text-gray-500 mb-3">Скільки вас буде?</p>
            <div class="flex flex-wrap gap-3">
              <button
                  v-for="n in (room.max_players - room.min_players + 1)"
                  :key="n"
                  @click="selectedPlayers = room.min_players + n - 1"
                  :class="selectedPlayers === (room.min_players + n - 1) ? 'bg-primary text-white shadow-md transform scale-105 border-primary' : 'bg-transparent border-secondary text-gray-600 hover:border-primary'"
                  class="w-14 h-14 rounded-2xl border-2 font-black text-lg flex items-center justify-center transition-all duration-200">
                {{ room.min_players + n - 1 }}
              </button>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div v-for="day in schedule" :key="day.dateStr" class="flex flex-col md:flex-row border-b-2 border-secondary pb-8 mb-8 last:border-0 last:pb-0 last:mb-0">

            <div class="md:w-48 shrink-0 flex items-center mb-4 md:mb-0">
              <div class="text-lg font-black" :class="day.isWeekend ? 'text-primary' : 'text-text'">
                {{ day.dateStr }}
              </div>
            </div>

            <div class="flex flex-wrap gap-3">
              <button
                  v-for="slot in day.slots"
                  :key="slot.time"
                  :disabled="slot.isPast || slot.isBooked"
                  @click="!(slot.isPast || slot.isBooked) && selectSlot(day, slot)"
                  :class="[
                  'flex flex-col items-center justify-center px-4 py-2 rounded-xl border-2 transition-all duration-200 min-w-[80px]',
                  (slot.isPast || slot.isBooked)
                    ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed opacity-60'
                    : selectedSlot?.date === day.dateStr && selectedSlot?.time === slot.time
                      ? 'border-primary bg-primary text-white shadow-md transform scale-105'
                      : 'border-secondary bg-transparent hover:border-primary text-text hover:bg-secondary/30'
                ]">
                <span class="text-lg font-bold">{{ slot.time }}</span>
                <span v-if="slot.isBooked" class="text-[10px] font-black uppercase text-red-400 mt-1 tracking-widest">Зайнято</span>
                <span v-else class="text-xs font-medium opacity-80 mt-1">{{ slot.price }} ₴</span>
              </button>
            </div>

          </div>
        </div>
      </div>

      <div class="mt-12 pt-12 border-t border-secondary">
        <h2 class="text-2xl font-bold mb-6 text-text">Відгуки гравців ({{ reviews.length }})</h2>

        <div v-if="reviews.length === 0" class="bg-gray-50 rounded-2xl p-6 text-center text-gray-500 font-medium border border-secondary">
          Поки що немає відгуків. Станьте першим, хто поділиться враженнями після гри!
        </div>

        <div v-else class="space-y-4">
          <div v-for="review in reviews" :key="review.id" class="bg-white p-6 rounded-2xl border border-secondary shadow-sm">
            <div class="flex justify-between items-start mb-2">
              <div class="font-bold text-text">{{ review.user?.name || 'Гість' }}</div>
              <div class="flex text-yellow-400">
                <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= review.rating ? 'fill-current' : 'text-gray-200 fill-current'" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
            </div>
            <p class="text-gray-600 italic text-sm mb-3">"{{ review.message }}"</p>
            <div class="text-xs text-gray-400">{{ new Date(review.created_at).toLocaleDateString('uk-UA') }}</div>
          </div>
        </div>
      </div>

    </div>

    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in" @click="closeBookingModal">
      <div class="bg-white rounded-3xl w-full max-w-4xl shadow-2xl overflow-hidden" @click.stop>

        <div class="bg-primary p-6 text-white flex justify-between items-center relative overflow-hidden">
          <h3 class="text-2xl font-black relative z-10">Оформлення броні</h3>

          <div class="relative z-10 flex items-center gap-2 bg-white/20 px-3 py-1.5 rounded-lg border border-white/30" :class="{'animate-pulse text-red-200': timeLeft < 60}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-black font-mono tracking-wider">{{ formattedTimeLeft }}</span>
          </div>

          <button @click="closeBookingModal" class="relative z-10 text-white/70 hover:text-white transition-colors bg-white/10 p-2 rounded-full ml-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
          <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
        </div>

        <div class="p-8 max-h-[85vh] overflow-y-auto custom-scrollbar">
          <div v-if="errorMessage" class="mb-6 p-3 bg-red-50 text-red-600 rounded-xl border border-red-200 font-bold flex items-start gap-2 shadow-sm text-sm">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ errorMessage }}</span>
          </div>
          <form @submit.prevent="submitBooking" class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="space-y-5">
              <h4 class="text-lg font-black text-text border-b border-secondary pb-2">Ваші дані</h4>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Ваше ім'я *</label>
                <input v-model="bookingForm.name" type="text" required placeholder="Олександр"
                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:outline-none transition-colors"
                       :class="validationErrors.guest_name ? 'border-red-500 bg-red-50 focus:ring-red-200 text-red-700' : 'border-secondary bg-gray-50 focus:ring-primary'">
                <span v-if="validationErrors.guest_name" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.guest_name[0] }}</span>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Телефон *</label>
                <input v-model="bookingForm.phone"
                       @input="handlePhoneInput"
                       @focus="bookingForm.phone = bookingForm.phone || '+380 '"
                       type="tel" required placeholder="+380 (99) 000-00-00" maxlength="19"
                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:outline-none transition-colors font-bold tracking-wide"
                       :class="validationErrors.guest_phone ? 'border-red-500 bg-red-50 focus:ring-red-200 text-red-700' : 'border-secondary bg-gray-50 focus:ring-primary'">
                <span v-if="validationErrors.guest_phone" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.guest_phone[0] }}</span>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Email (для чеку)</label>
                <input v-model="bookingForm.email" type="email" placeholder="email@example.com"
                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:outline-none transition-colors"
                       :class="validationErrors.guest_email ? 'border-red-500 bg-red-50 focus:ring-red-200 text-red-700' : 'border-secondary bg-gray-50 focus:ring-primary'">
                <span v-if="validationErrors.guest_email" class="text-xs text-red-500 font-bold mt-1 block">{{ validationErrors.guest_email[0] }}</span>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Коментар (необов'язково)</label>
                <textarea v-model="bookingForm.comment" rows="2" placeholder="Наприклад: з нами будуть діти"
                          class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none transition-colors bg-gray-50 resize-none"></textarea>
              </div>
            </div>
            <div class="space-y-6 flex flex-col">
              <h4 class="text-lg font-black text-text border-b border-secondary pb-2">Деталі гри</h4>
              <div class="bg-secondary/30 p-5 rounded-2xl text-sm text-text font-medium space-y-4 border border-secondary">
                <div class="flex justify-between items-center">
                  <span class="text-gray-500">Кімната:</span>
                  <span class="font-bold text-right">{{ room.name }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-gray-500">Дата та час:</span>
                  <span class="text-primary font-black bg-white px-3 py-1 rounded-lg border border-secondary shadow-sm">{{ selectedSlot.date }} о {{ selectedSlot.time }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-gray-500">Гравців:</span>
                  <select v-model="selectedPlayers"
                          class="bg-white border border-secondary rounded-lg px-3 py-1.5 font-bold text-text outline-none focus:ring-2 focus:ring-primary cursor-pointer shadow-sm">
                    <option v-for="n in (room.max_players - room.min_players + 1)" :key="n" :value="room.min_players + n - 1">
                      {{ room.min_players + n - 1 }} чол.
                    </option>
                  </select>
                </div>
                <div class="flex justify-between items-center pt-4 border-t border-secondary border-dashed mt-2">
                  <span class="text-gray-500">До сплати:</span>
                  <span class="text-3xl font-black text-text">{{ currentPrice }} ₴</span>
                </div>
              </div>
              <div class="pt-2">
                <label class="block text-sm font-bold text-gray-700 mb-3">Спосіб оплати</label>
                <div class="grid grid-cols-2 gap-3">
                  <label class="relative flex items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition-all"
                         :class="bookingForm.payment_method === 'cash' ? 'border-primary bg-primary/5 text-primary shadow-sm' : 'border-secondary text-gray-500 hover:border-primary'">
                    <input type="radio" v-model="bookingForm.payment_method" value="cash" class="sr-only">
                    <span class="font-bold">Готівкою на місці</span>
                  </label>
                  <label class="relative flex items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition-all"
                         :class="bookingForm.payment_method === 'card' ? 'border-primary bg-primary/5 text-primary shadow-sm' : 'border-secondary text-gray-500 hover:border-primary'">
                    <input type="radio" v-model="bookingForm.payment_method" value="card" class="sr-only">
                    <span class="font-bold">Карткою зараз</span>
                  </label>
                </div>
              </div>
              <div class="mt-auto pt-4">
                <button type="submit" :disabled="isSubmitting"
                        class="w-full bg-primary hover:bg-purple-500 text-white font-black text-lg py-4 rounded-xl shadow-lg shadow-primary/30 transition-all disabled:opacity-70 disabled:transform-none transform hover:-translate-y-1">
                  <span v-if="isSubmitting">Відправка заявки...</span>
                  <span v-else>Підтвердити бронювання</span>
                </button>
                <p class="text-center text-xs text-gray-400 mt-4">
                  Натискаючи кнопку, ви погоджуєтеся з правилами квест-кімнати
                </p>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 8px; width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #E6E6FA; border-radius: 20px; }
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes scan {
  0% { transform: translateY(-100%); }
  100% { transform: translateY(200%); }
}
.animate-scan {
  animation: scan 3s linear infinite;
}
</style>