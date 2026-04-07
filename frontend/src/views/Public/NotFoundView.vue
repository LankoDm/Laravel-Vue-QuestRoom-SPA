<template>
  <div class="min-h-[70vh] flex flex-col items-center justify-center px-6 text-center animate-fade-in">
    <h1 class="text-9xl font-black text-primary mb-2 drop-shadow-sm">404</h1>
    <h2 class="text-3xl font-bold text-text mb-4">Ой! Сторінку не знайдено</h2>
    <p class="text-gray-500 font-medium mb-10 max-w-md mx-auto leading-relaxed">
      Схоже, ви перейшли за неправильним посиланням, або ця сторінка була переміщена чи видалена.
    </p>
    <RouterLink :to="{ name: 'home' }" class="inline-flex items-center gap-2 bg-primary hover:bg-purple-600 text-white font-bold py-4 px-8 rounded-xl transition-all shadow-lg hover:-translate-y-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      Повернутися на головну
    </RouterLink>
  </div>
</template>
<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
</style>