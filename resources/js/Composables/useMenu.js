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
                { label: 'Data Inovasi', icon: 'pi pi-lightbulb', route: 'admin.innovations.index' },
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
            label: 'Konten',
            items: [
                { label: 'Kategori Post', icon: 'pi pi-tags', route: 'admin.post-categories.index' },
                { label: 'Post', icon: 'pi pi-megaphone', route: 'admin.posts.index' },
                { label: 'Page', icon: 'pi pi-file', route: 'admin.pages.index' },                
                { label: 'Pustaka', icon: 'pi pi-folder-open', route: 'admin.library-documents.index' },
                { label: 'FAQ', icon: 'pi pi-question-circle', route: 'admin.faqs.index' },
                { label: 'Menu', icon: 'pi pi-bars', route: 'admin.menus.index' },
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
            items: [
                { label: 'Inovasi OPD', icon: 'pi pi-lightbulb', route: 'admin.innovations.index' },
                { label: 'Ajukan Inovasi', icon: 'pi pi-plus-circle', route: 'admin.innovations.create' },
            ],
        },
        {
            label: 'Riset Kami',
            items: [
                { label: 'Riset OPD', icon: 'pi pi-book', route: 'admin.risets.index' },
                { label: 'Ajukan Riset', icon: 'pi pi-plus-circle', route: 'admin.risets.create' },
            ],
        },
        {
            label: 'Lainnya',
            items: [
                { label: 'Staf ASN', icon: 'pi pi-id-card', route: 'admin.users.index' },
            ],
        },
    ],
    bupati: [
        {
            label: 'Inovasi',
            items: [
                { label: 'Ajukan Inovasi', icon: 'pi pi-plus-circle', route: 'admin.innovations.create' },
                { label: 'Inovasi Saya', icon: 'pi pi-list', route: 'admin.innovations.mine' },
            ],
        },
    ],
    dprd: [
        {
            label: 'Inovasi',
            items: [
                { label: 'Ajukan Inovasi', icon: 'pi pi-plus-circle', route: 'admin.innovations.create' },
                { label: 'Inovasi Saya', icon: 'pi pi-list', route: 'admin.innovations.mine' },
            ],
        },
    ],
    asn: [
        {
            label: 'Inovasi',
            items: [
                { label: 'Ajukan Inovasi', icon: 'pi pi-plus-circle', route: 'admin.innovations.create' },
                { label: 'Inovasi Saya', icon: 'pi pi-list', route: 'admin.innovations.mine' },
            ],
        },
    ],
    publik: [
        {
            label: 'Inovasi',
            items: [
                { label: 'Ajukan Inovasi', icon: 'pi pi-plus-circle', route: 'admin.innovations.create' },
                { label: 'Inovasi Saya', icon: 'pi pi-list', route: 'admin.innovations.mine' },
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
            ],
        },
    ],
};

export function useMenu() {
    const page = usePage();

    const roles = computed(() => page.props.auth?.roles ?? []);

    const sections = computed(() => {
        const primaryRole = ['super-admin', 'admin-opd', 'reviewer', 'bupati', 'dprd', 'asn', 'publik'].find((r) =>
            roles.value.includes(r),
        );

        return MENU[primaryRole] ?? [];
    });

    return { sections };
}
