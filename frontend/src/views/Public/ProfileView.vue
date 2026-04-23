<script setup>
import {ref, onMounted, computed, watch} from 'vue';
import axios from 'axios';
import {useAuthStore} from '@/stores/auth';
import {useToastStore} from '@/stores/toast';

// Import Shared Logic (Composables)
import {useFormatters} from '@/composables/useFormatters';
import {useRoomHelpers} from '@/composables/useRoomHelpers';

const toast = useToastStore();
const authStore = useAuthStore();

// Shared Utilities
const {formatPrice, formatFullDate} = useFormatters();
const {getFirstImage} = useRoomHelpers();

// State
const activeBookings = ref([]);
const pastBookings = ref([]);
const historyPage = ref(1);
const historyTotalPages = ref(1);

const isLoading = ref(true);
const selectedBooking = ref(null);
const isDownloadingTicket = ref(false);

// Modals State
const isModalOpen = ref(false);
const isEditProfileModalOpen = ref(false);
const isChangePasswordModalOpen = ref(false);
const isReviewModalOpen = ref(false);

// Forms
const profileForm = ref({name: '', phone: ''});
const passwordForm = ref({current_password: '', password: '', password_confirmation: ''});
const reviewForm = ref({room_id: null, room_name: '', rating: 5, message: ''});

// Dictionaries
const statusClasses = {
    pending: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    finished: 'bg-blue-100 text-blue-700'
};
const statusNames = {
    pending: 'Очікує', confirmed: 'Підтверджено', cancelled: 'Скасовано', finished: 'Завершено'
};

/**
 * Fetch all bookings for the authenticated user via independent server request logic.
 */
const fetchActiveBookings = async () => {
    try {
        const response = await axios.get('/user/bookings?type=active');
        activeBookings.value = response.data;
    } catch (error) {
        console.error('Помилка завантаження активних бронювань:', error);
    }
};

const fetchPastBookings = async (page = 1) => {
    try {
        const response = await axios.get(`/user/bookings?type=past&page=${page}`);
        pastBookings.value = response.data.data || response.data;
        const meta = response.data.meta || response.data;
        historyPage.value = meta.current_page || 1;
        historyTotalPages.value = meta.last_page || 1;
    } catch (error) {
        console.error('Помилка завантаження історії бронювань:', error);
    }
};

const fetchMyBookings = async () => {
    isLoading.value = true;
    await fetchActiveBookings();
    isLoading.value = false;

    // Load history after first meaningful paint for faster perceived response.
    fetchPastBookings(historyPage.value);
};

watch(historyPage, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        fetchPastBookings(newVal);
    }
});

/**
 * Fetch detailed view for a single booking modal.
 */
const viewDetails = async (id) => {
    try {
        const response = await axios.get(`/bookings/${id}`);
        selectedBooking.value = response.data.data || response.data;
        isModalOpen.value = true;
    } catch (error) {
        toast.error('Не вдалося завантажити деталі бронювання.');
    }
};

// Profile actions

const openEditProfileModal = () => {
    profileForm.value.name = authStore.user?.name || '';
    profileForm.value.phone = authStore.user?.phone || '';
    isEditProfileModalOpen.value = true;
};

const handlePhoneInput = (event) => {
    let input = event.target.value.replace(/\D/g, '');
    if (!input.startsWith('380')) input = '380' + input.replace(/^380/, '');
    input = input.substring(0, 12);
    let formatted = '+380';
    if (input.length > 3) formatted += ' (' + input.substring(3, 5);
    if (input.length > 5) formatted += ') ' + input.substring(5, 8);
    if (input.length > 8) formatted += '-' + input.substring(8, 10);
    if (input.length > 10) formatted += '-' + input.substring(10, 12);
    profileForm.value.phone = formatted;
};

const submitProfileUpdate = async () => {
    if (profileForm.value.phone === '+380 ' || profileForm.value.phone === '+380') profileForm.value.phone = '';
    if (profileForm.value.phone && profileForm.value.phone.length < 19) {
        return toast.warning('Будь ласка, введіть повний номер телефону (10 цифр).');
    }

    try {
        await axios.put('/user/profile', profileForm.value);
        await authStore.fetchUser();
        toast.success('Профіль успішно оновлено!');
        isEditProfileModalOpen.value = false;
    } catch (error) {
        toast.error(error.response?.data?.message || 'Помилка оновлення профілю');
    }
};

