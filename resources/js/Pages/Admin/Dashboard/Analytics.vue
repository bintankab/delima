<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    stats: { type: Object, required: true },
    innovationsByStatus: { type: Object, required: true },
    latestSubmissions: { type: Array, required: true },
});

const statCards = computed(() => [
    { label: 'Total Inovasi', value: props.stats.total_innovations, icon: 'pi pi-lightbulb', color: 'bg-indigo-50 text-indigo-600' },
    { label: 'Inovasi Terbit', value: props.stats.published_innovations, icon: 'pi pi-check-circle', color: 'bg-emerald-50 text-emerald-600' },
    { label: 'Ide Masuk', value: props.stats.open_ideas, icon: 'pi pi-comment', color: 'bg-amber-50 text-amber-600' },
    { label: 'Konsultasi Aktif', value: props.stats.active_consultations, icon: 'pi pi-heart', color: 'bg-rose-50 text-rose-600' },
    { label: 'Peserta Kompetisi', value: props.stats.competition_submissions, icon: 'pi pi-trophy', color: 'bg-sky-50 text-sky-600' },
]);

const chartData = computed(() => ({
    labels: props.innovationsByStatus.labels,
    datasets: [
        {
            label: 'Jumlah Inovasi',
            backgroundColor: '#6366f1',
            borderRadius: 6,
            data: props.innovationsByStatus.data,
        },
    ],
}));

const chartOptions = {
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { precision: 0 } },
    },
};

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
    <div class="flex flex-col gap-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="bg-white rounded-xl border border-surface-200 p-4 flex items-center gap-3"
            >
                <div class="w-11 h-11 rounded-lg flex items-center justify-center" :class="card.color">
                    <i :class="card.icon" class="text-lg" />
                </div>
                <div>
                    <p class="text-2xl font-semibold text-surface-900 leading-tight">{{ card.value }}</p>
                    <p class="text-xs text-surface-500">{{ card.label }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white rounded-xl border border-surface-200 p-5">
                <h2 class="text-sm font-semibold text-surface-900 mb-4">Inovasi per Status</h2>
                <Chart type="bar" :data="chartData" :options="chartOptions" class="h-72" />
            </div>

            <div class="bg-white rounded-xl border border-surface-200 p-5">
                <h2 class="text-sm font-semibold text-surface-900 mb-4">Submission Terbaru</h2>
                <ul class="flex flex-col gap-3">
                    <li
                        v-for="item in latestSubmissions"
                        :key="item.id"
                        class="flex items-start justify-between gap-2 text-sm"
                    >
                        <div class="min-w-0">
                            <p class="text-surface-800 truncate">{{ item.title }}</p>
                            <p class="text-surface-400 text-xs">{{ item.organisasi?.name ?? '-' }}</p>
                        </div>
                        <Tag :value="item.status" :severity="statusSeverity[item.status] ?? 'secondary'" />
                    </li>
                    <li v-if="!latestSubmissions.length" class="text-surface-400 text-sm">
                        Belum ada data.
                    </li>
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-surface-900">Daftar Submission Perlu Ditindaklanjuti</h2>
                <Link :href="route('admin.innovations.index')" class="text-xs text-indigo-600 font-medium">
                    Lihat Semua
                </Link>
            </div>
            <DataTable :value="latestSubmissions" size="small" :paginator="latestSubmissions.length > 5" :rows="5">
                <Column field="title" header="Judul Inovasi" />
                <Column header="OPD">
                    <template #body="{ data }">{{ data.organisasi?.name ?? '-' }}</template>
                </Column>
                <Column header="Kategori">
                    <template #body="{ data }">{{ data.category?.name ?? '-' }}</template>
                </Column>
                <Column field="tahun" header="Tahun" />
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="statusSeverity[data.status] ?? 'secondary'" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
