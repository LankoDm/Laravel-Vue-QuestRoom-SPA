<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

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

      <div class="mt-8 text-center text-sm text-gray-500">
        Вже маєте акаунт?
        <RouterLink :to="{ name: 'login' }" class="text-primary font-bold hover:underline">Увійти</RouterLink>
      </div>
    </div>
  </div>
</template>