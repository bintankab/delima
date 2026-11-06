<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    page: { type: Object, required: true },
    slides: { type: Array, default: () => [] },
});
</script>

<template>
    <article class="max-w-6xl mx-auto px-4 py-10 flex flex-col gap-8">
        <header>
            <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ page.title }}</h1>
            <p v-if="page.excerpt" class="mt-3 text-lg text-white/80">{{ page.excerpt }}</p>
        </header>

        <div v-if="slides.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <figure v-for="(slide, index) in slides" :key="index" class="overflow-hidden rounded-2xl border border-surface-200 bg-white">
                <img :src="slide.url" :alt="slide.caption || page.title" class="h-52 w-full object-cover" />
                <figcaption v-if="slide.caption" class="px-4 py-3 text-sm text-surface-600">{{ slide.caption }}</figcaption>
            </figure>
        </div>

        <div v-if="page.fields?.cards?.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <section v-for="(card, index) in page.fields.cards" :key="index" class="rounded-2xl border border-surface-200 bg-white p-5">
                <img v-if="card.url" :src="card.thumb || card.url" :alt="card.title" class="mb-4 h-40 w-full rounded-xl object-cover" />
                <h2 class="text-xl font-semibold text-surface-900">{{ card.title }}</h2>
                <p v-if="card.body" class="mt-2 text-surface-600">{{ card.body }}</p>
            </section>
        </div>

        <div class="rich-text text-base bg-white rounded-2xl border border-surface-200 p-6" v-html="page.content" />
    </article>
</template>
