<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';
import { ref } from 'vue';
import { useLayout } from '@/Composables/useLayout';

const { toggleSidebar } = useLayout();
const page = usePage();
const user = page.props.auth.user;

const userMenu = ref();
const userMenuItems = [
    {
        label: 'Profil',
        icon: 'pi pi-user',
        command: () => router.visit(route('profile.edit')),
    },
    {
        label: 'Keluar',
        icon: 'pi pi-sign-out',
        command: () => router.post(route('logout')),
    },
];
</script>

<template>
    <div class="app-topbar">
        <div class="flex items-center gap-3">            
            <button
                type="button"
                class="topbar-icon-btn hidden lg:inline-flex"
                @click="toggleSidebar"
            >
                <i class="pi pi-bars" />
            </button>
            <Link href="/" class="flex items-center gap-2 text-white font-semibold tracking-wide">
                <i class="pi pi-sitemap text-xl" />
                <span>DELIMA</span>
            </Link>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" class="topbar-icon-btn">
                <i class="pi pi-bell" />
            </button>
            <button type="button" class="flex items-center gap-2 pl-2" @click="userMenu.toggle($event)">
                <Avatar :label="user.name.charAt(0)" shape="circle" class="bg-white/20 text-white" />
                <span class="hidden md:inline text-white text-sm">{{ user.name }}</span>
                <i class="pi pi-angle-down text-white text-xs hidden md:inline" />
            </button>
            <Menu ref="userMenu" :model="userMenuItems" :popup="true" />
        </div>
    </div>
</template>
