<script>
// Le même écusson est souvent affiché plusieurs fois sur une page.
const croppedLogos = new Map();
const MAX_CACHED_LOGOS = 100;
const MAX_LOGO_SIZE = 384;

function cropTransparentMargins(image, source) {
    const scale = Math.min(1, MAX_LOGO_SIZE / Math.max(image.naturalWidth, image.naturalHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
    canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));

    try {
        const context = canvas.getContext('2d', { willReadFrequently: true });
        if (!context) return source;
        const { width, height } = canvas;
        context.drawImage(image, 0, 0, width, height);
        const { data } = context.getImageData(0, 0, width, height);
        let left = width, top = height, right = -1, bottom = -1;

        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                if (data[(y * width + x) * 4 + 3] === 0) continue;
                left = Math.min(left, x);
                top = Math.min(top, y);
                right = Math.max(right, x);
                bottom = Math.max(bottom, y);
            }
        }

        const cropWidth = right - left + 1;
        const cropHeight = bottom - top + 1;
        if (cropWidth <= 0 || cropHeight <= 0 || (cropWidth === width && cropHeight === height)) return source;

        const cropped = document.createElement('canvas');
        cropped.width = cropWidth;
        cropped.height = cropHeight;
        cropped.getContext('2d').drawImage(canvas, left, top, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);
        return cropped.toDataURL();
    } catch {
        // Une image externe peut s'afficher sans autoriser la lecture de ses pixels.
        return source;
    }
}
</script>

<script setup>
import { computed, shallowRef } from 'vue';

const props = defineProps({
    src: { type: String, required: true },
});

const cropped = shallowRef(null);
const displayedSrc = computed(() => cropped.value?.source === props.src
    ? cropped.value.url
    : croppedLogos.get(props.src) ?? props.src);

function onLoad({ currentTarget: image }) {
    const source = props.src;
    if (image.getAttribute('src') !== source || !image.naturalWidth || !image.naturalHeight) return;

    if (!croppedLogos.has(source)) {
        if (croppedLogos.size >= MAX_CACHED_LOGOS) croppedLogos.delete(croppedLogos.keys().next().value);
        croppedLogos.set(source, cropTransparentMargins(image, source));
    }
    cropped.value = { source, url: croppedLogos.get(source) };
}
</script>

<template>
    <img :src="displayedSrc" alt="" class="object-contain" @load="onLoad" />
</template>
