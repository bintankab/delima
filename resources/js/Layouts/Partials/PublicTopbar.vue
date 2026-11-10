<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
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
  <header class="bg-black text-[#FFD700]">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
      <Link href="/" class="font-bold text-lg flex items-center gap-2">
        <img src="/images/logo-2.png" alt="" class="h-9 w-auto" />
        <span>DELIMA</span>
      </Link>
      <nav class="hidden lg:flex items-center gap-6 text-sm">
        <div v-for="item in navItems" :key="`${item.label}-${item.url}`" class="relative group">
          <Link
            v-if="isInternal(item)"
            :href="item.url"
            class="inline-flex items-center gap-1 hover:text-[#FFF3B0]"
          >
            {{ item.label }}
            <i v-if="item.children?.length" class="pi pi-chevron-down text-[10px]" />
          </Link>
          <a
            v-else
            :href="item.url"
            class="inline-flex items-center gap-1 hover:text-[#FFF3B0]"
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
            <div class="rounded-lg bg-black border border-neutral-800 py-2 text-white shadow-lg">
              <template v-for="child in item.children" :key="`${child.label}-${child.url}`">
                <Link
                  v-if="isInternal(child)"
                  :href="child.url"
                  class="block px-4 py-2 hover:bg-neutral-800 hover:text-[#FFD700]"
                >
                  {{ child.label }}
                </Link>
                <a
                  v-else
                  :href="child.url"
                  class="block px-4 py-2 hover:bg-neutral-800 hover:text-[#FFD700]"
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
</template>
