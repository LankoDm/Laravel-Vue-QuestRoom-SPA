<script setup>
import { RouterView, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
</script>

<template>
  <div class="min-h-screen flex flex-col bg-background font-sans text-text">

    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-secondary shadow-sm">
      <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">

        <RouterLink :to="{ name: 'home' }" class="text-2xl font-black text-primary tracking-tight">
          Onea<span class="text-text">Room</span>
        </RouterLink>
<!-- зміна для вкладок про нас та контакти -->
        <nav class="hidden md:flex space-x-8 font-semibold text-gray-500">
          <RouterLink
              :to="{ name: 'home' }"
              exact-active-class="text-primary"
              class="hover:text-primary transition-colors cursor-pointer">
            Каталог
          </RouterLink>
          <RouterLink
              :to="{ name: 'about' }"
              exact-active-class="text-primary"
              class="hover:text-primary transition-colors cursor-pointer">
            Про нас
          </RouterLink>
          <RouterLink
              :to="{ name: 'contacts' }"
              exact-active-class="text-primary"
              class="hover:text-primary transition-colors cursor-pointer">
            Контакти
          </RouterLink>
        </nav>

        <div class="flex items-center gap-4">

          <template v-if="!authStore.isAuthenticated">
            <RouterLink :to="{ name: 'login' }" class="bg-secondary text-primary hover:bg-primary hover:text-white px-6 py-2.5 rounded-xl font-bold transition-all duration-300">
              Увійти
            </RouterLink>
          </template>

          <template v-else>
            <RouterLink v-if="authStore.isAdmin" to="/admin" class="hidden md:flex bg-red-100 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2.5 rounded-xl font-bold transition-all duration-300">
              Адмін-панель
            </RouterLink>

            <RouterLink v-if="authStore.isManager" to="/manager" class="hidden md:flex bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white px-4 py-2.5 rounded-xl font-bold transition-all duration-300">
              Панель менеджера
            </RouterLink>

            <RouterLink
                :to="{ name: 'profile' }"
                class="font-bold text-gray-600 hover:text-primary transition-colors">
              Профіль
            </RouterLink>
            <button @click="authStore.logout" class="text-gray-400 hover:text-red-500 transition-colors cursor-pointer" title="Вийти">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </button>
          </template>
        </div>

      </div>
    </header>

    <main class="flex-grow">
      <RouterView />
    </main>
<!--додати посилання на соцмережі та політика конфіденційності і договір публічної оферти-->
    <footer class="bg-white border-t border-secondary py-8 mt-12 text-center text-gray-400 text-sm">
      <p>© 2026 OneaRoom. Дипломний проєкт.</p>
    </footer>

  </div>
</template>