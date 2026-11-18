<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
defineProps({ photos: { type: Array, default: () => [] } });
</script>

<template>
    <Head title="Crédits des photos" />
    <PublicLayout>
        <h1 class="mb-4 text-3xl font-bold">Crédits des photos</h1>
        <p class="mb-8 text-slate-300">Les portraits ci-dessous proviennent de Wikimedia Commons. Chaque photographie conserve sa licence propre. Les miniatures sont fournies par Wikimedia et peuvent être cadrées à l'affichage dans le jeu. Les crédits ne constituent pas un soutien des joueurs ou des photographes au projet.</p>
        <ul class="space-y-5">
            <li v-for="photo in photos" :key="photo.slug" :id="photo.slug" class="flex gap-4 rounded-2xl border border-slate-700 bg-slate-800 p-4">
                <img :src="`/storage/${photo.photo_path}`" :alt="photo.player" class="h-24 w-20 shrink-0 rounded-lg bg-white object-cover" loading="lazy" />
                <div class="min-w-0 break-words">
                    <h2 class="font-bold">{{ photo.player }}</h2>
                    <p class="text-sm text-slate-300">{{ photo.author }}</p>
                    <p v-if="photo.credit" class="mt-1 text-xs text-slate-400">{{ photo.credit }}</p>
                    <div class="mt-2 flex flex-wrap gap-x-4 text-sm text-teal-300">
                        <a :href="photo.source_url" target="_blank" rel="noopener noreferrer" class="underline">Source et historique</a>
                        <a :href="photo.license_url" target="_blank" rel="noopener noreferrer" class="underline">{{ photo.license }}</a>
                    </div>
                </div>
            </li>
        </ul>
    </PublicLayout>
</template>
