<template>
  <div class="flex h-screen bg-background">
    <!-- Sidebar -->
    <aside class="w-64 bg-card border-r border-border flex flex-col">
      <!-- Logo -->
      <div class="p-6 border-b border-border">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
            <ChatBubbleLeftIcon class="w-5 h-5 text-primary" />
          </div>
          <span class="font-semibold text-lg text-foreground">JOBS SMS</span>
        </div>
        <p class="text-xs text-muted-foreground mt-2 ml-11">Gestion de campagnes</p>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-1">
        <router-link
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
          :class="isActive(item.path) ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'"
        >
          <component :is="item.icon" class="w-5 h-5" />
          <span>{{ item.label }}</span>
        </router-link>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="h-16 bg-white border-b border-border px-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-foreground">{{ pageTitle }}</h1>
        <div class="flex items-center gap-4">
          <button
            @click="goToCreateCampaign"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Créer une Nouvelle Campagne</span>
          </button>
          <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10">
            <BellIcon class="w-5 h-5" />
          </button>

          <!-- User Menu -->
          <div class="relative">
            <button
              @click="toggleUserMenu"
              class="flex items-center gap-3 p-2 rounded-lg hover:bg-accent transition-colors"
            >
              <div class="relative flex shrink-0 overflow-hidden rounded-full w-8 h-8 bg-primary flex items-center justify-center text-primary-foreground font-semibold text-sm">
                {{ userInitials }}
              </div>
              <div class="flex-1 min-w-0 text-left hidden md:block">
                <p class="text-sm font-medium text-foreground truncate">{{ user?.name }}</p>
                <p class="text-xs text-muted-foreground truncate">Admin</p>
              </div>
              <ChevronDownIcon class="w-4 h-4 text-muted-foreground hidden md:block" />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="showUserMenu"
              class="absolute right-0 mt-2 w-56 rounded-md border bg-card shadow-lg z-50"
            >
              <div class="p-3 border-b border-border">
                <p class="text-sm font-medium text-foreground">{{ user?.name }}</p>
                <p class="text-xs text-muted-foreground">{{ user?.email }}</p>
              </div>
              <div class="p-1">
                <button
                  @click="goToProfile"
                  class="w-full flex items-center gap-3 px-3 py-2 text-sm rounded-md hover:bg-accent transition-colors text-left"
                >
                  <UserCircleIcon class="w-4 h-4" />
                  <span>Profil</span>
                </button>
                <button
                  @click="handleLogout"
                  class="w-full flex items-center gap-3 px-3 py-2 text-sm rounded-md hover:bg-destructive/10 text-destructive transition-colors text-left"
                >
                  <ArrowRightOnRectangleIcon class="w-4 h-4" />
                  <span>Déconnexion</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Page Content -->
      <div class="flex-1 overflow-y-auto">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  ChartBarIcon,
  PlusIcon,
  UsersIcon,
  DocumentTextIcon,
  CalendarIcon,
  ChartPieIcon,
  CreditCardIcon,
  CodeBracketIcon,
  LinkIcon,
  Cog6ToothIcon,
  ArrowRightOnRectangleIcon,
  BellIcon,
  UserCircleIcon,
  ChevronDownIcon,
  ChatBubbleLeftIcon,
  PaperAirplaneIcon,
  ClockIcon,
  InboxIcon
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user)
const showUserMenu = ref(false)

const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const menuItems = [
  { path: '/dashboard', label: 'Tableau de bord', icon: ChartBarIcon },
  { path: '/send-message', label: 'Envoyer un message', icon: PaperAirplaneIcon },
  { path: '/campaign/create', label: 'Nouvelle campagne', icon: PlusIcon },
  { path: '/contacts', label: 'Contacts', icon: UsersIcon },
  { path: '/templates', label: 'Modèles', icon: DocumentTextIcon },
  { path: '/calendar', label: 'Calendrier', icon: CalendarIcon },
  { path: '/campaigns/history', label: 'Historique campagnes', icon: ClockIcon },
  { path: '/messages/history', label: 'Historique messages', icon: InboxIcon },
  { path: '/reports', label: 'Rapports', icon: ChartPieIcon },
  { path: '/accounts', label: 'Comptes', icon: CreditCardIcon },
  { path: '/api', label: 'Configuration API SMS', icon: CodeBracketIcon },
  { path: '/webhooks', label: 'Webhooks', icon: LinkIcon },
  { path: '/settings', label: 'Paramètres', icon: Cog6ToothIcon },
]

const pageTitle = computed(() => {
  const currentItem = menuItems.find(item => isActive(item.path))
  return currentItem?.label || 'JOBS SMS'
})

function isActive(path: string): boolean {
  return route.path === path || route.path.startsWith(path + '/')
}

function goToCreateCampaign() {
  router.push('/campaign/create')
}

function goToProfile() {
  showUserMenu.value = false
  router.push('/profile')
}

async function handleLogout() {
  showUserMenu.value = false
  await authStore.logout()
  router.push('/login')
}

function toggleUserMenu() {
  showUserMenu.value = !showUserMenu.value
}
</script>
