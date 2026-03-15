<script setup>
import { RouterView } from 'vue-router';
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
onMounted(() => {
  authStore.fetchUser();
});
</script>

<template>
  <RouterView />
</template>
