<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const roles = page.props.auth.roles ?? [];

const isAdminRole = computed(() => ['super-admin', 'admin-opd', 'reviewer'].some((role) => roles.includes(role)));
const canSubmitInnovation = computed(() => ['bupati', 'dprd', 'asn', 'publik'].some((role) => roles.includes(role)));
const menuOpen = ref(false);

const closeMenu = () => {
  menuOpen.value = false;
};

watch(() => page.url, closeMenu);

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
  <header class="relative z-40 bg-black text-[#FFD700]">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
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
        <ThemeToggle />
        <Link
          v-if="isAdminRole"
          :href="route('admin.dashboard')"
          class="hidden lg:inline-block text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
        >
          Dashboard
        </Link>
        <template v-else-if="canSubmitInnovation">
          <Link
            :href="route('admin.innovations.create')"
            class="hidden lg:inline-block text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
          >
            Ajukan Inovasi
          </Link>
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="hidden lg:inline-block text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
          >
            Keluar
          </Link>
        </template>
        <Link
          v-else
          :href="route('login')"
          class="hidden lg:inline-block text-sm bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2"
        >
          Masuk
        </Link>
        <button
          type="button"
          class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20"
          :aria-expanded="menuOpen"
          aria-controls="public-mobile-nav"
          aria-label="Menu"
          @click="menuOpen = !menuOpen"
        >
          <i :class="menuOpen ? 'pi pi-times' : 'pi pi-bars'" />
        </button>
      </div>
    </div>

    <nav
      v-if="menuOpen"
      id="public-mobile-nav"
      class="lg:hidden absolute inset-x-0 top-full border-t border-neutral-800 bg-black shadow-lg"
    >
      <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col gap-1">
        <div v-for="item in navItems" :key="`mobile-${item.label}-${item.url}`" class="flex flex-col">
          <Link
            v-if="isInternal(item)"
            :href="item.url"
            class="rounded-lg px-3 py-2 text-sm hover:bg-neutral-800 hover:text-[#FFF3B0]"
            @click="closeMenu"
          >
            {{ item.label }}
          </Link>
          <a
            v-else
            :href="item.url"
            class="rounded-lg px-3 py-2 text-sm hover:bg-neutral-800 hover:text-[#FFF3B0]"
            target="_blank"
            rel="noopener noreferrer"
            @click="closeMenu"
          >
            {{ item.label }}
          </a>
          <template v-if="item.children?.length">
            <template v-for="child in item.children" :key="`mobile-${child.label}-${child.url}`">
              <Link
                v-if="isInternal(child)"
                :href="child.url"
                class="rounded-lg py-2 pr-3 pl-6 text-sm text-white/80 hover:bg-neutral-800 hover:text-[#FFD700]"
                @click="closeMenu"
              >
                {{ child.label }}
              </Link>
              <a
                v-else
                :href="child.url"
                class="rounded-lg py-2 pr-3 pl-6 text-sm text-white/80 hover:bg-neutral-800 hover:text-[#FFD700]"
                target="_blank"
                rel="noopener noreferrer"
                @click="closeMenu"
              >
                {{ child.label }}
              </a>
            </template>
          </template>
        </div>

        <div class="mt-2 flex flex-col gap-2 border-t border-neutral-800 pt-3">
          <Link
            v-if="isAdminRole"
            :href="route('admin.dashboard')"
            class="rounded-lg bg-white/10 px-3 py-2 text-sm hover:bg-white/20"
            @click="closeMenu"
          >
            Dashboard
          </Link>
          <template v-else-if="canSubmitInnovation">
            <Link
              :href="route('admin.innovations.create')"
              class="rounded-lg bg-white/10 px-3 py-2 text-sm hover:bg-white/20"
              @click="closeMenu"
            >
              Ajukan Inovasi
            </Link>
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="rounded-lg bg-white/10 px-3 py-2 text-left text-sm hover:bg-white/20"
              @click="closeMenu"
            >
              Keluar
            </Link>
          </template>
          <Link
            v-else
            :href="route('login')"
            class="rounded-lg bg-white/10 px-3 py-2 text-sm hover:bg-white/20"
            @click="closeMenu"
          >
            Masuk
          </Link>
        </div>
      </div>
    </nav>
  </header>
</template>
