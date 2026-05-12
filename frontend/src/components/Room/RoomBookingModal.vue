<script setup>
import {ref, computed, watch, onUnmounted} from 'vue';
import axios from 'axios';
import {useAuthStore} from '@/stores/auth';
import {vMaska} from "maska/vue";

const props = defineProps({
    isOpen: {type: Boolean, required: true},
    room: {type: Object, required: true},
    selectedSlot: {type: Object, required: true},
    players: {type: Number, required: true},
    totalPriceKopecks: {type: Number, required: true},
    holdToken: {type: String, required: true}
});

const emit = defineEmits(['close', 'success', 'timeout']);
const authStore = useAuthStore();

// State
const isSubmitting = ref(false);
const validationErrors = ref({});
const errorMessage = ref('');
const timeLeft = ref(600);
const timerInterval = ref(null);

const bookingForm = ref({
    name: '', phone: '', email: '', comment: '', payment_method: 'cash'
});

// Sync Auth Data
watch(() => authStore.user, (user) => {
    if (user) {
        bookingForm.value.name = user.name || '';
        bookingForm.value.email = user.email || '';
        bookingForm.value.phone = user.phone || '';
    }
}, {immediate: true});

// Timer Logic
watch(() => props.isOpen, (newVal) => {
    if (newVal) startTimer();
    else clearInterval(timerInterval.value);
});

const formattedTimeLeft = computed(() => {
    const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0');
    const s = (timeLeft.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

const startTimer = () => {
    timeLeft.value = 600;
    clearInterval(timerInterval.value);
    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) timeLeft.value--;
        else {
            clearInterval(timerInterval.value);
            emit('timeout'); // Tell parent the time is up
        }
    }, 1000);
};

/**
 * Handle input mask for Ukrainian phone numbers.
 */

/**
 * Final submit to Backend API.
 */
const submitBooking = async () => {
    isSubmitting.value = true;
    validationErrors.value = {};
    errorMessage.value = '';

    try {
        const payload = {
            room_id: props.room.id,
            start_time: `${props.selectedSlot.backendDate} ${props.selectedSlot.time}:00`,
            duration_minutes: props.room.duration_minutes,
            players_count: props.players,
            total_price: props.totalPriceKopecks,
            guest_name: bookingForm.value.name,
            guest_phone: bookingForm.value.phone,
            guest_email: bookingForm.value.email,
            comment: bookingForm.value.comment,
            payment_method: bookingForm.value.payment_method,
            hold_token: props.holdToken
        };

        const response = await axios.post('/bookings', payload);
        const createdBooking = response.data;

        if (bookingForm.value.payment_method === 'card') {
            const paymentPayload = createdBooking.payment_token
                ? { payment_token: createdBooking.payment_token }
                : {};
            const paymentResponse = await axios.post(`/bookings/${createdBooking.id}/pay`, paymentPayload);
            window.location.href = paymentResponse.data.url;
            return;
        }

        emit('success');
    } catch (error) {
        if (error.response?.status === 409) emit('timeout', error.response.data.message);
        else if (error.response?.status === 429) {
            errorMessage.value = 'Забагато спроб. Спробуйте пізніше.';
        }
        else if (error.response?.status === 422 && error.response.data.errors) {
            validationErrors.value = error.response.data.errors;
            errorMessage.value = 'Будь ласка, перевірте правильність заповнення форми.';
        } else errorMessage.value = error.response?.data?.message || 'Помилка сервера.';
    } finally {
        isSubmitting.value = false;
    }
};

onUnmounted(() => clearInterval(timerInterval.value));
</script>

