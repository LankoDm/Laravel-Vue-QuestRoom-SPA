<script setup>
import {ref, onUnmounted} from 'vue';

const props = defineProps({
    /**
     * The secret phrase to reveal upon successful hack.
     */
    hintPhrase: {
        type: String,
        required: true
    }
});

// Game State
const gameStage = ref(0);
const currentLevel = ref(1);
const markerPos = ref(0);
const targetStart = ref(40);
const targetWidth = ref(20);
const failedHack = ref(false);
const successHit = ref(false);
const displayedHint = ref('************************');

// Animation variables
let speed = 0.4;
let dir = 1;
let reqId = null;
let lastTimestamp = 0;

/**
 * Initializes game level parameters.
 */
const initLevel = (lvl) => {
    targetWidth.value = 32 - (lvl * 6);
    targetStart.value = 10 + Math.random() * (80 - targetWidth.value);
    speed = 0.3 + (lvl * 0.3);
};

/**
 * Main game loop for the moving marker.
 */
const gameLoop = (timestamp) => {
    if (gameStage.value !== 1) return;
    if (!lastTimestamp) lastTimestamp = timestamp;

    const deltaTime = timestamp - lastTimestamp;
    lastTimestamp = timestamp;
    markerPos.value += (speed * deltaTime * 0.1) * dir;

    if (markerPos.value >= 100) {
        markerPos.value = 100;
        dir = -1;
    }
    if (markerPos.value <= 0) {
        markerPos.value = 0;
        dir = 1;
    }

    reqId = requestAnimationFrame(gameLoop);
};

/**
 * Starts the mini-game.
 */
const startMiniGame = () => {
    gameStage.value = 1;
    currentLevel.value = 1;
    initLevel(1);
    lastTimestamp = 0;
    cancelAnimationFrame(reqId);
    reqId = requestAnimationFrame(gameLoop);
};

/**
 * Validates the player's attempt to stop the marker in the green zone.
 */
const tryHack = () => {
    const tolerance = 2.5;
    const isHit = markerPos.value >= (targetStart.value - tolerance) &&
        markerPos.value <= (targetStart.value + targetWidth.value + tolerance);

    if (isHit) {
        successHit.value = true;
        setTimeout(() => {
            successHit.value = false;
        }, 200);

        if (currentLevel.value < 3) {
            currentLevel.value++;
            initLevel(currentLevel.value);
        } else {
            gameStage.value = 2;
            cancelAnimationFrame(reqId);
            triggerDecoder();
        }
    } else {
        failedHack.value = true;
        setTimeout(() => {
            failedHack.value = false;
        }, 300);
        currentLevel.value = 1;
        initLevel(1);
    }
};

/**
 * Animates the decoding of the secret phrase.
 */
const triggerDecoder = () => {
    const targetText = props.hintPhrase || 'Таємниця відсутня';
    const characters = 'АБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯ0123456789@#$%&*!?><';
    let iterations = 0;

    const interval = setInterval(() => {
        displayedHint.value = targetText.split('').map((char, index) => {
            if (index < iterations) return char;
            if (char === ' ') return ' ';
            return characters[Math.floor(Math.random() * characters.length)];
        }).join('');

        if (iterations >= targetText.length) {
            clearInterval(interval);
            gameStage.value = 3;
            displayedHint.value = targetText;
        }
        iterations += 1 / 3;
    }, 40);
};

// Cleanup animation frame on component unmount
onUnmounted(() => {
    if (reqId) cancelAnimationFrame(reqId);
});
</script>

<template>
    <div
        class="bg-gray-50 p-8 rounded-3xl mt-12 shadow-sm border transition-colors duration-300 relative overflow-hidden"
        :class="failedHack ? 'bg-red-50 border-red-200' : 'border-secondary'">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center mb-8 relative z-10">
            <h2 class="text-2xl font-black text-text mb-2 flex items-center justify-center gap-2">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Секретний смарт-замок
            </h2>
            <p class="text-gray-500 font-medium text-sm md:text-base">
                Зупиніть сканер у зеленій зоні, щоб розблокувати приховану підказку.
            </p>
        </div>

        <div class="flex flex-col items-center gap-6 relative z-10 w-full max-w-xl mx-auto">
            <div v-if="gameStage === 0" class="py-6">
                <button @click="startMiniGame"
                        class="bg-primary cursor-pointer hover:bg-purple-600 text-white font-black uppercase tracking-widest py-4 px-12 rounded-xl transition-all shadow-md hover:shadow-lg hover:-translate-y-1">
                    Розблокувати
                </button>
            </div>

            <div v-else-if="gameStage === 1" class="w-full space-y-8 animate-fade-in">
                <div class="flex justify-between items-center px-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Рівень захисту:</span>
                    <div class="flex gap-2">
                        <div v-for="i in 3" :key="i" class="w-8 h-2 rounded-full transition-colors duration-300"
                             :class="i < currentLevel ? 'bg-green-400 shadow-sm' : i === currentLevel ? 'bg-primary animate-pulse' : 'bg-gray-200'">
                        </div>
                    </div>
                </div>
                <div
                    class="relative w-full h-12 bg-white rounded-xl overflow-hidden border-2 transition-colors duration-200 shadow-inner"
                    :class="failedHack ? 'border-red-300' : successHit ? 'border-green-400' : 'border-secondary'">
                    <div class="absolute h-full bg-green-100 border-x-2 border-green-400 transition-all duration-300"
                         :style="{ left: `${targetStart}%`, width: `${targetWidth}%` }"></div>
                    <div
                        class="absolute h-full w-2 bg-primary shadow-[0_0_8px_rgba(168,85,247,0.6)] z-10 -ml-1 transition-opacity"
                        :class="{'opacity-0': successHit}"
                        :style="{ left: `${markerPos}%` }"></div>
                    <div v-if="successHit" class="absolute inset-0 bg-green-50 animate-pulse"></div>
                </div>
                <button @click="tryHack"
                        class="w-full cursor-pointer bg-primary hover:bg-purple-600 text-white font-black text-xl tracking-widest py-5 rounded-xl transition-all active:scale-95 shadow-md">
                    ЗУПИНИТИ
                </button>
            </div>

            <div v-else
                 class="w-full bg-white p-8 rounded-3xl border border-secondary min-h-[140px] flex flex-col items-center justify-center relative overflow-hidden shadow-sm animate-fade-in">
                <div
                    class="absolute inset-0 bg-gradient-to-b from-transparent via-primary/5 to-transparent opacity-50 animate-scan pointer-events-none"></div>
                <p class="font-mono text-xl md:text-2xl break-words text-center leading-relaxed tracking-wider transition-colors duration-300 relative z-10"
                   :class="gameStage === 3 ? 'text-primary font-black drop-shadow-sm' : 'text-gray-400 font-medium'">
                    {{ displayedHint }}
                </p>
                <div v-if="gameStage === 3"
                     class="mt-4 bg-green-50 text-green-600 text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg animate-fade-in flex items-center gap-2 border border-green-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Підказку розблоковано
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}

@keyframes scan {
    0% {
        transform: translateY(-100%);
    }
    100% {
        transform: translateY(200%);
    }
}

.animate-scan {
    animation: scan 3s linear infinite;
}
</style>
