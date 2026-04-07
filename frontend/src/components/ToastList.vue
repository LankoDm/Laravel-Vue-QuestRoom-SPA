<script setup>
import {useToastStore} from '@/stores/toast';

const toastStore = useToastStore();
</script>
<template>
  <div class="fixed top-6 left-6 z-[9999] flex flex-col gap-3 pointer-events-none">
    <TransitionGroup name="toast">
      <div v-for="toast in toastStore.toasts" :key="toast.id"
           class="pointer-events-auto flex items-center gap-4 min-w-[300px] max-w-sm p-4 rounded-2xl shadow-2xl border bg-white backdrop-blur-sm bg-white/95"
           :class="{'border-green-200': toast.type === 'success', 'border-red-200': toast.type === 'error', 'border-blue-200': toast.type === 'info', 'border-yellow-200': toast.type === 'warning'}">
        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-inner"
             :class="{
               'bg-green-50 text-green-500': toast.type === 'success', 'bg-red-50 text-red-500': toast.type === 'error', 'bg-blue-50 text-blue-500': toast.type === 'info', 'bg-yellow-50 text-yellow-500': toast.type === 'warning' }">
          <svg v-if="toast.type === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          <svg v-if="toast.type === 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          <svg v-if="toast.type === 'info'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <svg v-if="toast.type === 'warning'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
        </div>
        <div class="flex-1 pt-0.5">
          <p class="text-sm font-bold text-gray-800 leading-snug">{{ toast.message }}</p>
        </div>
        <button @click="toastStore.remove(toast.id)"
                class="text-gray-400 hover:text-gray-800 transition-colors p-1 rounded-lg hover:bg-gray-100 self-start">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(50px) scale(0.9);
}

.toast-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>