<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const form = ref({
  email: '',
  password: ''
});
const errorMessage = ref('');
const isLoading = ref(false);

const handleLogin = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    await authStore.login(form.value);
    router.push({ name: 'home' });
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Неправильний email або пароль';
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="min-h-[70vh] flex items-center justify-center px-6">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-lg border border-secondary p-8">

      <div class="text-center mb-8">
        <h1 class="text-3xl font-black text-text mb-2">Вхід в систему</h1>
        <p class="text-gray-500">Увійдіть, щоб бронювати квести</p>
      </div>

      <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center border border-red-100">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
          <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-gray-50"
              placeholder="your@email.com">
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Пароль</label>
          <input
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-gray-50"
              placeholder="••••••••">
        </div>

        <button
            type="submit"
            :disabled="isLoading"
            class="w-full bg-primary hover:bg-purple-500 text-white font-bold py-4 rounded-xl transition-all shadow-md disabled:opacity-70 flex justify-center items-center">
          <span v-if="isLoading">Завантаження...</span>
          <span v-else>Увійти</span>
        </button>
      </form>

      <div class="mt-8 text-center text-sm text-gray-500">
        Ще немає акаунту?
        <a href="#" class="text-primary font-bold hover:underline">Зареєструватися</a>
      </div>

    </div>
  </div>
</template>