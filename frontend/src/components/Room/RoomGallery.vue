<script setup>
import {ref, computed} from 'vue';

const props = defineProps({
    /**
     * The raw image path from the database (can be string, JSON string, or array).
     */
    imagePath: {
        type: [String, Array],
        required: true
    },
    /**
     * The name of the room for alt tags.
     */
    roomName: {
        type: String,
        default: 'Quest Room'
    }
});

const activeImageIndex = ref(0);

/**
 * Parses the imagePath prop into an array of URLs safely.
 */
const parsedImages = computed(() => {
    if (!props.imagePath) return [];
    if (Array.isArray(props.imagePath)) return props.imagePath;

    if (typeof props.imagePath === 'string' && props.imagePath.startsWith('[')) {
        try {
            return JSON.parse(props.imagePath);
        } catch (e) {
            return [props.imagePath];
        }
    }
    return [props.imagePath];
});
</script>

<template>
    <div class="w-full flex flex-col gap-4">
        <div class="w-full h-[400px] md:h-[500px] rounded-3xl overflow-hidden shadow-sm relative bg-secondary/20">
            <template v-if="parsedImages.length > 0">
                <Transition name="fade" mode="out-in">
                    <img :key="activeImageIndex" :src="parsedImages[activeImageIndex]" :alt="roomName"
                         class="w-full h-full object-cover"/>
                </Transition>
            </template>
            <template v-else>
                <div class="w-full h-full flex items-center justify-center text-gray-400 font-bold">
                    Немає зображень
                </div>
            </template>
        </div>

        <div v-if="parsedImages.length > 1" class="flex flex-wrap gap-3">
            <button v-for="(img, idx) in parsedImages" :key="idx"
                    @click="activeImageIndex = idx"
                    class="shrink-0 w-24 h-24 rounded-xl overflow-hidden border-2 transition-all duration-300 cursor-pointer shadow-sm"
                    :class="activeImageIndex === idx ? 'border-primary opacity-100 ring-2 ring-primary/30' : 'border-transparent opacity-60 hover:opacity-100 hover:border-secondary'">
                <img :src="img" class="w-full h-full object-cover"/>
            </button>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
