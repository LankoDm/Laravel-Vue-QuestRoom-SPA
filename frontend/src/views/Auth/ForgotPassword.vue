<script setup>
import { ref } from 'vue';
import axios from 'axios';

const email = ref('');
const message = ref('');
const errorMessage = ref('');
const isLoading = ref(false);
const submit = async () => {
  isLoading.value = true;
  message.value = '';
  errorMessage.value = '';
  try {
    const response = await axios.post('http://localhost:8080/api/forgot-password', {
      email: email.value
    });
    message.value = response.data.message;
    email.value = '';
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Помилка. Перевірте правильність email.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="max-w-md mx-auto mt-20 p-6 bg-white rounded-3xl shadow-sm border border-gray-100">
    <h2 class="text-2xl font-black text-center mb-6">Відновлення пароля</h2>
    <div v-if="message" class="mb-4 p-4 bg-green-50 text-green-600 rounded-xl text-sm font-medium">
      {{ message }}
    </div>

    <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 text-red-600 rounded-xl text-sm font-medium">
      {{ errorMessage }}
    </div>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ваш Email</label>
        <input
            v-model="email"
            type="email"
            required
            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
            placeholder="email@example.com">
      </div>
      <button
          type="submit"
          :disabled="isLoading"
          class="w-full bg-primary text-white font-bold py-3 px-4 rounded-xl hover:bg-purple-600 transition-colors disabled:opacity-50">
        {{ isLoading ? 'Відправка' : 'Надіслати посилання' }}
      </button>
    </form>
  </div>
</template>