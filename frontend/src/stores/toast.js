import {defineStore} from 'pinia';
import {ref} from 'vue';

export const useToastStore = defineStore('toast', () => {
    const toasts = ref([]);
    const isSoundEnabled = ref(localStorage.getItem('toastSound') !== 'false');
    const toggleSound = () => {
        isSoundEnabled.value = !isSoundEnabled.value;
        localStorage.setItem('toastSound', isSoundEnabled.value);
    };
    const playPopSound = () => {
        if (!isSoundEnabled.value) return;
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(1760, audioCtx.currentTime + 0.1);
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.3);
        } catch (e) {
            console.warn('Аудіо не підтримується', e);
        }
    };
    const add = (toast) => {
        const id = Date.now() + Math.random();
        toasts.value.push({...toast, id});
        playPopSound();
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