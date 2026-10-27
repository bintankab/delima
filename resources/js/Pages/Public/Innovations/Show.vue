<script setup>
import { Link } from '@inertiajs/vue3';
import DocumentList from '@/Components/DocumentList.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    innovation: { type: Object, required: true },
});

const jenisLabel = (jenis) => ({
    produk: 'Produk',
    proses: 'Proses',
    pelayanan: 'Pelayanan',
}[jenis] ?? jenis);
</script>

<template>
    <section class="bg-indigo-700 text-white">
        <div class="max-w-4xl mx-auto px-4 py-12">
            <Link :href="route('inovasi')" class="text-sm text-indigo-200 hover:text-white">
                &larr; Kembali ke Daftar Inovasi
            </Link>
            <h1 class="text-2xl font-bold mt-3">{{ innovation.title }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-indigo-100">
                <span v-if="innovation.category">{{ innovation.category }}</span>
                <span v-if="innovation.category">&middot;</span>
                <span>{{ jenisLabel(innovation.jenis_inovasi) }}</span>
                <span>&middot;</span>
                <span>{{ innovation.tahun }}</span>
            </div>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white rounded-xl border border-surface-200 p-6 flex flex-col gap-6">
            <img
                v-if="innovation.cover"
                :src="innovation.cover.url"
                :alt="innovation.title"
                class="w-full max-h-96 object-cover rounded-lg border border-surface-200"
            />

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Organisasi/OPD</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.organisasi ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Kategori</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.category ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Jenis Inovasi</dt>
                    <dd class="text-sm text-surface-800">{{ jenisLabel(innovation.jenis_inovasi) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tahun</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.tahun }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tanggal Terbit</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.published_at ?? '—' }}</dd>
                </div>
            </dl>

            <div>
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Deskripsi</h2>
                <p class="text-sm text-surface-600 whitespace-pre-line">{{ innovation.description }}</p>
            </div>

            <div v-if="innovation.latar_belakang">
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Latar Belakang</h2>
                <p class="text-sm text-surface-600 whitespace-pre-line">{{ innovation.latar_belakang }}</p>
            </div>

            <div v-if="innovation.documents.length">
                <h2 class="text-sm font-semibold text-surface-900 mb-2">Dokumen Pendukung</h2>
                <DocumentList :documents="innovation.documents" />
            </div>
        </div>
    </section>
</template>
