<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    page: { type: Object, required: true },
    slides: { type: Array, default: () => [] },
});
</script>

<template>
    <article class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ page.title }}</h1>
        <p v-if="page.excerpt" class="mt-4 text-lg text-white/80">{{ page.excerpt }}</p>
        <div class="rich-text mt-8 text-base bg-white rounded-2xl border border-surface-200 p-6" v-html="page.content" />
    </article>
</template>
