<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const room = ref(null);
const isLoading = ref(true);

const calendarSection = ref(null);
const bookingPanel = ref(null);

const selectedSlot = ref(null);

const formatPrice = (kopecks) => {
  return kopecks ? kopecks / 100 : 0;
};

const fetchRoom = async () => {
  try {
    const response = await axios.get(`http://localhost:8080/api/rooms/${route.params.slug}`);
    room.value = response.data.data || response.data;
  } catch (error) {
    console.error('Помилка завантаження кімнати:', error);
  } finally {
    isLoading.value = false;
  }
};

const schedule = computed(() => {
  if (!room.value) return [];

  const days = [];
  const today = new Date();
  const ukrDays = ['НД', 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ'];

  const duration = room.value.duration_minutes || 60;
  const prepTime = 30;
  const step = duration + prepTime;

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

    const slots = timeSlots.map(time => {
      const hour = parseInt(time.split(':')[0]);
      const isLate = hour >= 21;
      const finalPrice = basePrice + (isLate ? 200 : 0);

      return { time, price: finalPrice };
    });

    days.push({
      dateStr: `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')} ${ukrDays[d.getDay()]}`,
      isWeekend,
      slots
    });
  }
  return days;
});

const scrollToCalendar = () => {
  if (calendarSection.value) {
    calendarSection.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

const selectSlot = (dateStr, slot) => {
  selectedSlot.value = {
    date: dateStr,
    time: slot.time,
    price: slot.price
  };

  setTimeout(() => {
    if (bookingPanel.value) {
      bookingPanel.value.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, 50);
};

onMounted(() => {
  fetchRoom();
});
</script>

<template>
  <div class="py-10 px-6 max-w-6xl mx-auto">

    <div v-if="isLoading" class="text-center text-xl text-primary animate-pulse py-20">
      Завантаження даних кімнати...
    </div>

    <div v-else-if="room" class="space-y-16">

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

        <div class="lg:col-span-2 space-y-8">

          <div class="w-full flex overflow-x-auto snap-x snap-mandatory gap-4 pb-4 custom-scrollbar">
            <template v-if="room.images && room.images.length > 0">
              <div v-for="img in room.images" :key="img.id" class="snap-center shrink-0 w-full md:w-4/5 h-[400px] rounded-3xl overflow-hidden relative shadow-sm">
                <img :src="img.path || img.image_path" :alt="room.name" class="w-full h-full object-cover" />
              </div>
            </template>
            <template v-else-if="room.image_path">
              <div class="snap-center shrink-0 w-full h-[400px] rounded-3xl overflow-hidden relative shadow-sm">
                <img :src="room.image_path" :alt="room.name" class="w-full h-full object-cover" />
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
        </div>

        <div class="lg:col-span-1" ref="bookingPanel">
          <div class="sticky top-28 bg-white p-8 rounded-3xl shadow-lg border border-secondary flex flex-col items-center text-center">

            <div v-if="selectedSlot" class="w-full flex flex-col items-center animate-fade-in">
              <p class="text-gray-500 font-medium mb-1">Ви обрали час:</p>
              <p class="text-2xl font-bold text-text mb-6">
                {{ selectedSlot.date }} о <span class="text-primary">{{ selectedSlot.time }}</span>
              </p>

              <p class="text-sm text-gray-500">До сплати</p>
              <p class="text-4xl font-black text-text mb-8">{{ selectedSlot.price }} ₴</p>

              <button class="w-full bg-primary hover:bg-purple-500 text-white font-bold py-4 rounded-xl transition-colors shadow-lg shadow-primary/30">
                Перейти до бронювання
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

        <div class="flex justify-between items-center mb-8 border-b border-secondary pb-4">
          <h2 class="text-3xl font-bold text-text">Оберіть час гри</h2>
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
                  @click="selectSlot(day.dateStr, slot)"
                  :class="[
                  'flex flex-col items-center justify-center px-4 py-2 rounded-xl border-2 transition-all duration-200 min-w-[80px]',
                  selectedSlot?.date === day.dateStr && selectedSlot?.time === slot.time
                    ? 'border-primary bg-primary text-white shadow-md transform scale-105'
                    : 'border-secondary bg-transparent hover:border-primary text-text hover:bg-secondary/30'
                ]"
              >
                <span class="text-lg font-bold">{{ slot.time }}</span>
                <span class="text-xs font-medium opacity-80 mt-1">{{ slot.price }} ₴</span>
              </button>
            </div>

          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #E6E6FA; border-radius: 20px; }
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
</style>