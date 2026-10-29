<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';

const page = usePage();
const user = page.props.auth.user;
const roles = page.props.auth.roles ?? [];

const isAdminRole = computed(() => ['super-admin', 'admin-opd', 'reviewer'].some((role) => roles.includes(role)));
const canSubmitInnovation = computed(() => ['bupati', 'dprd', 'asn', 'publik'].some((role) => roles.includes(role)));

const navItems = [
    { label: 'Beranda', href: '/' },
    { label: 'Etalase', href: '/etalase' },
    { label: 'Kompetisi', href: '/kompetisi' },
    { label: 'Riset', href: '/riset' },
    { label: 'Inovasi', href: '/inovasi' },
    { label: 'Ide Inovasi', href: '/ide-inovasi' },
    { label: 'Pustaka', href: '/pustaka' },
    { label: 'FAQ', href: '/faq' },
];
</script>

<template>
    <div class="min-h-screen bg-surface-50 flex flex-col">
        <Toast />

        <header class="bg-indigo-800 text-white">
            <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
                <Link href="/" class="font-bold text-lg flex items-center gap-2">
                    <i class="pi pi-sitemap" />
                    DELIMA
                </Link>
                <nav class="hidden lg:flex items-center gap-6 text-sm">
                    <Link v-for="item in navItems" :key="item.href" :href="item.href" class="hover:text-indigo-200">
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="isAdminRole"
                        :href="route('admin.dashboard')"
                        class="text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
                    >
                        Dashboard
                    </Link>
                    <template v-else-if="canSubmitInnovation">
                        <Link
                            :href="route('admin.innovations.create')"
                            class="text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
                        >
                            Ajukan Inovasi
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
                        >
                            Keluar
                        </Link>
                    </template>
                    <Link
                        v-else
                        :href="route('login')"
                        class="text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
                    >
                        Masuk
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="bg-surface-900 text-surface-300 text-sm py-8 mt-10">
            <div class="max-w-6xl mx-auto px-4">
                <p class="font-semibold text-white">DELIMA — Simpul Jaring Inovasi Daerah</p>
                <p class="mt-1 text-surface-400">
                    Dokumentasi, diseminasi, dan replikasi inovasi daerah Kabupaten Bintan.
                </p>
            </div>
        </footer>
    </div>
</template>
