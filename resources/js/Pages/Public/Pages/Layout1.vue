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
        <h1 class="text-3xl sm:text-4xl font-bold text-surface-900">{{ page.title }}</h1>
        <p v-if="page.excerpt" class="mt-4 text-lg text-surface-600">{{ page.excerpt }}</p>
        <div class="rich-text mt-8 text-base" v-html="page.content" />
    </article>
</template>
