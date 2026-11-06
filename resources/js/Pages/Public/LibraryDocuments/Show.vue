<script setup>
import { Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    document: { type: Object, required: true },
    relatedDocuments: { type: Array, default: () => [] },
});

const toast = useToast();

const typeSeverity = {
    regulasi: 'info',
    panduan: 'success',
    laporan: 'warn',
    lainnya: 'secondary',
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
    const text = encodeURIComponent(`${props.document.title} — ${window.location.href}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
};
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 py-8 flex flex-col gap-6">
        <Link :href="route('pustaka')" class="text-sm text-white/80 hover:text-[#FFD700] w-fit">
            &larr; Kembali ke Pustaka
        </Link>

        <div class="rounded-2xl bg-indigo-700 text-white p-5 sm:p-8">
            <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm font-medium uppercase tracking-wide text-white/80">
                <Tag
                    :value="document.type_label"
                    :severity="typeSeverity[document.type] ?? 'secondary'"
                />
                <span v-if="document.nomor_dokumen">{{ document.nomor_dokumen }}</span>
                <span>&middot; {{ document.read_minutes }} Menit Baca</span>
            </div>
            <h1 class="text-xl sm:text-3xl font-bold mt-3">{{ document.title }}</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 bg-white rounded-2xl border border-surface-200 p-5 sm:p-8">
            <div class="lg:col-span-2 flex flex-col gap-6">
                <p v-if="document.description" class="text-base text-surface-800 whitespace-pre-line">{{ document.description }}</p>
                <p v-else class="text-sm text-surface-400">Belum ada deskripsi untuk dokumen ini.</p>

                <div v-if="document.file">
                    <h2 class="text-lg font-semibold text-surface-900 mb-2">Berkas</h2>
                    <a
                        :href="document.file.url"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-3 text-sm bg-surface-50 rounded-lg px-3 py-2 text-indigo-600 w-fit max-w-full"
                    >
                        <i class="pi pi-file-pdf" />
                        <span class="truncate">{{ document.file.name }}</span>
                        <span class="text-surface-400 shrink-0">({{ document.file.size }})</span>
                        <i class="pi pi-download text-xs shrink-0" />
                    </a>
                </div>

                <div class="border-t border-surface-200 pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="font-semibold text-surface-900">{{ document.organisasi ?? 'Pemerintah Kabupaten Bintan' }}</p>
                        <p class="text-sm text-surface-500">
                            {{ document.penyusun ? `Disusun oleh ${document.penyusun}` : document.type_label }}
                            &middot; {{ document.read_minutes }} menit baca
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-link" severity="secondary" outlined rounded aria-label="Salin tautan" @click="copyLink" />
                        <Button icon="pi pi-whatsapp" severity="secondary" outlined rounded aria-label="Bagikan ke WhatsApp" @click="shareWhatsapp" />
                    </div>
                </div>
            </div>

            <aside class="flex flex-col gap-4 lg:border-l lg:border-surface-200 lg:pl-6">
                <div class="flex items-center justify-between pb-4 border-b border-surface-200 text-sm">
                    <span class="font-semibold text-surface-800">{{ document.read_minutes }} menit baca</span>
                    <span class="inline-flex items-center gap-1 text-surface-500">
                        <i class="pi pi-eye" />
                        {{ document.views_count }}
                    </span>
                </div>

                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Diunduh</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ document.downloads_count }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Diterbitkan</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ document.tanggal_terbit ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Jenis</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ document.type_label }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Nomor Dokumen</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ document.nomor_dokumen ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Organisasi</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ document.organisasi ?? '—' }}</p>
                </div>
                <div class="pb-4 border-b border-surface-200">
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Penyusun</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ document.penyusun ?? '—' }}</p>
                </div>
                <div>
                    <h3 class="text-xs font-medium text-surface-500 uppercase">Tahun Terbit</h3>
                    <p class="text-sm text-surface-800 mt-1">{{ document.tahun_terbit ?? '—' }}</p>
                </div>
            </aside>
        </div>

        <div v-if="relatedDocuments.length" class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold text-white">Dokumen Lainnya</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <button
                    v-for="item in relatedDocuments"
                    :key="item.id"
                    type="button"
                    class="text-left rounded-xl border border-surface-200 bg-white p-3 hover:border-indigo-400 hover:shadow-sm transition-colors"
                    @click="router.visit(route('pustaka.show', item.slug))"
                >
                    <p class="text-xs text-surface-500">{{ item.type_label }}<span v-if="item.tahun_terbit"> &middot; {{ item.tahun_terbit }}</span></p>
                    <p class="font-semibold text-surface-900 mt-1">{{ item.title }}</p>
                    <p class="text-xs text-surface-500 mt-1">{{ item.organisasi ?? '—' }}</p>
                </button>
            </div>
        </div>
    </div>
</template>
