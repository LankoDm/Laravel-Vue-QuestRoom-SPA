<script setup>
import {ref, onMounted} from 'vue';
import axios from 'axios';
import {useToastStore} from '@/stores/toast';
import {useFormatters} from '@/composables/useFormatters';
import {useReviewsManager} from '@/composables/useReviewsManager';
import PaginationControls from "@/components/UI/PaginationControls.vue";

const toast = useToastStore();
const isLoading = ref(true);

// Import shared logic
const {formatFullDate} = useFormatters();

/**
 * Fetch all reviews for management.
 */
const fetchReviews = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/reviews', { params: buildQueryParams() });
        reviews.value = response.data.data;
        setPaginationData(response.data.meta || response.data);
    } catch (error) {
        console.error('Помилка завантаження відгуків:', error);
        toast.error('Не вдалося завантажити відгуки.');
    } finally {
        isLoading.value = false;
    }
};

const {
    filterStatus, currentPage, itemsPerPage, totalPages, totalItems,
    reviews, buildQueryParams, setPaginationData
} = useReviewsManager(fetchReviews);

/**
 * Approve a pending review.
 */
const approveReview = async (id) => {
    try {
        await axios.patch(`/reviews/${id}/approve`);
        const review = reviews.value.find(r => r.id === id);
        if (review) {
            review.is_approved = 1; // Update locally
            toast.success('Відгук успішно опубліковано');
        }
    } catch (error) {
        toast.error('Не вдалося схвалити відгук.');
    }
};

/**
 * Delete a review permanently.
 */
const deleteReview = async (id) => {
    if (!confirm('Ви впевнені, що хочете видалити цей відгук назавжди?')) return;

    try {
        await axios.delete(`/reviews/${id}`);
        reviews.value = reviews.value.filter(r => r.id !== id);
        toast.success('Відгук видалено');
    } catch (error) {
        toast.error('Не вдалося видалити відгук.');
    }
};

onMounted(() => fetchReviews());
</script>

<template>
    <div>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <h1 class="text-3xl font-black text-text">Модерація відгуків</h1>
            <div class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-4 w-full md:w-auto">
                <div
                    class="flex flex-wrap items-center gap-4 bg-white px-4 py-2 rounded-xl border border-secondary shadow-sm w-full sm:w-auto">
                    <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
                        <input type="radio" value="all" v-model="filterStatus"
                               class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                        <span class="text-sm font-bold text-text">Всі</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
                        <input type="radio" value="new" v-model="filterStatus"
                               class="w-4 h-4 text-yellow-500 focus:ring-yellow-500 border-gray-300">
                        <span class="text-sm font-bold text-yellow-600">Нові</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
                        <input type="radio" value="published" v-model="filterStatus"
                               class="w-4 h-4 text-green-500 focus:ring-green-500 border-gray-300">
                        <span class="text-sm font-bold text-green-600">Опубліковані</span>
                    </label>
                </div>

                <button @click="fetchReviews"
                        class="w-full sm:w-auto flex justify-center p-3 bg-white text-primary shadow-sm border border-secondary hover:bg-secondary rounded-xl transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="py-20 text-center animate-pulse text-gray-400 font-bold">
            Завантаження відгуків...
        </div>

        <div v-else-if="totalItems === 0"
             class="bg-white p-12 rounded-3xl text-center text-gray-500 shadow-sm border border-secondary">
            За вашими критеріями немає жодного відгуку.
        </div>

        <div v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

                <div v-for="review in reviews" :key="review.id"
                     class="bg-white p-6 rounded-3xl shadow-sm border transition-colors flex flex-col"
                     :class="review.is_approved ? 'border-secondary' : 'border-yellow-300 bg-yellow-50/30'">

                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="font-bold text-text text-lg flex items-center gap-2">
                                {{ review.guest_name || review.user?.name || 'Невідомий користувач' }}
                                <span v-if="review.guest_name" class="bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider">Гість</span>
                            </div>
                            <div class="text-xs text-primary font-bold mt-1">Кімната: {{
                                    review.room?.name || 'ID ' + review.room_id
                                }}
                            </div>
                        </div>
                        <span v-if="!review.is_approved"
                              class="bg-yellow-100 text-yellow-700 text-xs font-black px-2 py-1 rounded-lg">Новий</span>
                        <span v-else class="bg-green-100 text-green-700 text-xs font-black px-2 py-1 rounded-lg">Опубліковано</span>
                    </div>

                    <div class="flex gap-1 mb-4 text-yellow-400">
                        <svg v-for="i in 5" :key="i" class="w-5 h-5"
                             :class="i <= review.rating ? 'fill-current' : 'text-gray-200 fill-current'"
                             viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>

                    <p class="text-gray-600 text-sm mb-6 flex-1 italic">"{{ review.message }}"</p>

                    <div class="flex items-center justify-between pt-4 border-t border-secondary mt-auto">
                        <span class="text-xs text-gray-400 font-medium">{{ formatFullDate(review.created_at) }}</span>

                        <div class="flex gap-2">
                            <button v-if="!review.is_approved" @click="approveReview(review.id)"
                                    title="Опублікувати на сайті"
                                    class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>

                            <button @click="deleteReview(review.id)" title="Видалити назавжди"
                                    class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
            <PaginationControls
                v-model:current-page="currentPage"
                :total-pages="totalPages"
                :total-items="totalItems"
                :items-per-page="itemsPerPage"
            />
        </div>
    </div>
</template>
