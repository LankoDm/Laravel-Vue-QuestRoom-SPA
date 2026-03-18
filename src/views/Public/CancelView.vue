<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <div class="py-20 px-6 max-w-3xl mx-auto text-center min-h-[60vh] flex flex-col justify-center items-center">
    <div class="w-24 h-24 bg-red-50 text-red-400 rounded-full flex items-center justify-center mx-auto mb-6">
      <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </div>

    <h1 class="text-4xl font-black text-text mb-4">Оплату скасовано</h1>
    <p class="text-lg text-gray-500 font-medium mb-10">
      Ви перервали процес оплати. Ваше бронювання збережено, але воно потребує оплати на місці.
    </p>

    <RouterLink :to="{ name: 'home' }" class="bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold py-3 px-8 rounded-xl transition-colors">
      На головну
    </RouterLink>
  </div>
</template>