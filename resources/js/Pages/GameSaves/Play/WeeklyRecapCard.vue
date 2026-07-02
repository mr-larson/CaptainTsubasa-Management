<script setup>
defineProps({
    weeklyRecap: { type: Array, required: true },
});
</script>

<template>
    <div class="border border-amber-200 rounded-xl bg-amber-50 p-3">
        <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1.5">Cette semaine</div>
        <ul class="space-y-1">
            <li v-for="(entry, idx) in weeklyRecap" :key="idx" class="flex items-start gap-2 text-xs text-amber-800">
                <span class="shrink-0">{{ entry.type === 'injury' ? '🤕' : '🟥' }}</span>
                <span>{{ entry.message }}</span>
            </li>
        </ul>
    </div>
</template>
