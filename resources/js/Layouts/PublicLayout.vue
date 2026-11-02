<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';

const page = usePage();
const user = page.props.auth.user;
const roles = page.props.auth.roles ?? [];

const isAdminRole = computed(() => ['super-admin', 'admin-opd', 'reviewer'].some((role) => roles.includes(role)));
const canSubmitInnovation = computed(() => ['bupati', 'dprd', 'asn', 'publik'].some((role) => roles.includes(role)));

const fallbackNavigation = [
    { label: 'Beranda', url: '/' },
    { label: 'Kompetisi', url: '/kompetisi' },
    { label: 'Riset', url: '/riset' },
    { label: 'Inovasi', url: '/inovasi' },
    { label: 'Pustaka', url: '/pustaka' },
    { label: 'FAQ', url: '/faq' },
];

const navItems = computed(() => page.props.navigation ?? fallbackNavigation);

const isInternal = (item) => Boolean(item.url?.startsWith('/'))
    && !item.url.startsWith('//')
    && !item.opens_in_new_tab;
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
                    <div v-for="item in navItems" :key="`${item.label}-${item.url}`" class="relative group">
                        <Link
                            v-if="isInternal(item)"
                            :href="item.url"
                            class="inline-flex items-center gap-1 hover:text-indigo-200"
                        >
                            {{ item.label }}
                            <i v-if="item.children?.length" class="pi pi-chevron-down text-[10px]" />
                        </Link>
                        <a
                            v-else
                            :href="item.url"
                            class="inline-flex items-center gap-1 hover:text-indigo-200"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ item.label }}
                            <i v-if="item.children?.length" class="pi pi-chevron-down text-[10px]" />
                        </a>
                        <div
                            v-if="item.children?.length"
                            class="absolute left-0 top-full z-20 hidden min-w-48 pt-2 group-hover:block group-focus-within:block"
                        >
                            <div class="rounded-lg bg-white py-2 text-surface-800 shadow-lg">
                                <template v-for="child in item.children" :key="`${child.label}-${child.url}`">
                                    <Link
                                        v-if="isInternal(child)"
                                        :href="child.url"
                                        class="block px-4 py-2 hover:bg-surface-100"
                                    >
                                        {{ child.label }}
                                    </Link>
                                    <a
                                        v-else
                                        :href="child.url"
                                        class="block px-4 py-2 hover:bg-surface-100"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ child.label }}
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
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
