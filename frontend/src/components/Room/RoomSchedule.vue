<script setup>
import {computed} from 'vue';

const props = defineProps({
    /** @type {Object} The room data from the database */
    room: {type: Object, required: true},
    /** @type {Number} Currently selected players count */
    players: {type: Number, required: true},
    /** @type {Object|null} Currently selected time slot */
    selectedSlot: {type: Object, default: null}
});

const emit = defineEmits(['update:players', 'update:selectedSlot', 'slot-selected']);

/**
 * Format price from kopecks to UAH locally.
 */
const formatPrice = (kopecks) => kopecks ? kopecks / 100 : 0;

/**
 * Generates the 7-day schedule with available and booked time slots.
 */
const schedule = computed(() => {
    if (!props.room || !props.players) return [];

    const days = [];
    const today = new Date();
    const now = new Date();
    const ukrDays = ['НД', 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ'];

    const duration = props.room.duration_minutes || 60;
    const prepTime = 30;
    const step = duration + prepTime;
    const extraPlayers = props.players - props.room.min_players;
    const surcharge = extraPlayers > 0 ? extraPlayers * 10000 : 0; // In kopecks

    const timeSlots = [];
    let currentMinutes = 8 * 60;
    const endMinutes = 23 * 60;

    while (currentMinutes <= endMinutes) {
        const hours = Math.floor(currentMinutes / 60);
        const mins = currentMinutes % 60;
        timeSlots.push(`${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`);
        currentMinutes += step;
    }

    for (let i = 0; i < 7; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);
        const isWeekend = d.getDay() === 0 || d.getDay() === 6;

        // Base price in kopecks
        const basePriceKopecks = isWeekend ? props.room.weekend_price : props.room.weekday_price;
        const backendDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

        const slots = timeSlots.map(time => {
            const [hour, minute] = time.split(':').map(Number);
            const slotDateObj = new Date(d);
            slotDateObj.setHours(hour, minute, 0, 0);

            const slotStartTime = slotDateObj.getTime();
            const slotEndTime = slotStartTime + (duration * 60 * 1000);
            const isPast = slotStartTime < now.getTime();

            // Check if booked
            const isBooked = props.room.bookings?.some(b => {
                const bStart = new Date(b.start_time.replace(' ', 'T')).getTime();
                const bEnd = new Date(b.end_time.replace(' ', 'T')).getTime();
                return slotStartTime < bEnd && slotEndTime > bStart;
            }) || false;

            const isLate = hour >= 21;
            const lateSurchargeKopecks = isLate ? 20000 : 0; // 200 UAH
            const slotBaseKopecks = basePriceKopecks + lateSurchargeKopecks;
            const finalPriceKopecks = slotBaseKopecks + surcharge;

            return {
                time,
                price: formatPrice(finalPriceKopecks),
                basePriceKopecks: slotBaseKopecks,
                isPast,
                isBooked
            };
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

/**
 * Handles slot selection and emits to parent.
 */
const selectSlot = (day, slot) => {
    emit('update:selectedSlot', {
        date: day.dateStr,
        backendDate: day.backendDate,
        time: slot.time,
        basePriceKopecks: slot.basePriceKopecks // Keep math in kopecks
    });
    emit('slot-selected');
};
</script>

<template>
    <div>
        <div class="mb-8 border-b border-secondary pb-6">
            <h2 class="text-3xl font-bold text-text mb-6">Оберіть кількість гравців та час гри</h2>
            <div>
                <p class="text-sm font-bold text-gray-500 mb-3">Скільки вас буде?</p>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="n in (room.max_players - room.min_players + 1)" :key="n"
                        @click="emit('update:players', room.min_players + n - 1)"
                        :class="players === (room.min_players + n - 1) ? 'bg-primary text-white shadow-md transform scale-105 border-primary' : 'bg-transparent border-secondary text-gray-600 hover:border-primary'"
                        class="w-14 h-14 cursor-pointer rounded-2xl border-2 font-black text-lg flex items-center justify-center transition-all duration-200">
                        {{ room.min_players + n - 1 }}
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div v-for="day in schedule" :key="day.dateStr"
                 class="flex flex-col md:flex-row border-b-2 border-secondary pb-8 mb-8 last:border-0 last:pb-0 last:mb-0">
                <div class="md:w-48 shrink-0 flex items-center mb-4 md:mb-0">
                    <div class="text-lg font-black" :class="day.isWeekend ? 'text-primary' : 'text-text'">
                        {{ day.dateStr }}
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="slot in day.slots" :key="slot.time"
                        :disabled="slot.isPast || slot.isBooked"
                        @click="!(slot.isPast || slot.isBooked) && selectSlot(day, slot)"
                        :class="[
              'flex flex-col cursor-pointer items-center justify-center px-4 py-2 rounded-xl border-2 transition-all duration-200 min-w-[80px]',
              (slot.isPast || slot.isBooked)
                ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed opacity-60'
                : selectedSlot?.date === day.dateStr && selectedSlot?.time === slot.time
                  ? 'border-primary bg-primary text-white shadow-md transform scale-105'
                  : 'border-secondary bg-transparent hover:border-primary text-text hover:bg-secondary/30'
            ]">
                        <span class="text-lg font-bold">{{ slot.time }}</span>
                        <span v-if="slot.isBooked"
                              class="text-[10px] font-black uppercase text-red-400 mt-1 tracking-widest">Зайнято</span>
                        <span v-else class="text-xs font-medium opacity-80 mt-1">{{ slot.price }} ₴</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
