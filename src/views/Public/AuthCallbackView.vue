<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
  const token = route.query.token;
  if (token) {
    localStorage.setItem('token', token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    authStore.token = token;
    authStore.isAuthenticated = true;
    try {
      await authStore.fetchUser();
      router.push({ name: 'home' });
    } catch (error) {
      console.error('Помилка завантаження профілю Google', error);
      router.push({ name: 'login' });
    }
  } else {
    router.push({ name: 'login' });
  }
});
</script>

<template>
  <div class="min-h-[60vh] flex flex-col items-center justify-center">
    <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4"></div>
    <h2 class="text-2xl font-black text-text">Авторизація через Google</h2>
    <p class="text-gray-500 mt-2">Зачекайте мить, ми налаштовуємо ваш профіль</p>
  </div>
</template>