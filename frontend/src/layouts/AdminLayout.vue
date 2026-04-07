<script setup>
import { ref } from 'vue';
import { RouterView, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const isMobileMenuOpen = ref(false);
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
const closeMobileMenu = () => {
  isMobileMenuOpen.value = false;
};
</script>

<template>
  <div class="h-screen w-full flex bg-gray-50 font-sans text-text overflow-hidden">

    <div v-if="isMobileMenuOpen" @click="closeMobileMenu" class="fixed inset-0 bg-black/20 z-30 md:hidden"></div>
    <aside
        :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
        class="w-64 h-full shrink-0 bg-white border-r border-secondary flex flex-col shadow-sm fixed md:relative z-40 transition-transform duration-300 md:translate-x-0">
      <div class="h-20 shrink-0 flex items-center px-6 border-b border-secondary justify-between">
        <RouterLink :to="{ name: 'home' }" class="text-2xl font-black text-primary tracking-tight">
          Onea<span class="text-text">Admin</span>
        </RouterLink>
        <button @click="closeMobileMenu" class="md:hidden text-gray-400 hover:text-primary">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
        <RouterLink @click="closeMobileMenu" to="/admin" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-secondary/50 hover:text-primary transition-colors font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
          Дашборд
        </RouterLink>
        <RouterLink @click="closeMobileMenu" to="/admin/rooms" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-secondary/50 hover:text-primary transition-colors font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
          Кімнати
        </RouterLink>
        <RouterLink @click="closeMobileMenu" to="/admin/bookings" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-secondary/50 hover:text-primary transition-colors font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"></path></svg>
          Бронювання
        </RouterLink>
        <RouterLink @click="closeMobileMenu" to="/admin/users" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-secondary/50 hover:text-primary transition-colors font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          Користувачі
        </RouterLink>
      </nav>

      <div class="p-4 shrink-0 border-t border-secondary">
        <RouterLink to="/" class="w-full flex items-center justify-center gap-2 px-4 py-3 text-gray-600 hover:bg-secondary hover:text-primary rounded-xl font-bold transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          На головну
        </RouterLink>
      </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
      <header class="h-20 bg-white border-b border-secondary flex items-center justify-between px-8 shrink-0">
        <div class="flex items-center gap-3">
          <button @click="toggleMobileMenu" class="md:hidden p-2 text-gray-500 hover:text-primary focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
          </button>
          <h2 class="text-lg md:text-xl font-bold text-text truncate">Панель керування</h2>
        </div>
        <div class="flex items-center gap-2 md:gap-4 shrink-0">
          <span class="hidden sm:inline text-sm font-medium text-gray-500">
            Привіт, <span class="font-bold text-primary">{{ authStore.user?.name || 'Адмін' }}</span>
          </span>
          <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-secondary flex items-center justify-center text-primary font-black shrink-0">
            {{ authStore.user?.name?.charAt(0) || 'A' }}
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto p-8 relative">
        <RouterView />
      </div>
    </main>

  </div>
</template>

<style scoped>
.router-link-exact-active {
  background-color: var(--color-secondary);
  color: var(--color-primary);
}
</style>