const submitPasswordUpdate = async () => {
    try {
        await axios.put('/user/password', passwordForm.value);
        toast.success(authStore.user.has_password ? 'Пароль успішно змінено!' : 'Пароль успішно встановлено!');
        isChangePasswordModalOpen.value = false;
        authStore.user.has_password = true;
        passwordForm.value = {current_password: '', password: '', password_confirmation: ''};
    } catch (error) {
        toast.warning(error.response?.data?.message || 'Помилка зміни пароля. Перевірте правильність поточного пароля.');
    }
};

// Review actions

const openReviewModal = (booking) => {
    reviewForm.value = {room_id: booking.room?.id, room_name: booking.room?.name, rating: 5, message: ''};
    isReviewModalOpen.value = true;
};

const submitReview = async () => {
    try {
        await axios.post('/reviews', {
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

const downloadTicket = async (bookingId) => {
    isDownloadingTicket.value = true;
    try {
        const response = await axios.get(`/bookings/${bookingId}/ticket`, {
            responseType: 'blob',
            headers: {
                'Accept': 'application/pdf'
            }
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `ticket_${bookingId}.pdf`);
        document.body.appendChild(link);
        link.click();

        link.parentNode.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        toast.error('Не вдалося завантажити квиток');
        console.error('Download ticket error:', error);
    } finally {
        isDownloadingTicket.value = false;
    }
};

onMounted(() => fetchMyBookings());
</script>

<template>
    <div class="py-10 px-6 max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-secondary text-center sticky top-28">
                    <div
                        class="w-32 h-32 mx-auto rounded-full bg-primary/10 flex items-center justify-center text-primary text-5xl font-black mb-6 border-4 border-white shadow-lg">
                        {{ authStore.user?.name?.charAt(0) || 'U' }}
                    </div>
                    <h1 class="text-2xl font-black text-text mb-1">{{ authStore.user?.name }}</h1>
                    <p class="text-gray-500 font-medium mb-6">{{ authStore.user?.email }}</p>
                    <p class="text-gray-500 font-medium mb-6 text-sm">{{
                            authStore.user?.phone || 'Телефон не вказано'
                        }}</p>

                    <div class="bg-gray-50 p-4 rounded-2xl text-left border border-gray-100 space-y-3">
                        <div>
                            <span
                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Дата реєстрації</span>
                            <span class="font-bold text-text">{{
                                    new Date(authStore.user?.created_at).toLocaleDateString('uk-UA')
                                }}</span>
                        </div>
                    </div>
                    <button @click="openEditProfileModal"
                            class="w-full cursor-pointer bg-gray-100 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-200 transition-colors">
                        Редагувати профіль
                    </button>
                    <button @click="isChangePasswordModalOpen = true"
                            class="w-full cursor-pointer bg-gray-100 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-200 transition-colors">
                        {{ authStore.user.has_password ? 'Змінити пароль' : 'Встановити пароль' }}
                    </button>
                    <button @click="authStore.logout"
                            class="w-full cursor-pointer mt-6 bg-red-50 text-red-600 font-bold py-3 rounded-xl hover:bg-red-600 hover:text-white transition-colors">
                        Вийти з акаунта
                    </button>
                </div>
            </div>

            <div class="lg:col-span-2">
                <h2 class="text-3xl font-black text-text mb-8">Мої бронювання</h2>

                <div v-if="isLoading" class="space-y-6 min-h-[700px]">
                    <div class="bg-white p-6 rounded-2xl border border-secondary animate-pulse">
                        <div class="h-5 w-40 bg-gray-200 rounded mb-4"></div>
                        <div class="space-y-4">
                            <div v-for="index in 3" :key="'active-skeleton-' + index" class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-xl bg-gray-200 shrink-0"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 w-1/2 bg-gray-200 rounded"></div>
                                    <div class="h-3 w-1/3 bg-gray-200 rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-secondary animate-pulse">
                        <div class="h-5 w-24 bg-gray-200 rounded mb-4"></div>
                        <div class="space-y-3">
                            <div v-for="index in 4" :key="'past-skeleton-' + index" class="h-12 bg-gray-200 rounded"></div>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-10">
                    <section>
                        <h3 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-green-500"></span> Актуальні
                        </h3>
                        <div v-if="activeBookings.length === 0"
                             class="bg-white p-8 rounded-3xl border border-secondary text-gray-500 text-center">
                            У вас немає запланованих ігор.
                        </div>
                        <div class="space-y-4">
                            <div v-for="booking in activeBookings" :key="booking.id"
                                 class="bg-white p-4 sm:p-5 rounded-2xl border border-secondary flex flex-col sm:flex-row justify-between sm:items-center gap-4 hover:border-primary transition-colors">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden w-full sm:w-auto">
                                    <img v-if="booking.room?.image_path" :src="getFirstImage(booking.room.image_path)"
                                         loading="lazy"
                                         decoding="async"
                                         class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl object-cover shrink-0"/>
                                    <div class="min-w-0"><h4 class="font-bold text-base sm:text-lg text-text truncate"
                                                             :title="booking.room?.name">
                                        {{ booking.room?.name }}
                                    </h4>
                                        <p class="text-xs sm:text-sm font-bold text-primary">
                                            {{ formatFullDate(booking.start_time) }}</p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-4 shrink-0">
                  <span
                      class="px-2 py-1 sm:px-3 sm:py-1 rounded-lg text-[10px] sm:text-xs font-black uppercase tracking-wider"
                      :class="statusClasses[booking.status]">
                    {{ statusNames[booking.status] }}
                  </span>
                                    <button @click="viewDetails(booking.id)"
                                            class="text-gray-400 hover:text-primary p-2 cursor-pointer bg-gray-50 rounded-lg sm:bg-transparent">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 5l7 7-7 7"></path>
                                        </svg>
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
                            <div v-for="booking in pastBookings" :key="booking.id"
                                 class="bg-white p-4 sm:p-5 rounded-2xl border border-secondary flex flex-col sm:flex-row justify-between sm:items-center gap-4 opacity-80 hover:opacity-100 transition-opacity">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden w-full sm:w-auto">
                                    <img v-if="booking.room?.image_path" :src="getFirstImage(booking.room.image_path)"
                                         loading="lazy"
                                         decoding="async"
                                         class="w-12 h-12 rounded-xl object-cover shrink-0 grayscale"/>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-sm sm:text-base text-text truncate"
                                            :title="booking.room?.name">
                                            {{ booking.room?.name }}
                                        </h4>
                                        <p class="text-[10px] sm:text-xs text-gray-500">
                                            {{ formatFullDate(booking.start_time) }}</p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-3 shrink-0">
                  <span class="px-2 py-1 rounded-lg text-[9px] sm:text-[10px] font-black uppercase tracking-wider"
                        :class="statusClasses[booking.status]">
                    {{ statusNames[booking.status] }}
                  </span>
                                    <div class="flex items-center gap-2">
                                        <button v-if="booking.status === 'finished'" @click="openReviewModal(booking)"
                                                class="text-xs sm:text-sm font-bold text-primary hover:underline cursor-pointer bg-primary/10 px-2 py-1 rounded-lg">
                                            Відгук
                                        </button>
                                        <button @click="viewDetails(booking.id)"
                                                class="text-gray-400 hover:text-primary p-1.5 cursor-pointer bg-gray-50 rounded-lg">
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="historyTotalPages > 1"
                                 class="flex justify-center items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                                <button @click="historyPage--" :disabled="historyPage === 1"
                                        class="px-4 py-2 rounded-xl text-sm font-bold transition-colors"
                                        :class="historyPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-primary bg-secondary hover:bg-purple-100'">
                                    &larr; Назад
                                </button>
                                <span class="text-sm font-bold text-gray-500">{{ historyPage }} / {{
                                        historyTotalPages
                                    }}</span>
                                <button @click="historyPage++" :disabled="historyPage === historyTotalPages"
                                        class="px-4 py-2 rounded-xl text-sm font-bold transition-colors"
                                        :class="historyPage === historyTotalPages ? 'text-gray-300 cursor-not-allowed' : 'text-primary bg-secondary hover:bg-purple-100'">
                                    Далі &rarr;
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <div v-if="isEditProfileModalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click="isEditProfileModalOpen = false">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-8" @click.stop>
                <h3 class="text-2xl font-black mb-6 text-text">Редагування профілю</h3>
                <form @submit.prevent="submitProfileUpdate" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Ім'я</label>
                        <input v-model="profileForm.name" type="text" required
                               class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
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
                        <button type="button" @click="isEditProfileModalOpen = false"
                                class="flex-1 px-4 py-3 font-bold text-gray-400 hover:text-text transition-colors cursor-pointer">
                            Скасувати
                        </button>
                        <button type="submit"
                                class="flex-1 bg-primary text-white font-black py-3 rounded-xl shadow-lg hover:bg-purple-600 transition-all cursor-pointer">
                            Зберегти
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div v-if="isChangePasswordModalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click="isChangePasswordModalOpen = false">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-8" @click.stop>
                <h3 class="text-2xl font-black mb-6 text-text">{{ authStore.user.has_password ? 'Зміна пароля' : 'Встановлення пароля' }}</h3>
                <form @submit.prevent="submitPasswordUpdate" class="space-y-4">
                    <div v-if="authStore.user.has_password">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Поточний пароль</label>
                        <input v-model="passwordForm.current_password" type="password" required
                               class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Новий пароль</label>
                        <input v-model="passwordForm.password" type="password" required minlength="8"
                               class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Підтвердіть пароль</label>
                        <input v-model="passwordForm.password_confirmation" type="password" required minlength="8"
                               class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none bg-gray-50 text-text font-medium">
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="isChangePasswordModalOpen = false"
                                class="flex-1 px-4 py-3 font-bold text-gray-400 hover:text-text transition-colors cursor-pointer">
                            Скасувати
                        </button>
                        <button type="submit"
                                class="flex-1 bg-primary text-white font-black py-3 rounded-xl shadow-lg hover:bg-purple-600 transition-all cursor-pointer">
                            Оновити
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="isModalOpen && selectedBooking"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click="isModalOpen = false">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden" @click.stop>
                <div class="bg-primary p-6 text-white flex justify-between items-center">
                    <h3 class="text-xl font-black">Деталі бронювання #{{ selectedBooking.id }}</h3>
                    <button @click="isModalOpen = false" class="text-white/70 hover:text-white cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-text">
                    <div class="flex justify-between border-b border-gray-100 pb-3">
                        <span class="text-gray-500">Кімната</span>
                        <span class="font-bold">{{ selectedBooking.room?.name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-3">
                        <span class="text-gray-500">Дата гри</span>
                        <span class="font-bold text-primary">{{ formatFullDate(selectedBooking.start_time) }}</span>
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
                        <button @click="downloadTicket(selectedBooking.id)" :disabled="isDownloadingTicket"
                           class="w-full bg-primary hover:bg-purple-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all shadow-md flex justify-center items-center gap-2">
                            <span v-if="isDownloadingTicket" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></span>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span v-if="isDownloadingTicket">Завантаження...</span>
                            <span v-else>Завантажити квиток (PDF)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isReviewModalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click="isReviewModalOpen = false">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-8" @click.stop>
                <h3 class="text-2xl font-black mb-2 text-text">Ваші враження від «{{ reviewForm.room_name }}»</h3>
                <p class="text-gray-500 mb-6 font-medium">Ваш відгук допоможе іншим обрати найкращий квест!</p>

                <div class="flex justify-center gap-2 mb-8">
                    <svg v-for="star in 5" :key="star"
                         @click="reviewForm.rating = star"
                         class="w-10 h-10 star-rating transition-all"
                         :class="star <= reviewForm.rating ? 'text-yellow-400 fill-current' : 'text-gray-200 fill-current'"
                         viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>

                <textarea v-model="reviewForm.message" rows="4" placeholder="Що вам найбільше сподобалось?"
                          class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary outline-none mb-6 resize-none bg-gray-50 text-text font-medium"></textarea>

                <div class="flex gap-4">
                    <button @click="isReviewModalOpen = false"
                            class="flex-1 px-4 py-3 font-bold text-gray-400 hover:text-text transition-colors cursor-pointer">
                        Скасувати
                    </button>
                    <button @click="submitReview"
                            class="flex-1 bg-primary text-white font-black py-3 rounded-xl shadow-lg hover:bg-purple-600 transition-all cursor-pointer">
                        Надіслати
                    </button>
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
