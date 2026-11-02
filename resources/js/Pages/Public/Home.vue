<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    latestPosts: { type: Array, default: () => [] },
    topInnovations: { type: Array, default: () => [] },
});

const slideIndex = ref(0);
let timer;

const slides = computed(() => props.latestPosts.slice(0, 5));
const stories = computed(() => (
    props.latestPosts.length > 5 ? props.latestPosts.slice(5) : props.latestPosts.slice(1)
));
const current = computed(() => slides.value[slideIndex.value] ?? null);

const initials = (name) => (name ?? '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('') || '—';

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const cardStyle = (item, index) => {
    if (!item.cover) {
        return 'text';
    }

    if (index % 3 === 1) {
        return 'feature';
    }

    if (index % 3 === 2) {
        return 'thumb';
    }

    return 'text';
};

const stop = () => clearInterval(timer);

const start = () => {
    stop();
    if (slides.value.length < 2) {
        return;
    }

    timer = setInterval(() => {
        slideIndex.value = (slideIndex.value + 1) % slides.value.length;
    }, 6000);
};

const openPost = (slug) => router.visit(route('berita.show', slug));

const go = (step) => {
    const total = slides.value.length;
    if (total < 2) {
        return;
    }

    slideIndex.value = (slideIndex.value + step + total) % total;
    start();
};

onMounted(start);
onUnmounted(stop);
</script>

<template>
    <div>
        <section v-if="latestPosts.length" class="max-w-6xl mx-auto px-4 py-8">
            <div class="bg-white rounded-2xl border border-surface-200 p-4 sm:p-6">
                <div v-if="current" class="relative rounded-2xl overflow-hidden min-h-[320px] sm:min-h-[460px] bg-surface-800">
                    <Transition name="post-fade">
                        <div :key="current.id" class="absolute inset-0 cursor-pointer" @click="openPost(current.slug)">
                            <img
                                v-if="current.cover"
                                :src="current.cover"
                                :alt="current.title"
                                class="absolute inset-0 w-full h-full object-cover"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-black/10" />
                            <div class="relative z-10 h-full min-h-[320px] sm:min-h-[460px] flex flex-col justify-end p-5 sm:p-8 text-white">
                                <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Berita Terbaru</p>
                                <h1 class="text-2xl sm:text-4xl font-bold max-w-2xl mt-2 leading-tight">{{ current.title }}</h1>
                                <div class="flex items-center gap-3 mt-4">
                                    <span class="w-9 h-9 rounded-full bg-white/20 border border-white/30 flex items-center justify-center text-xs font-semibold">
                                        {{ initials(current.author) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-medium">{{ current.author ?? 'Pemerintah Kabupaten Bintan' }}</p>
                                        <p v-if="current.published_at" class="text-xs text-white/70">{{ formatDate(current.published_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <div v-if="slides.length > 1" class="absolute bottom-4 right-4 z-20 flex gap-2">
                        <button
                            type="button"
                            class="w-9 h-9 rounded-full bg-white/90 text-surface-800 hover:bg-white flex items-center justify-center"
                            aria-label="Berita sebelumnya"
                            @click="go(-1)"
                        >
                            <i class="pi pi-chevron-left text-xs" />
                        </button>
                        <button
                            type="button"
                            class="w-9 h-9 rounded-full bg-white/90 text-surface-800 hover:bg-white flex items-center justify-center"
                            aria-label="Berita berikutnya"
                            @click="go(1)"
                        >
                            <i class="pi pi-chevron-right text-xs" />
                        </button>
                    </div>
                </div>

                <div v-if="stories.length" class="columns-1 md:columns-2 lg:columns-3 gap-8 mt-8">
                    <article
                        v-for="(item, index) in stories"
                        :key="item.id"
                        class="break-inside-avoid mb-8 border-b border-dashed border-surface-200 pb-6 cursor-pointer"
                        @click="openPost(item.slug)"
                    >
                        <p class="text-[11px] font-medium tracking-wide uppercase text-surface-400">
                            {{ formatDate(item.published_at) }}
                        </p>

                        <div v-if="cardStyle(item, index) === 'thumb'" class="flex gap-3 mt-2">
                            <img :src="item.cover" :alt="item.title" class="w-24 h-20 rounded-lg object-cover shrink-0" />
                            <div class="min-w-0">
                                <h2 class="font-semibold text-surface-900 leading-snug">{{ item.title }}</h2>
                                <p v-if="item.excerpt" class="text-sm text-surface-500 mt-1 line-clamp-3">{{ item.excerpt }}</p>
                            </div>
                        </div>

                        <template v-else>
                            <img
                                v-if="cardStyle(item, index) === 'feature'"
                                :src="item.cover"
                                :alt="item.title"
                                class="w-full h-44 rounded-xl object-cover mt-3"
                            />
                            <h2 class="font-semibold text-surface-900 leading-snug mt-2">{{ item.title }}</h2>
                            <p v-if="item.excerpt" class="text-sm text-surface-500 mt-2 line-clamp-4">{{ item.excerpt }}</p>
                        </template>

                        <p class="text-sm font-medium text-indigo-600 mt-3 inline-flex items-center gap-1">
                            Baca selengkapnya
                            <i class="pi pi-arrow-right text-xs" />
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section v-else class="max-w-6xl mx-auto px-4 py-16">
            <p class="text-surface-400 text-sm text-center">Belum ada berita.</p>
        </section>

        <section class="max-w-6xl mx-auto px-4 py-12">
            <h2 class="text-lg font-semibold text-surface-900 mb-4">Top Inovasi</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Link
                    v-for="item in topInnovations"
                    :key="item.id"
                    :href="`/etalase/${item.slug}`"
                    class="bg-white rounded-xl border border-surface-200 p-4 hover:border-indigo-300"
                >
                    <h3 class="font-medium text-surface-900">{{ item.title }}</h3>
                    <p class="text-sm text-surface-500 mt-1">Tahun {{ item.tahun }}</p>
                </Link>
                <p v-if="!topInnovations.length" class="text-surface-400 text-sm">Belum ada inovasi unggulan.</p>
            </div>
        </section>
    </div>
</template>

<style scoped>
.post-fade-enter-active,
.post-fade-leave-active {
    transition: opacity 0.45s ease;
}

.post-fade-enter-from,
.post-fade-leave-to {
    opacity: 0;
}
</style>
