<script setup>
import { ref, onMounted } from 'vue';
import { RouterView, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const footerRooms = ref([]);
const isMobileMenuOpen = ref(false);
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false;
};
onMounted(async () => {
  try {
    const response = await axios.get('/rooms');
    let data = response.data.data || response.data;
    footerRooms.value = data.filter(room => room.is_active);
  } catch (error) {
    console.error('Помилка завантаження квестів для футера:', error);
  }
});
</script>

<template>
  <div class="min-h-screen flex flex-col bg-background font-sans text-text">

    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-secondary shadow-sm">
      <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">

        <RouterLink :to="{ name: 'home' }" class="text-2xl font-black text-primary tracking-tight">
          Onea<span class="text-text">Room</span>
        </RouterLink>
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
          <button @click="toggleMobileMenu" class="md:hidden p-2 text-gray-500 hover:text-primary focus:outline-none ml-2">
            <svg v-if="!isMobileMenuOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

      </div>
      <div v-show="isMobileMenuOpen" class="md:hidden absolute top-20 left-0 w-full bg-white border-b border-secondary shadow-2xl z-40 origin-top animate-dropdown">
        <nav class="flex flex-col px-6 py-6 space-y-4">
          <RouterLink @click="closeMobileMenu" :to="{ name: 'home' }" exact-active-class="text-primary" class="text-xl font-bold text-gray-600 hover:text-primary">
            Каталог
          </RouterLink>
          <RouterLink @click="closeMobileMenu" :to="{ name: 'about' }" exact-active-class="text-primary" class="text-xl font-bold text-gray-600 hover:text-primary">
            Про нас
          </RouterLink>
          <RouterLink @click="closeMobileMenu" :to="{ name: 'contacts' }" exact-active-class="text-primary" class="text-xl font-bold text-gray-600 hover:text-primary">
            Контакти
          </RouterLink>
          <div v-if="authStore.isAdmin || authStore.isManager" class="border-t border-gray-100 my-2 pt-4 space-y-4">
            <RouterLink v-if="authStore.isAdmin" @click="closeMobileMenu" to="/admin" class="block text-xl font-bold text-red-500">
              Адмін-панель
            </RouterLink>
            <RouterLink v-if="authStore.isManager" @click="closeMobileMenu" to="/manager" class="block text-xl font-bold text-blue-500">
              Панель менеджера
            </RouterLink>
          </div>
        </nav>
      </div>
    </header>

    <main class="flex-grow">
      <RouterView />
    </main>
    <footer class="bg-white border-t border-secondary mt-12 shrink-0">
      <div class="max-w-6xl mx-auto px-6 py-12">
        <div v-if="footerRooms.length > 0" class="mb-12">
          <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Наші квести</h3>
          <div class="flex flex-wrap gap-x-10 gap-y-4">
            <RouterLink
                v-for="room in footerRooms"
                :key="room.id"
                :to="{ name: 'room.show', params: { slug: room.slug } }"
                class="group flex items-center gap-3 text-sm font-bold text-gray-600 hover:text-primary transition-colors max-w-full">
              <span class="w-1.5 h-1.5 rounded-full bg-gray-300 group-hover:bg-primary transition-colors shrink-0"></span>
              <span class="truncate">{{ room.name }}</span>
            </RouterLink>
          </div>
        </div>
        <div class="flex flex-col md:flex-row justify-between items-center gap-6 pt-8 border-t border-secondary">
          <div class="flex flex-col md:flex-row items-center gap-2 md:gap-4 text-center md:text-left">
            <RouterLink :to="{ name: 'home' }" class="text-xl font-black text-primary tracking-tight">Onea<span class="text-text">Room</span></RouterLink>
            <span class="hidden md:inline text-gray-300">|</span>
            <span class="text-sm text-gray-400 font-medium">© 2026 Чернігів.</span>
          </div>
          <div class="flex flex-wrap justify-center gap-6 text-sm font-bold">
            <RouterLink to="/privacy-policy" class="text-gray-500 hover:text-primary transition-colors">Політика конфіденційності</RouterLink>
            <RouterLink to="/public-offer" class="text-gray-500 hover:text-primary transition-colors">Договір публічної оферти</RouterLink>
          </div>
        </div>
      </div>
    </footer>

  </div>
</template>
<style scoped>
@keyframes dropdown {
  0% { opacity: 0; transform: translateY(-10px) scaleY(0.95); }
  100% { opacity: 1; transform: translateY(0) scaleY(1); }
}
.animate-dropdown {
  animation: dropdown 0.2s ease-out forwards;
}
</style>
