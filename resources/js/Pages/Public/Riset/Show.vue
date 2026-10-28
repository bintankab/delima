<script setup>
import { Link } from '@inertiajs/vue3';
import DocumentList from '@/Components/DocumentList.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    riset: { type: Object, required: true },
});
</script>

<template>
    <section class="bg-indigo-700 text-white">
        <div class="max-w-4xl mx-auto px-4 py-12">
            <Link :href="route('riset')" class="text-sm text-indigo-200 hover:text-white">
                &larr; Kembali ke Daftar Riset
            </Link>
            <h1 class="text-2xl font-bold mt-3">{{ riset.title }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-indigo-100">
                <span v-if="riset.category">{{ riset.category }}</span>
                <span v-if="riset.category">&middot;</span>
                <span>{{ riset.tahun }}</span>
            </div>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white rounded-xl border border-surface-200 p-6 flex flex-col gap-6">
            <img
                v-if="riset.cover"
                :src="riset.cover.url"
                :alt="riset.title"
                class="w-full max-h-96 object-cover rounded-lg border border-surface-200"
            />

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Organisasi/OPD</dt>
                    <dd class="text-sm text-surface-800">{{ riset.organisasi ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Kategori</dt>
                    <dd class="text-sm text-surface-800">{{ riset.category ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Peneliti</dt>
                    <dd class="text-sm text-surface-800">{{ riset.peneliti ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tahun</dt>
                    <dd class="text-sm text-surface-800">{{ riset.tahun }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-surface-500 uppercase">Tanggal Terbit</dt>
                    <dd class="text-sm text-surface-800">{{ riset.published_at ?? '—' }}</dd>
                </div>
            </dl>

            <div>
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Abstrak</h2>
                <div class="rich-text" v-html="riset.abstrak" />
            </div>

            <div v-if="riset.latar_belakang">
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Latar Belakang</h2>
                <div class="rich-text" v-html="riset.latar_belakang" />
            </div>

            <div v-if="riset.metodologi">
                <h2 class="text-sm font-semibold text-surface-900 mb-1">Metodologi</h2>
                <div class="rich-text" v-html="riset.metodologi" />
            </div>

            <div v-if="riset.documents.length">
                <h2 class="text-sm font-semibold text-surface-900 mb-2">Dokumen Pendukung</h2>
                <DocumentList :documents="riset.documents" />
            </div>
        </div>
    </section>
</template>
