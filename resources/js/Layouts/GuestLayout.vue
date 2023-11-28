<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center bg-bottom md:bg-left md:pt-0 bg-slate-100 bg-contain bg-no-repeat" style="background-image: url('/images/Image8.png');">
        <div>
            <Link href="/">
                <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="w-4/5 sm:max-w-md mt-3 mx-2 px-6 py-4 bg-white shadow-lg overflow-hidden rounded-lg border border-gray-300 opacity-90"
        >
            <slot />
        </div>
    </div>
</template>
