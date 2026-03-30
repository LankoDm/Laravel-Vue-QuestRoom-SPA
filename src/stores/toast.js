import {defineStore} from 'pinia';
import {ref} from 'vue';

export const useToastStore = defineStore('toast', () => {
    const toasts = ref([]);
    const add = (toast) => {
        const id = Date.now() + Math.random();
        toasts.value.push({...toast, id});
        setTimeout(() => remove(id), toast.timeout || 3500);
    };
    const remove = (id) => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    };
    const success = (message, timeout) => add({message, type: 'success', timeout});
    const error = (message, timeout = 5000) => add({message, type: 'error', timeout});
    const info = (message, timeout) => add({message, type: 'info', timeout});
    const warning = (message, timeout) => add({message, type: 'warning', timeout});
    return {toasts, remove, success, error, info, warning};
});