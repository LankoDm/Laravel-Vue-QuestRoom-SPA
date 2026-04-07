<script setup>
import { RouterView } from 'vue-router';
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import ToastList from '@/components/ToastList.vue'

const authStore = useAuthStore();
onMounted(() => {
  authStore.fetchUser();
});
</script>

<template>
  <RouterView />
  <ToastList />
</template>
