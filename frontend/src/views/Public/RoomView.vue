<script setup>
import {ref, onMounted, computed, onUnmounted, watch} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import axios from 'axios';
import {useToastStore} from '@/stores/toast';
import RoomGallery from '@/components/Room/RoomGallery.vue';
import RoomMiniGame from '@/components/Room/RoomMiniGame.vue';
import RoomSchedule from '@/components/Room/RoomSchedule.vue';
import RoomBookingModal from '@/components/Room/RoomBookingModal.vue';
import GuestReviewModal from '@/components/Room/GuestReviewModal.vue';
import PaginationControls from '@/components/UI/PaginationControls.vue';

const route = useRoute();
const router = useRouter();
const toast = useToastStore();

// Main State
const room = ref(null);
const reviews = ref([]);
const isLoading = ref(true);

// Reviews Pagination
const reviewsPage = ref(1);
const reviewsTotalPages = ref(1);
const reviewsTotalItems = ref(0);
const reviewsPerPage = ref(5);

// Booking State
const selectedPlayers = ref(0);
const selectedSlot = ref(null);
const holdToken = ref(sessionStorage.getItem('questroom_hold_token') || Math.random().toString(36).substring(2, 15));
sessionStorage.setItem('questroom_hold_token', holdToken.value);

const isModalOpen = ref(false);
const isHoldSubmitting = ref(false);

const isReviewModalOpen = ref(false);
const reviewTokenUrl = ref('');

// Refs for scrolling
const calendarSection = ref(null);
const bookingPanel = ref(null);
/**
 * Total price calculation in kopecks.
 */
const currentPriceKopecks = computed(() => {
    if (!selectedSlot.value || !room.value) return 0;
    const extraPlayers = selectedPlayers.value - room.value.min_players;
    const surcharge = extraPlayers > 0 ? extraPlayers * 10000 : 0;
    return selectedSlot.value.basePriceKopecks + surcharge;
});

/**
 * Fetch Reviews with pagination.
 */
const fetchReviews = async (page = 1) => {
    if (!room.value) return;
    try {
        const revRes = await axios.get(`/rooms/${room.value.id}/reviews?page=${page}&per_page=${reviewsPerPage.value}`);
        reviews.value = revRes.data.data;
        const meta = revRes.data.meta || revRes.data;
        reviewsPage.value = meta.current_page || 1;
        reviewsTotalPages.value = meta.last_page || 1;
        reviewsTotalItems.value = meta.total || 0;
    } catch (error) {
        console.error('Помилка завантаження відгуків', error);
    }
};

watch(reviewsPage, (newVal) => {
    fetchReviews(newVal);
});

/**
 * Fetch Room and Reviews.
 */
const fetchRoomData = async () => {
    try {
        const res = await axios.get(`/rooms/${route.params.slug}`);
        const fetchedRoom = res.data.data || res.data;
        if (!fetchedRoom.is_active) return router.replace({name: 'not-found'});

        room.value = fetchedRoom;
        if (selectedPlayers.value === 0) selectedPlayers.value = room.value.min_players; // Set default once

        await fetchReviews(reviewsPage.value);
    } catch (error) {
        if (error.response?.status === 404) router.replace({name: 'not-found'});
    } finally {
        isLoading.value = false;
    }
};
/**
 * Hold the slot via API before opening the modal.
 */
const startBookingProcess = async () => {
    isHoldSubmitting.value = true;
    try {
        const backendStartTime = `${selectedSlot.value.backendDate} ${selectedSlot.value.time}:00`;
        await axios.post('/bookings/hold', {
            room_id: room.value.id,
            start_time: backendStartTime,
            hold_token: holdToken.value
        });
        isModalOpen.value = true;
    } catch (error) {
        toast.info(error.response?.data?.message || 'Помилка. Оберіть інший час.');
        selectedSlot.value = null;
    } finally {
        isHoldSubmitting.value = false;
    }
};

/**
 * Release the slot via API.
 */
