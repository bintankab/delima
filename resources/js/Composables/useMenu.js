import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const MENU = {
    'super-admin': [
        {
            label: 'Dashboard',
            items: [{ label: 'Analytics', icon: 'pi pi-chart-line', route: 'admin.dashboard' }],
        },
        {
            label: 'Inovasi',
            items: [
                { label: 'Daftar Inovasi', icon: 'pi pi-lightbulb', route: 'admin.innovations.index' },
                { label: 'Input Arsip Lama', icon: 'pi pi-box', route: 'admin.innovations.archive.create' },
                { label: 'Kategori', icon: 'pi pi-tags', route: 'admin.innovation-categories.index' },
            ],
        },
        {
            label: 'Riset',
            items: [
                { label: 'Daftar Riset', icon: 'pi pi-book', route: 'admin.risets.index' },
                { label: 'Input Arsip Riset Lama', icon: 'pi pi-box', route: 'admin.risets.archive.create' },
                { label: 'Kategori', icon: 'pi pi-tags', route: 'admin.riset-categories.index' },
            ],
        },
        {
            label: 'Kompetisi',
            items: [
                { label: 'Daftar Kompetisi', icon: 'pi pi-trophy', route: 'admin.competitions.index' },
                { label: 'Submission Masuk', icon: 'pi pi-inbox', route: 'admin.competition-submissions.index' },
            ],
        },
        {
            label: 'Keterlibatan Publik',
            items: [
                { label: 'Ide Inovasi', icon: 'pi pi-comment', route: 'admin.ideas.index' },
                { label: 'Klinik Inovasi', icon: 'pi pi-heart', route: 'admin.consultations.index' },
            ],
        },
        {
            label: 'Konten',
            items: [
                { label: 'Berita', icon: 'pi pi-megaphone', route: 'admin.news.index' },
                { label: 'Agenda', icon: 'pi pi-calendar', route: 'admin.events.index' },
                { label: 'Pengumuman', icon: 'pi pi-bell', route: 'admin.announcements.index' },
                { label: 'Pustaka', icon: 'pi pi-folder-open', route: 'admin.library-documents.index' },
                { label: 'FAQ', icon: 'pi pi-question-circle', route: 'admin.faqs.index' },
            ],
        },
        {
            label: 'Administrasi',
            items: [
                { label: 'Organisasi/OPD', icon: 'pi pi-building', route: 'admin.organisasis.index' },
                { label: 'Pengguna', icon: 'pi pi-users', route: 'admin.users.index' },
            ],
        },
    ],
    'admin-opd': [
        {
            label: 'Dashboard',
            items: [{ label: 'Ringkasan', icon: 'pi pi-chart-line', route: 'admin.dashboard' }],
        },
        {
            label: 'Inovasi Kami',
            items: [{ label: 'Inovasi OPD', icon: 'pi pi-lightbulb', route: 'admin.innovations.index' }],
        },
        {
            label: 'Riset Kami',
            items: [{ label: 'Riset OPD', icon: 'pi pi-book', route: 'admin.risets.index' }],
        },
        {
            label: 'Kompetisi',
            items: [{ label: 'Ikuti Kompetisi', icon: 'pi pi-trophy', route: 'admin.competition-submissions.index' }],
        },
        {
            label: 'Lainnya',
            items: [
                { label: 'Pustaka OPD', icon: 'pi pi-folder-open', route: 'admin.library-documents.index' },
                { label: 'Klinik Inovasi', icon: 'pi pi-heart', route: 'admin.consultations.index' },
            ],
        },
    ],
    reviewer: [
        {
            label: 'Dashboard',
            items: [{ label: 'Ringkasan', icon: 'pi pi-chart-line', route: 'admin.dashboard' }],
        },
        {
            label: 'Review',
            items: [
                { label: 'Review Inovasi', icon: 'pi pi-check-square', route: 'admin.innovations.index' },
                { label: 'Review Riset', icon: 'pi pi-check-square', route: 'admin.risets.index' },
                { label: 'Review Kompetisi', icon: 'pi pi-trophy', route: 'admin.competition-submissions.index' },
            ],
        },
    ],
};

export function useMenu() {
    const page = usePage();

    const roles = computed(() => page.props.auth?.roles ?? []);

    const sections = computed(() => {
        const primaryRole = ['super-admin', 'admin-opd', 'reviewer'].find((r) =>
            roles.value.includes(r),
        );

        return MENU[primaryRole] ?? [];
    });

    return { sections };
}
