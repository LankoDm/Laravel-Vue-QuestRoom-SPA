<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <div class="py-20 px-6 max-w-3xl mx-auto text-center min-h-[60vh] flex flex-col justify-center items-center animate-fade-in">
    <div class="w-32 h-32 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-8 shadow-lg shadow-green-100/50">
      <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
      </svg>
    </div>

    <h1 class="text-5xl font-black text-text mb-4">Оплата успішна!</h1>
    <p class="text-xl text-gray-500 font-medium mb-10">
      Дякуємо за бронювання. Ми вже отримали ваш платіж і з нетерпінням чекаємо на вашу команду!
    </p>

    <RouterLink :to="{ name: 'home' }" class="bg-primary hover:bg-purple-500 text-white font-black text-lg py-4 px-10 rounded-xl shadow-lg shadow-primary/30 transition-all transform hover:-translate-y-1">
      Повернутися до каталогу
    </RouterLink>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
</style>