<script setup>
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    post: { type: Object, required: true },
    relatedPosts: { type: Array, default: () => [] },
});

const toast = useToast();

const initials = (name) => (name ?? '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('') || '—';

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        toast.add({ severity: 'success', summary: 'Tersalin', detail: 'Tautan berhasil disalin.', life: 2500 });
    } catch {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Tidak bisa menyalin tautan. Salin manual dari address bar.', life: 4000 });
    }
};

const shareWhatsapp = () => {
    const text = encodeURIComponent(`${props.post.title} — ${window.location.href}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
};
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 py-8">
        <article class="bg-neutral-900 rounded-2xl border border-neutral-800 overflow-hidden">
            <div class="relative min-h-[280px] sm:min-h-[420px] bg-neutral-800 flex items-end">
                <img
                    v-if="post.cover"
                    :src="post.cover"
                    :alt="post.title"
                    class="absolute inset-0 w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-black/10" />
                <div class="relative z-10 p-5 sm:p-8 text-white max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/80">
                        <template v-if="post.categories?.length">{{ post.categories.map((category) => category.name).join(', ') }} &middot; </template>
                        Berita &middot; {{ post.read_minutes }} menit
                    </p>
                    <h1 class="text-2xl sm:text-4xl font-bold mt-2 leading-tight">{{ post.title }}</h1>
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-1 lg:grid-cols-[minmax(0,1fr)_17rem] gap-8 p-5 sm:p-8">
                <div class="min-w-0">
                    <p v-if="post.excerpt" class="text-base text-neutral-400 leading-relaxed">{{ post.excerpt }}</p>
                    <div class="rich-text mt-6 text-base text-neutral-200" v-html="post.content" />

                    <div class="mt-8 pt-6 border-t border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-11 h-11 rounded-full bg-[#FFD700] text-black flex items-center justify-center text-sm font-semibold shrink-0">
                                {{ initials(post.author) }}
                            </span>
                            <div>
                                <p class="font-semibold text-white">{{ post.author ?? 'Pemerintah Kabupaten Bintan' }}</p>
                                <p class="text-sm text-neutral-500">{{ post.read_minutes }} menit baca</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button icon="pi pi-link" severity="secondary" outlined rounded aria-label="Salin tautan" @click="copyLink" />
                            <Button icon="pi pi-whatsapp" severity="secondary" outlined rounded aria-label="Bagikan ke WhatsApp" @click="shareWhatsapp" />
                        </div>
                    </div>
                </div>

                <aside class="flex flex-col gap-4 lg:border-l lg:border-neutral-800 lg:pl-6">
                    <div class="flex items-center justify-between pb-4 border-b border-neutral-800 text-sm">
                        <span class="font-semibold text-neutral-200">{{ post.read_minutes }} menit baca</span>
                        <span class="inline-flex items-center gap-1 text-neutral-500">
                            <i class="pi pi-eye" />
                            {{ post.views_count }}
                        </span>
                    </div>
                    <div class="pb-4 border-b border-neutral-800">
                        <h2 class="text-xs font-medium text-neutral-500 uppercase">Diterbitkan</h2>
                        <p class="text-sm text-neutral-200 mt-1">{{ formatDate(post.published_at) }}</p>
                    </div>
                    <div class="pb-4 border-b border-neutral-800">
                        <h2 class="text-xs font-medium text-neutral-500 uppercase">Penulis</h2>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="w-8 h-8 rounded-full bg-[#FFD700] text-black text-xs font-semibold flex items-center justify-center shrink-0">
                                {{ initials(post.author) }}
                            </span>
                            <p class="text-sm text-neutral-200">{{ post.author ?? 'Pemerintah Kabupaten Bintan' }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-link" severity="secondary" outlined rounded aria-label="Salin tautan" @click="copyLink" />
                        <Button icon="pi pi-whatsapp" severity="secondary" outlined rounded aria-label="Bagikan ke WhatsApp" @click="shareWhatsapp" />
                    </div>
                </aside>
            </div>
        </article>

        <section v-if="relatedPosts.length" class="mt-10">
            <h2 class="text-lg font-semibold text-white mb-4">Baca juga</h2>
            <div class="flex flex-col gap-4">
                <Link
                    v-for="item in relatedPosts"
                    :key="item.id"
                    :href="route('berita.show', item.slug)"
                    class="flex gap-4 bg-neutral-900 rounded-xl border border-neutral-800 p-3 hover:border-[#FFD700]/50"
                >
                    <img
                        v-if="item.cover"
                        :src="item.cover"
                        :alt="item.title"
                        class="w-28 h-20 sm:w-40 sm:h-24 rounded-lg object-cover shrink-0"
                    />
                    <div class="min-w-0">
                        <p class="text-xs text-neutral-500 uppercase tracking-wide">
                            {{ formatDate(item.published_at) }} &middot; {{ item.read_minutes }} menit
                        </p>
                        <p class="font-semibold text-white mt-1 leading-snug">{{ item.title }}</p>
                        <p v-if="item.excerpt" class="text-sm text-neutral-500 mt-1 line-clamp-2">{{ item.excerpt }}</p>
                        <p class="text-xs text-neutral-500 mt-2">{{ item.author ?? 'Pemerintah Kabupaten Bintan' }}</p>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
