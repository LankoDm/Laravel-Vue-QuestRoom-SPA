<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToastStore } from '@/stores/toast';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true
    },
    reviewTokenUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['close', 'success']);
const toast = useToastStore();

const rating = ref(5);
const message = ref('');
const isSubmitting = ref(false);

const setRating = (val) => {
    rating.value = val;
};

const submitReview = async () => {
    if (rating.value < 1 || rating.value > 5) {
        toast.error('Будь ласка, вкажіть оцінку від 1 до 5.');
        return;
    }
    if (!message.value.trim() || message.value.length < 5) {
        toast.error('Залиште відгук (мінімум 5 символів).');
        return;
    }

    isSubmitting.value = true;
    try {
        await axios.post(props.reviewTokenUrl, {
            rating: rating.value,
            message: message.value.trim()
        });

        toast.success('Дякуємо за ваш відгук! Він відправлений на перевірку.');
        emit('success');
        
        rating.value = 5;
        message.value = '';
    } catch (error) {
        const msg = error.response?.data?.message || 'Помилка при відправці відгуку. Можливо, посилання застаріло.';
        toast.error(msg);
        emit('close');
    } finally {
        isSubmitting.value = false;
    }
};

</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full m-4 shadow-2xl relative z-10 animate-fade-in-up">
            <button @click="$emit('close')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <h2 class="text-2xl font-black text-text mb-4 text-center">Ваші враження від гри</h2>
            <p class="text-gray-500 text-center mb-6">Поділіться з іншими гравцями, як минув ваш квест! Що сподобалось найбільше?</p>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2 text-center">Ваша оцінка</label>
                <div class="flex justify-center gap-2 text-yellow-400 hover:cursor-pointer">
                    <svg v-for="i in 5" :key="i" class="w-8 h-8 transition-transform hover:scale-110" 
                         :class="i <= rating ? 'fill-current' : 'text-gray-200 fill-current'" 
                         viewBox="0 0 20 20"
                         @click="setRating(i)">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Напишіть відгук</label>
                <textarea v-model="message" rows="4" 
                    placeholder="Нам дуже сподобались загадки..."
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition resize-none"></textarea>
            </div>
            
            <button @click="submitReview" :disabled="isSubmitting"
                    class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg hover:bg-purple-500 disabled:opacity-70 transition">
                {{ isSubmitting ? 'Відправка...' : 'Залишити відгук' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.animate-fade-in-up {
    animation: fadeInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>