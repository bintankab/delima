<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import DocumentList from '@/Components/DocumentList.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useVideoEmbed } from '@/Composables/useVideoEmbed';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    innovation: { type: Object, required: true },
    relatedInnovations: { type: Array, default: () => [] },
});

const toast = useToast();
const { getEmbed } = useVideoEmbed();

const videoDialogVisible = ref(false);
const videoEmbed = getEmbed(props.innovation.link_video);

const initials = (name) => (name ?? '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('') || '—';

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        toast.add({ severity: 'success', summary: 'Tersalin', detail: 'Tautan berhasil disalin.', life: 2500 });
    } catch {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Tidak bisa menyalin tautan. Salin manual dari address bar.', life: 4000 });
    }
};

const shareWhatsapp = () => {
    const text = encodeURIComponent(`${props.innovation.title} — ${window.location.href}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
};
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 py-8 flex flex-col gap-6">
        <Link :href="route('inovasi')" class="text-sm text-surface-500 hover:text-indigo-600 w-fit">
            &larr; Kembali ke Data Inovasi
        </Link>

        <div class="relative rounded-2xl overflow-hidden min-h-[260px] sm:min-h-[420px] bg-surface-800 flex items-end">
            <img
                v-if="innovation.cover"
                :src="innovation.cover.url"
                :alt="innovation.title"
                class="absolute inset-0 w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
            <Button
                v-if="videoEmbed"
                label="Tonton Video"
                icon="pi pi-play"
                class="absolute top-4 right-4 z-10"
                severity="contrast"
                @click="videoDialogVisible = true"
            />
            <div class="relative z-10 p-5 sm:p-8 text-white">
                <p class="text-xs sm:text-sm font-medium uppercase tracking-wide text-white/80">
                    {{ innovation.category ?? innovation.jenis_inovasi_label ?? 'Inovasi' }} &middot; {{ innovation.read_minutes }} Menit Baca
                </p>
                <h1 class="text-xl sm:text-3xl font-bold mt-2">{{ innovation.title }}</h1>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 flex flex-col gap-6">
                <div class="rich-text text-base" v-html="innovation.description" />

                <div v-if="innovation.latar_belakang">
                    <h2 class="text-lg font-semibold text-surface-900 mb-2">Latar Belakang</h2>
                    <div class="rich-text" v-html="innovation.latar_belakang" />
                </div>

                <div v-if="innovation.manfaat">
                    <h2 class="text-lg font-semibold text-surface-900 mb-2">Manfaat</h2>
                    <div class="rich-text" v-html="innovation.manfaat" />
                </div>

                <div v-if="innovation.documents.length">
                    <h2 class="text-lg font-semibold text-surface-900 mb-2">Dokumen Pendukung</h2>
                    <DocumentList :documents="innovation.documents" />
                </div>

                <div class="border-t border-surface-200 pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold shrink-0">
                            {{ initials(innovation.organisasi) }}
                        </div>
                        <div>
                            <p class="font-semibold text-surface-900">{{ innovation.organisasi ?? 'Pemerintah Kabupaten Bintan' }}</p>
                            <p class="text-sm text-surface-500">{{ innovation.category ?? '—' }} &middot; {{ innovation.read_minutes }} menit baca</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-link" severity="secondary" outlined rounded aria-label="Salin tautan" @click="copyLink" />
                        <Button icon="pi pi-whatsapp" severity="secondary" outlined rounded aria-label="Bagikan ke WhatsApp" @click="shareWhatsapp" />
                    </div>
                </div>
            </div>

            <aside class="flex flex-col gap-4 lg:border-l lg:border-surface-200 lg:pl-6">
                <div class="flex items-center justify-between pb-4 border-b border-surface-200 text-sm">
                    <span class="font-semibold text-surface-800">{{ innovation.read_minutes }} menit baca</span>
                    <span class="inline-flex items-center gap-1 text-surface-500">
                        <i class="pi pi-eye" />
                        {{ innovation.views_count }}
                    </span>
                </div>

                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Diterbitkan</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ innovation.implementation_at ?? '—' }}</p>
                </div>
                <div v-if="innovation.testing_at" class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Tanggal Uji Coba</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ innovation.testing_at }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Kategori</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ innovation.category ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">OPD</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ innovation.organisasi ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Jenis Inovasi</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ innovation.jenis_inovasi_label ?? '—' }}</p>
                </div>
                <div>
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Tahun</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ innovation.tahun }}</p>
                </div>
            </aside>
        </div>

        <div v-if="relatedInnovations.length" class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold text-surface-900">Inovasi Lainnya</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <button
                    v-for="item in relatedInnovations"
                    :key="item.id"
                    type="button"
                    class="flex gap-3 text-left rounded-xl border border-surface-200 p-3 hover:border-indigo-400 hover:shadow-sm transition-colors"
                    @click="router.visit(route('inovasi.show', item.slug))"
                >
                    <img
                        :src="item.cover || '/images/placeholder-cover.svg'"
                        :alt="item.title"
                        class="w-28 h-20 rounded-lg object-cover shrink-0 bg-surface-100"
                    />
                    <div class="min-w-0">
                        <p class="text-xs text-surface-500">{{ item.implementation_at ?? '—' }} &middot; {{ item.read_minutes }} menit</p>
                        <p class="font-semibold text-surface-900 truncate">{{ item.title }}</p>
                        <p class="text-xs text-surface-500 mt-1">{{ item.organisasi ?? '—' }}</p>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <Dialog
        v-model:visible="videoDialogVisible"
        header="Video Inovasi"
        modal
        dismissable-mask
        :style="{ width: '48rem', maxWidth: '95vw' }"
    >
        <div v-if="videoDialogVisible" class="aspect-video">
            <video v-if="videoEmbed?.type === 'video'" :src="videoEmbed.src" controls autoplay class="w-full h-full rounded-lg" />
            <iframe
                v-else
                :src="videoEmbed?.src"
                class="w-full h-full rounded-lg"
                allow="autoplay; encrypted-media; picture-in-picture"
                allowfullscreen
            />
        </div>
        <div v-if="videoDialogVisible" class="mt-2 text-right">
            <a :href="innovation.link_video" target="_blank" rel="noopener" class="text-sm text-indigo-600">
                Video tidak tampil? Buka di tab baru &rarr;
            </a>
        </div>
    </Dialog>
</template>
