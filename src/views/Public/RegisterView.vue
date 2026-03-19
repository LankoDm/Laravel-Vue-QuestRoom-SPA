<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});
const errorMessage = ref('');
const isLoading = ref(false);
const loginWithGoogle = async () => {
  try {
    const response = await axios.get('http://localhost:8080/api/auth/google');
    window.location.href = response.data.url; // перенаправлення людини на сторінку гугл
  } catch (error) {
    console.error('Помилка ініціалізації Google логіну', error);
  }
};
const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Паролі не співпадають!';
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  try {
    await authStore.register({
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation
    });
    await router.push({name: 'home'});
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      errorMessage.value = Object.values(errors)[0][0];
    } else {
      errorMessage.value = 'Сталася помилка при реєстрації.';
    }
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="min-h-[80vh] flex items-center justify-center px-6 py-12">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-lg border border-secondary p-8">

      <div class="text-center mb-8">
        <h1 class="text-3xl font-black text-text mb-2">Створити акаунт</h1>
        <p class="text-gray-500">Приєднуйтесь до нас та бронюйте квести коли зручно</p>
      </div>
      <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center border border-red-100">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleRegister" class="space-y-5">

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Ім'я</label>
          <input v-model="form.name" type="text" required
                 class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-gray-50"
                 placeholder="Ваше ім'я"
          >
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
          <input v-model="form.email" type="email" required
                 class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-gray-50"
                 placeholder="your@email.com">
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Пароль</label>
          <input v-model="form.password" type="password" required minlength="8"
                 class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-gray-50"
                 placeholder="Мінімум 8 символів">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Підтвердіть пароль</label>
          <input v-model="form.password_confirmation" type="password" required
                 class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-gray-50"
                 placeholder="Повторіть пароль">
        </div>
        <button type="submit" :disabled="isLoading"
                class="w-full bg-primary hover:bg-purple-500 text-white font-bold py-4 rounded-xl transition-all shadow-md disabled:opacity-70 mt-4 flex justify-center items-center">
          <span v-if="isLoading">Створення...</span>
          <span v-else>Зареєструватися</span>
        </button>
      </form>
      <div class="mt-6 flex items-center justify-center space-x-2">
        <span class="h-px bg-gray-200 w-full"></span>
        <span class="text-gray-400 font-medium text-sm">АБО</span>
        <span class="h-px bg-gray-200 w-full"></span>
      </div>
      <button
          @click="loginWithGoogle" type="button"
          class="mt-6 w-full cursor-pointer bg-white border border-gray-300 text-gray-700 font-bold py-3.5 rounded-xl hover:bg-gray-50 transition-all shadow-sm flex justify-center items-center gap-3">
        <svg class="w-5 h-5" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Увійти через Google
      </button>

      <div class="mt-8 text-center text-sm text-gray-500">
        Вже маєте акаунт?
        <RouterLink :to="{ name: 'login' }" class="text-primary font-bold hover:underline">Увійти</RouterLink>
      </div>
    </div>
  </div>
</template>