const releaseSlot = () => {
    if (!selectedSlot.value) return;
    try {
        const backendStartTime = `${selectedSlot.value.backendDate} ${selectedSlot.value.time}:00`;
        axios.post('/bookings/release', {
            room_id: room.value.id,
            start_time: backendStartTime,
            hold_token: holdToken.value
        });
    } catch (error) {
        console.error('Release failed', error);
    }
};

/**
 * Handle success, timeout or manual close of Modal.
 */
const handleModalClose = () => {
    releaseSlot();
    isModalOpen.value = false;
    selectedSlot.value = null;
};

const handleTimeout = (msg) => {
    handleModalClose();
    toast.info(msg || 'Час на оформлення вичерпано. Оберіть час ще раз.');
    fetchRoomData();
};

const handleBookingSuccess = () => {
    isModalOpen.value = false;
    selectedSlot.value = null;

    sessionStorage.removeItem('questroom_hold_token');
    holdToken.value = sessionStorage.getItem('questroom_hold_token') || Math.random().toString(36).substring(2, 15);
    sessionStorage.setItem('questroom_hold_token', holdToken.value);

    toast.success('Бронювання успішно створено! Очікуйте дзвінка.');
    fetchRoomData();
};

// Lifecycle Hooks
const handleBeforeUnload = () => {
    if (isModalOpen.value && selectedSlot.value) {
        const payload = JSON.stringify({
            room_id: room.value.id,
            start_time: `${selectedSlot.value.backendDate} ${selectedSlot.value.time}:00`,
            hold_token: holdToken.value
        });
        const apiUrl = `${import.meta.env.VITE_API_URL}/bookings/release`;

        navigator.sendBeacon(apiUrl, new Blob([payload], {type: 'application/json'}));
    }
};

onMounted(() => {
    fetchRoomData();
    window.addEventListener('beforeunload', handleBeforeUnload);

    if (route.query.review_token) {
        reviewTokenUrl.value = route.query.review_token;
        isReviewModalOpen.value = true;
        router.replace({ query: {} });
    }

    if (window.Echo) {
        window.Echo.channel('manager-channel')
            .listen('.booking.created', fetchRoomData)
            .listen('.booking.updated', fetchRoomData);
    }
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    if (window.Echo) {
        window.Echo.leave('manager-channel');
    }
});
</script>

