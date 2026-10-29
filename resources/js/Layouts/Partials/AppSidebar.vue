<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useMenu } from '@/Composables/useMenu';

const { sections } = useMenu();
const page = usePage();

// Kalau salah satu item menu cocok PERSIS dengan route saat ini, hanya item
// itu yang aktif — fallback wildcard di bawah tidak boleh ikut menyalakan
// item lain (mis. "Input Arsip Lama" aktif tidak boleh ikut menyalakan
// "Daftar Riset").
const exactActiveRoute = computed(() => {
    // Ziggy's route().current() reads window.location directly, which isn't
    // reactive on its own — referencing page.url ties this computed to every
    // Inertia navigation so the highlight doesn't get stuck on the first
    // page rendered by this (persistent) layout.
    void page.url;

    for (const section of sections.value) {
        for (const item of section.items) {
            if (route().current(item.route)) {
                return item.route;
            }
        }
    }

    return null;
});

const isActive = (routeName) => {
    if (exactActiveRoute.value !== null) {
        return exactActiveRoute.value === routeName;
    }

    if (!routeName.endsWith('.index')) {
        return false;
    }

    // Tidak ada item menu yang cocok persis (mis. sedang di halaman Lihat/Ubah
    // yang tidak punya entri menu sendiri) — nyalakan "Daftar X" terkait
    // sebagai penanda posisi.
    return route().current(`${routeName.slice(0, -'.index'.length)}.*`);
};
</script>

<template>
    <aside class="app-sidebar">
        <nav class="app-menu">
            <div v-for="section in sections" :key="section.label" class="app-menu-section">
                <p class="app-menu-section-label">{{ section.label }}</p>
                <ul>
                    <li v-for="item in section.items" :key="item.route">
                        <Link
                            :href="route(item.route)"
                            class="app-menu-item"
                            :class="{ 'app-menu-item-active': isActive(item.route) }"
                        >
                            <i :class="item.icon" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>
</template>