<template>
    <div v-if="isOpen"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
         @click="emit('close')">
        <div class="bg-white rounded-3xl w-full max-w-4xl shadow-2xl overflow-hidden" @click.stop>
            <div
                class="bg-primary p-4 md:p-6 text-white flex justify-between items-center relative overflow-hidden gap-2">
                <h3 class="text-lg md:text-2xl font-black relative z-10 truncate">Оформлення броні</h3>
                <div
                    class="relative z-10 flex items-center gap-1 md:gap-2 bg-white/20 px-2 py-1 md:px-3 md:py-1.5 rounded-lg border border-white/30 shrink-0"
                    :class="{'animate-pulse text-red-200': timeLeft < 60}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm md:text-base font-black font-mono tracking-wider">{{
                            formattedTimeLeft
                        }}</span>
                </div>
                <button @click="emit('close')"
                        class="relative z-10 text-white/70 hover:text-white transition-colors bg-white/10 p-1.5 md:p-2 rounded-full shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-4 md:p-8 max-h-[85vh] overflow-y-auto">
                <div v-if="errorMessage"
                     class="mb-6 p-3 bg-red-50 text-red-600 rounded-xl border border-red-200 font-bold flex items-start gap-2 shadow-sm text-sm">
                    <span>{{ errorMessage }}</span>
                </div>

                <form @submit.prevent="submitBooking" class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10">
                    <div class="space-y-4 md:space-y-5">
                        <h4 class="text-lg font-black text-text border-b border-secondary pb-2">Ваші дані</h4>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Ваше ім'я *</label>
                            <input v-model="bookingForm.name" type="text" required
                                   class="w-full px-3 py-2 border rounded-xl"
                                   :class="validationErrors.guest_name ? 'border-red-500' : 'border-secondary'">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Телефон *</label>
                            <input v-model="bookingForm.phone" v-maska data-maska="+380 (##) ###-##-##" type="tel"
                                   required
                                   placeholder="+380 (__) ___-__-__"
                                   class="w-full px-3 py-2 border rounded-xl font-bold tracking-wide"
                                   :class="validationErrors.guest_phone ? 'border-red-500' : 'border-secondary'">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Email (На цю пошту прийде квиток)</label>
                            <input v-model="bookingForm.email" type="email" class="w-full px-3 py-2 border rounded-xl"
                                   :class="validationErrors.guest_email ? 'border-red-500' : 'border-secondary'">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Коментар</label>
                            <textarea v-model="bookingForm.comment" rows="2"
                                      class="w-full px-3 py-2 border rounded-xl border-secondary"></textarea>
                        </div>
                    </div>

                    <div class="space-y-5 md:space-y-6 flex flex-col">
                        <h4 class="text-lg font-black text-text border-b border-secondary pb-2">Деталі гри</h4>
                        <div
                            class="bg-secondary/30 p-4 rounded-2xl text-sm font-medium border border-secondary space-y-3">
                            <div class="flex justify-between"><span>Кімната:</span><span class="font-bold">{{
                                    room.name
                                }}</span></div>
                            <div class="flex justify-between"><span>Час:</span><span
                                class="text-primary font-black">{{ selectedSlot.date }} о {{ selectedSlot.time }}</span>
                            </div>
                            <div class="flex justify-between"><span>Гравців:</span><span class="font-bold">{{ players }} чол.</span>
                            </div>
                            <div class="flex justify-between border-t border-secondary pt-3 mt-2">
                                <span>До сплати:</span><span class="text-2xl font-black">{{
                                    totalPriceKopecks / 100
                                }} ₴</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">Спосіб оплати</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex justify-center p-3 rounded-xl border-2 cursor-pointer"
                                       :class="bookingForm.payment_method === 'cash' ? 'border-primary bg-primary/5 text-primary' : 'border-secondary text-gray-500'">
                                    <input type="radio" v-model="bookingForm.payment_method" value="cash"
                                           class="hidden"><span class="font-bold text-sm">Готівка</span>
                                </label>
                                <label class="flex justify-center p-3 rounded-xl border-2 cursor-pointer"
                                       :class="bookingForm.payment_method === 'card' ? 'border-primary bg-primary/5 text-primary' : 'border-secondary text-gray-500'">
                                    <input type="radio" v-model="bookingForm.payment_method" value="card"
                                           class="hidden"><span class="font-bold text-sm">Картка</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" :disabled="isSubmitting"
                            class="mt-auto w-full cursor-pointer bg-primary text-white font-black py-4 rounded-xl border-2 border-primary shadow-lg hover:bg-white hover:text-primary hover:shadow-xl active:translate-y-[1px] disabled:opacity-70 disabled:cursor-not-allowed transition">
                            <span v-if="isSubmitting">Відправка...</span>
                            <span v-else>Підтвердити бронювання</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
</style>
