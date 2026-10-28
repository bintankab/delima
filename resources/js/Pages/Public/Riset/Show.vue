<script setup>
import { Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import DocumentList from '@/Components/DocumentList.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    riset: { type: Object, required: true },
    relatedRisets: { type: Array, default: () => [] },
});

const toast = useToast();

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
    const text = encodeURIComponent(`${props.riset.title} — ${window.location.href}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
};
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 py-8 flex flex-col gap-6">
        <Link :href="route('riset')" class="text-sm text-surface-500 hover:text-indigo-600 w-fit">
            &larr; Kembali ke Daftar Riset
        </Link>

        <div class="relative rounded-2xl overflow-hidden min-h-[260px] sm:min-h-[420px] bg-surface-800 flex items-end">
            <img
                v-if="riset.cover"
                :src="riset.cover.url"
                :alt="riset.title"
                class="absolute inset-0 w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
            <div class="relative z-10 p-5 sm:p-8 text-white">
                <p class="text-xs sm:text-sm font-medium uppercase tracking-wide text-white/80">
                    {{ riset.category ?? 'Riset' }} &middot; {{ riset.read_minutes }} Menit Baca
                </p>
                <h1 class="text-xl sm:text-3xl font-bold mt-2">{{ riset.title }}</h1>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 flex flex-col gap-6">
                <div class="rich-text text-base" v-html="riset.abstrak" />

                <div v-if="riset.latar_belakang">
                    <h2 class="text-lg font-semibold text-surface-900 mb-2">Latar Belakang</h2>
                    <div class="rich-text" v-html="riset.latar_belakang" />
                </div>

                <div v-if="riset.metodologi">
                    <h2 class="text-lg font-semibold text-surface-900 mb-2">Metodologi</h2>
                    <div class="rich-text" v-html="riset.metodologi" />
                </div>

                <div v-if="riset.documents.length">
                    <h2 class="text-lg font-semibold text-surface-900 mb-2">Dokumen Pendukung</h2>
                    <DocumentList :documents="riset.documents" />
                </div>

                <div class="border-t border-surface-200 pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold shrink-0">
                            {{ initials(riset.organisasi) }}
                        </div>
                        <div>
                            <p class="font-semibold text-surface-900">{{ riset.organisasi ?? 'Pemerintah Kabupaten Bintan' }}</p>
                            <p class="text-sm text-surface-500">{{ riset.category ?? '—' }} &middot; {{ riset.read_minutes }} menit baca</p>
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
                    <span class="font-semibold text-surface-800">{{ riset.read_minutes }} menit baca</span>
                    <span class="inline-flex items-center gap-1 text-surface-500">
                        <i class="pi pi-eye" />
                        {{ riset.views_count }}
                    </span>
                </div>

                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Diterbitkan</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ riset.published_at ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Peneliti</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ riset.peneliti ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Kategori</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ riset.category ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">OPD</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ riset.organisasi ?? '—' }}</p>
                </div>
                <div>
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Tahun</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ riset.tahun }}</p>
                </div>
            </aside>
        </div>

        <div v-if="relatedRisets.length" class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold text-surface-900">Riset Lainnya</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <button
                    v-for="item in relatedRisets"
                    :key="item.id"
                    type="button"
                    class="flex gap-3 text-left rounded-xl border border-surface-200 p-3 hover:border-indigo-400 hover:shadow-sm transition-colors"
                    @click="router.visit(route('riset.show', item.slug))"
                >
                    <img
                        :src="item.cover || '/images/placeholder-cover.svg'"
                        :alt="item.title"
                        class="w-28 h-20 rounded-lg object-cover shrink-0 bg-surface-100"
                    />
                    <div class="min-w-0">
                        <p class="text-xs text-surface-500">{{ item.published_at ?? '—' }} &middot; {{ item.read_minutes }} menit</p>
                        <p class="font-semibold text-surface-900 truncate">{{ item.title }}</p>
                        <p class="text-xs text-surface-500 mt-1">{{ item.organisasi ?? '—' }}</p>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
