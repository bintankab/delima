<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DocumentList from '@/Components/DocumentList.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useVideoEmbed } from '@/Composables/useVideoEmbed';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    innovation: { type: Object, required: true },
});

const { getEmbed } = useVideoEmbed();

const videoDialogVisible = ref(false);
const videoEmbed = getEmbed(props.innovation.link_video);
</script>

<template>
    <section class="bg-indigo-700 text-white">
        <div class="max-w-4xl mx-auto px-4 py-12">
            <Link :href="route('inovasi')" class="text-sm text-indigo-200 hover:text-white">
                &larr; Kembali ke Data Inovasi
            </Link>
            <h1 class="text-2xl font-bold mt-3">{{ innovation.title }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-indigo-100">
                <span v-if="innovation.category">{{ innovation.category }}</span>
                <span v-if="innovation.category">&middot;</span>
                <span>{{ innovation.jenis_inovasi_label ?? '—' }}</span>
                <span>&middot;</span>
                <span>{{ innovation.tahun }}</span>
            </div>
            <Button
                v-if="videoEmbed"
                label="Tonton Video"
                icon="pi pi-play"
                class="mt-4"
                severity="contrast"
                @click="videoDialogVisible = true"
            />
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
                    <dd class="text-sm text-surface-800">{{ innovation.jenis_inovasi_label ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tahun</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.tahun }}</dd>
                </div>
                <div v-if="innovation.testing_at">
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tanggal Uji Coba</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.testing_at }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tanggal Implementasi</dt>
                    <dd class="text-sm text-surface-800">{{ innovation.implementation_at ?? '—' }}</dd>
                </div>
            </dl>

            <div>
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Deskripsi</h2>
                <div class="rich-text" v-html="innovation.description" />
            </div>

            <div v-if="innovation.latar_belakang">
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Latar Belakang</h2>
                <div class="rich-text" v-html="innovation.latar_belakang" />
            </div>

            <div v-if="innovation.manfaat">
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Manfaat</h2>
                <div class="rich-text" v-html="innovation.manfaat" />
            </div>

            <div v-if="innovation.documents.length">
                <h2 class="text-sm font-semibold text-surface-900 mb-2">Dokumen Pendukung</h2>
                <DocumentList :documents="innovation.documents" />
            </div>
        </div>
    </section>

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
