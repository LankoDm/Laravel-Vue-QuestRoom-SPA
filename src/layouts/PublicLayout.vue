<script setup>
import { RouterView, RouterLink } from 'vue-router';
</script>

<template>
  <div class="min-h-screen flex flex-col bg-background font-sans text-text">

    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-secondary shadow-sm">
      <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">

        <RouterLink :to="{ name: 'home' }" class="text-2xl font-black text-primary tracking-tight">
          Onea<span class="text-text">Room</span>
        </RouterLink>

        <nav class="hidden md:flex space-x-8 font-semibold text-gray-500">
          <RouterLink :to="{ name: 'home' }" class="hover:text-primary transition-colors">Каталог</RouterLink>
          <a href="#" class="hover:text-primary transition-colors">Про нас</a>
          <a href="#" class="hover:text-primary transition-colors">Контакти</a>
        </nav>

        <div>
          <button class="bg-secondary text-primary hover:bg-primary hover:text-white px-6 py-2.5 rounded-xl font-bold transition-all duration-300">
            Увійти
          </button>
        </div>

      </div>
    </header>

    <main class="flex-grow">
      <RouterView />
    </main>

    <footer class="bg-white border-t border-secondary py-8 mt-12 text-center text-gray-400 text-sm">
      <p>© 2026 QuestRoom. Дипломний проєкт.</p>
    </footer>

  </div>
</template>