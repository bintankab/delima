<script setup>
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    innovation: { type: Object, required: true },
});

const jenisLabel = (jenis) => ({
    produk: 'Produk',
    proses: 'Proses',
    pelayanan: 'Pelayanan',
}[jenis] ?? jenis ?? '—');

const statusLabel = (status) => ({
    draft: 'Draf',
    submitted: 'Diajukan',
    under_review: 'Ditinjau',
    reviewed: 'Direview',
    revision_needed: 'Perlu revisi',
    published: 'Tayang',
    rejected: 'Ditolak',
    archived: 'Diarsipkan',
}[status] ?? status);

const statusSeverity = {
    draft: 'secondary',
    submitted: 'info',
    under_review: 'info',
    reviewed: 'warn',
    revision_needed: 'warn',
    published: 'success',
    rejected: 'danger',
    archived: 'contrast',
};
</script>

<template>
    <div class="max-w-3xl flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">{{ innovation.title }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <Tag :value="statusLabel(innovation.status)" :severity="statusSeverity[innovation.status] ?? 'secondary'" />
                    <span class="text-sm text-surface-500">{{ innovation.origin === 'archive' ? 'Arsip' : 'Pengajuan' }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <Button
                    label="Ubah"
                    icon="pi pi-pencil"
                    severity="secondary"
                    @click="router.visit(route('admin.innovations.edit', innovation.id))"
                />
                <Button
                    label="Kembali"
                    severity="secondary"
                    text
                    @click="router.visit(route('admin.innovations.index'))"
                />
            </div>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-6 flex flex-col gap-5">
            <img
                v-if="innovation.cover"
                :src="innovation.cover.thumb || innovation.cover.url"
                :alt="innovation.title"
                class="w-full max-w-md rounded-lg border border-surface-200"
            />

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">OPD</dt>
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
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tanggal terbit</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.published_at ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Diinput oleh</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.user ?? '—' }}</dd>
                </div>
            </dl>

            <div>
                <h2 class="text-sm font-medium text-surface-700 mb-1">Deskripsi</h2>
                <p class="text-sm text-surface-600 whitespace-pre-line">{{ innovation.description }}</p>
            </div>

            <div v-if="innovation.latar_belakang">
                <h2 class="text-sm font-medium text-surface-700 mb-1">Latar belakang</h2>
                <p class="text-sm text-surface-600 whitespace-pre-line">{{ innovation.latar_belakang }}</p>
            </div>

            <div v-if="innovation.documents.length">
                <h2 class="text-sm font-medium text-surface-700 mb-2">Dokumen pendukung</h2>
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="document in innovation.documents"
                        :key="document.id"
                        class="flex items-center justify-between gap-2 text-sm bg-surface-50 rounded-lg px-3 py-2"
                    >
                        <span class="flex items-center gap-2 text-surface-700 min-w-0">
                            <i class="pi pi-file" />
                            <span class="truncate">{{ document.name }}</span>
                            <span class="text-surface-400 shrink-0">({{ document.size }})</span>
                        </span>
                        <a :href="document.url" target="_blank" rel="noopener" class="text-indigo-600 shrink-0">
                            Unduh
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