<template>
    <div class="py-10 px-6 max-w-6xl mx-auto relative">
        <div v-if="isLoading" class="text-center text-xl text-primary animate-pulse py-20">Завантаження...</div>

        <div v-else-if="room" class="space-y-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2 space-y-8">
                    <RoomGallery :image-path="room.image_path" :room-name="room.name"/>

                    <div>
                        <span class="bg-primary text-white px-3 py-1 rounded-full text-xs font-bold uppercase">{{
                                room.difficulty
                            }}</span>
                        <h1 class="text-3xl md:text-4xl font-black text-text mt-3 mb-4">{{ room.name }}</h1>
                        <div class="flex gap-6 text-gray-500 font-medium">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>{{ room.min_players }} - {{ room.max_players }} гравців</span>
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ room.duration_minutes }} хв гри</span>
                        </div>
                    </div>

                    <div class="prose max-w-none text-text">
                        <h2 class="text-2xl font-bold mb-4">Про квест</h2>
                        <p class="whitespace-pre-line break-words">{{ room.description }}</p>
                    </div>

                    <RoomMiniGame v-if="room.hint_phrase" :hint-phrase="room.hint_phrase"/>
                </div>

                <div class="lg:col-span-1" ref="bookingPanel">
                    <div class="sticky top-28 bg-white p-8 rounded-3xl shadow-lg border border-secondary text-center">
                        <div v-if="selectedSlot" class="animate-fade-in">
                            <p class="text-gray-500 font-medium mb-1">Ви обрали час:</p>
                            <p class="text-2xl font-bold text-text mb-2">{{ selectedSlot.date }} о <span
                                class="text-primary">{{ selectedSlot.time }}</span></p>
                            <div class="bg-secondary/30 text-primary font-bold px-4 py-2 rounded-full mb-6 text-sm">
                                Гравців: {{ selectedPlayers }}
                            </div>
                            <p class="text-sm text-gray-500">До сплати</p>
                            <p class="text-4xl font-black text-text mb-8">{{ currentPriceKopecks / 100 }} ₴</p>
                            <button @click="startBookingProcess" :disabled="isHoldSubmitting"
                                    class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg hover:bg-purple-500 disabled:opacity-70 transition">
                                {{ isHoldSubmitting ? 'Перевірка часу...' : 'Перейти до бронювання' }}
                            </button>
                            <button @click="selectedSlot = null" class="mt-4 text-sm text-gray-400 hover:text-primary">
                                Обрати інший час
                            </button>
                        </div>

                        <div v-else>
                            <h3 class="text-xl font-bold text-text mb-6">Вартість гри</h3>
                            <div class="flex justify-between mb-4"><span class="text-gray-500">Будні</span><span
                                class="text-2xl font-bold">{{ room.weekday_price / 100 }} ₴</span></div>
                            <div class="flex justify-between mb-6 border-t pt-4"><span
                                class="text-gray-500">Вихідні</span><span
                                class="text-2xl font-black text-primary">{{ room.weekend_price / 100 }} ₴</span></div>
                            <div
                                class="mb-8 text-sm text-left text-gray-500 bg-secondary/30 p-4 rounded-xl border border-secondary">
                                <p class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Вечірні сеанси (з 21:00) дорожчі на <span
                                        class="font-bold text-primary">200 ₴</span></span>
                                </p>
                            </div>
                            <button @click="() => calendarSection.scrollIntoView({ behavior: 'smooth' })"
                                    class="w-full cursor-pointer bg-primary text-white font-bold py-4 rounded-xl shadow-md hover:opacity-90">
                                Вибрати час
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div ref="calendarSection" class="bg-white rounded-3xl shadow-sm border border-secondary p-8 scroll-mt-28">
                <RoomSchedule
                    :room="room"
                    v-model:players="selectedPlayers"
                    v-model:selected-slot="selectedSlot"
                    @slot-selected="() => bookingPanel.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                />
            </div>

            <div class="mt-12 pt-12 border-t border-secondary">
                <h2 class="text-2xl font-bold mb-6 text-text">Відгуки гравців ({{ reviews.length }})</h2>
                <div v-if="reviews.length === 0"
                     class="bg-gray-50 p-6 text-center text-gray-500 border border-secondary rounded-2xl">Поки що немає
                    відгуків.
                </div>
                <div v-else class="space-y-4">
                    <div v-for="review in reviews" :key="review.id"
                         class="bg-white p-6 rounded-2xl border border-secondary shadow-sm">
                        <div class="flex justify-between items-start mb-2">
                            <div class="font-bold text-text">{{ review.guest_name || review.user?.name || 'Гість' }}</div>
                            <div class="flex text-yellow-400">
                                <svg v-for="i in 5" :key="i" class="w-4 h-4"
                                     :class="i <= review.rating ? 'fill-current' : 'text-gray-200 fill-current'"
                                     viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-600 italic text-sm mb-3">"{{ review.message }}"</p>
                        <div class="text-xs text-gray-400">{{
                                new Date(review.created_at).toLocaleDateString('uk-UA')
                            }}
                        </div>
                    </div>
                    <PaginationControls
                        v-model:current-page="reviewsPage"
                        :total-pages="reviewsTotalPages"
                        :total-items="reviewsTotalItems"
                        :items-per-page="reviewsPerPage"
                    />
                </div>
            </div>
        </div>

        <RoomBookingModal
            v-if="room && selectedSlot"
            :is-open="isModalOpen"
            :room="room"
            :selected-slot="selectedSlot"
            :players="selectedPlayers"
            :total-price-kopecks="currentPriceKopecks"
            :hold-token="holdToken"
            @close="handleModalClose"
            @timeout="handleTimeout"
            @success="handleBookingSuccess"
        />

        <GuestReviewModal
            :is-open="isReviewModalOpen"
            :review-token-url="reviewTokenUrl"
            @close="isReviewModalOpen = false"
            @success="() => { isReviewModalOpen = false; fetchRoomData(); }"
        />
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
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
