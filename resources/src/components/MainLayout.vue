<template>
  <div class="flex h-screen bg-background">
    <!-- Mobile Menu Button -->
    <button
      @click="sidebarOpen = !sidebarOpen"
      class="lg:hidden fixed top-3 left-3 z-50 p-2 rounded-lg bg-card border border-border shadow-sm hover:bg-accent transition-colors"
    >
      <Bars3Icon v-if="!sidebarOpen" class="w-6 h-6" />
      <XMarkIcon v-else class="w-6 h-6" />
    </button>

    <!-- Mobile Overlay -->
    <div
      v-if="sidebarOpen"
      class="lg:hidden fixed inset-0 bg-black/50 z-30"
      @click="sidebarOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed lg:static inset-y-0 left-0 z-40 w-64 bg-card border-r border-border flex flex-col transform transition-transform duration-300 ease-in-out',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo -->
      <div class="p-4 sm:p-6 border-b border-border">
        <div class="flex items-center gap-2 sm:gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
            <ChatBubbleLeftIcon class="w-5 h-5 text-primary" />
          </div>
          <span class="font-semibold text-lg text-foreground">JOBS SMS</span>
        </div>
        <p class="text-xs text-muted-foreground mt-2 ml-10 sm:ml-11 hidden sm:block">Gestion de campagnes</p>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-2 sm:p-4 space-y-0.5 sm:space-y-1 overflow-y-auto">
        <template v-for="item in filteredMenuItems" :key="item.path">
          <router-link
            :to="item.path"
            @click="sidebarOpen = false"
            class="flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
            :class="isActive(item.path) ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'"
          >
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
            <span class="truncate">{{ item.label }}</span>
          </router-link>
        </template>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden w-full">
      <!-- Header -->
      <div class="h-14 sm:h-16 bg-white border-b border-border px-4 sm:px-6 flex items-center justify-between gap-2 sm:gap-4">
        <!-- Spacer for mobile menu button -->
        <div class="w-10 lg:hidden"></div>

        <h1 class="text-lg sm:text-xl lg:text-2xl font-semibold text-foreground truncate flex-1 lg:flex-none">{{ pageTitle }}</h1>

        <div class="flex items-center gap-2 sm:gap-4">
          <!-- Create Campaign Button - Desktop -->
          <button
            v-if="authStore.hasPermission('create_campaigns')"
            @click="goToCreateCampaign"
            class="hidden md:inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-9 sm:h-10 px-3 sm:px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground"
          >
            <PlusIcon class="w-4 h-4" />
            <span class="hidden lg:inline">Créer une Nouvelle Campagne</span>
            <span class="lg:hidden">Nouvelle</span>
          </button>

          <!-- Create Campaign Button - Mobile -->
          <button
            v-if="authStore.hasPermission('create_campaigns')"
            @click="goToCreateCampaign"
            class="md:hidden inline-flex items-center justify-center rounded-md h-9 w-9 bg-primary hover:bg-primary/90 text-primary-foreground"
          >
            <PlusIcon class="w-5 h-5" />
          </button>

          <!-- Notifications -->
          <button class="inline-flex items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground h-9 w-9 sm:h-10 sm:w-10 transition-colors">
            <BellIcon class="w-5 h-5" />
          </button>

          <!-- User Menu -->
          <div class="relative">
            <button
              @click="toggleUserMenu"
              class="flex items-center gap-2 sm:gap-3 p-1.5 sm:p-2 rounded-lg hover:bg-accent transition-colors"
            >
              <div class="relative flex shrink-0 overflow-hidden rounded-full w-8 h-8 bg-primary flex items-center justify-center text-primary-foreground font-semibold text-sm">
                {{ userInitials }}
              </div>
              <div class="flex-1 min-w-0 text-left hidden md:block">
                <p class="text-sm font-medium text-foreground truncate max-w-[120px]">{{ user?.name }}</p>
                <p class="text-xs text-muted-foreground truncate">{{ userRoleLabel }}</p>
              </div>
              <ChevronDownIcon class="w-4 h-4 text-muted-foreground hidden md:block" />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="showUserMenu"
              class="absolute right-0 mt-2 w-48 sm:w-56 rounded-md border bg-card shadow-lg z-50"
            >
              <div class="p-3 border-b border-border">
                <p class="text-sm font-medium text-foreground truncate">{{ user?.name }}</p>
                <p class="text-xs text-muted-foreground truncate">{{ user?.email }}</p>
                <p class="text-xs text-primary mt-1">{{ userRoleLabel }}</p>
              </div>
              <div class="p-1">
                <button
                  @click="goToProfile"
                  class="w-full flex items-center gap-3 px-3 py-2.5 text-sm rounded-md hover:bg-accent transition-colors text-left"
                >
                  <UserCircleIcon class="w-4 h-4" />
                  <span>Profil</span>
                </button>
                <button
                  v-if="authStore.hasPermission('manage_settings')"
                  @click="goToSettings"
                  class="w-full flex items-center gap-3 px-3 py-2.5 text-sm rounded-md hover:bg-accent transition-colors text-left"
                >
                  <Cog6ToothIcon class="w-4 h-4" />
                  <span>Paramètres</span>
                </button>
                <button
                  @click="handleLogout"
                  class="w-full flex items-center gap-3 px-3 py-2.5 text-sm rounded-md hover:bg-destructive/10 text-destructive transition-colors text-left"
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
import { computed, ref, watch } from 'vue'
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
  InboxIcon,
  NoSymbolIcon,
  SignalIcon,
  ClipboardDocumentListIcon,
  CircleStackIcon,
  Bars3Icon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

// Permission constants
const Permission = {
  MANAGE_USERS: 'manage_users',
  MANAGE_SETTINGS: 'manage_settings',
  MANAGE_API_KEYS: 'manage_api_keys',
  MANAGE_WEBHOOKS: 'manage_webhooks',
  MANAGE_SUB_ACCOUNTS: 'manage_sub_accounts',
  VIEW_AUDIT_LOGS: 'view_audit_logs',
  SEND_SMS: 'send_sms',
  VIEW_HISTORY: 'view_history',
  MANAGE_CONTACTS: 'manage_contacts',
  MANAGE_GROUPS: 'manage_groups',
  CREATE_CAMPAIGNS: 'create_campaigns',
  VIEW_ANALYTICS: 'view_analytics',
  MANAGE_TEMPLATES: 'manage_templates',
  EXPORT_DATA: 'export_data',
}

interface MenuItem {
  path: string
  label: string
  icon: any
  permission?: string
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user)
const showUserMenu = ref(false)
const sidebarOpen = ref(false)

// Close sidebar on route change (mobile)
watch(() => route.path, () => {
  sidebarOpen.value = false
})

const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const userRoleLabel = computed(() => {
  return user.value?.role_label || 'Utilisateur'
})

const menuItems: MenuItem[] = [
  { path: '/dashboard', label: 'Tableau de bord', icon: ChartBarIcon, permission: Permission.VIEW_ANALYTICS },
  { path: '/send-sms', label: 'Envoyer SMS', icon: PaperAirplaneIcon, permission: Permission.SEND_SMS },
  { path: '/transactional', label: 'Transactionnel', icon: Cog6ToothIcon, permission: Permission.SEND_SMS },
  { path: '/campaign/create', label: 'Nouvelle campagne', icon: PlusIcon, permission: Permission.CREATE_CAMPAIGNS },
  { path: '/contacts', label: 'Contacts', icon: UsersIcon, permission: Permission.MANAGE_CONTACTS },
  { path: '/database', label: 'Base de donnees', icon: CircleStackIcon, permission: Permission.MANAGE_CONTACTS },
  { path: '/templates', label: 'Modèles', icon: DocumentTextIcon, permission: Permission.MANAGE_TEMPLATES },
  { path: '/calendar', label: 'Calendrier', icon: CalendarIcon, permission: Permission.CREATE_CAMPAIGNS },
  { path: '/campaigns/history', label: 'Historique campagnes', icon: ClockIcon, permission: Permission.VIEW_HISTORY },
  { path: '/messages/history', label: 'Historique messages', icon: InboxIcon, permission: Permission.VIEW_HISTORY },
  { path: '/reports', label: 'Rapports', icon: ChartPieIcon, permission: Permission.VIEW_ANALYTICS },
  { path: '/blacklist', label: 'Liste noire', icon: NoSymbolIcon, permission: Permission.MANAGE_SETTINGS },
  { path: '/accounts', label: 'Comptes', icon: CreditCardIcon, permission: Permission.MANAGE_SUB_ACCOUNTS },
  { path: '/sms-config', label: 'Config. Opérateurs', icon: SignalIcon, permission: Permission.MANAGE_SETTINGS },
  { path: '/api-keys', label: 'Clés API', icon: CodeBracketIcon, permission: Permission.MANAGE_API_KEYS },
  { path: '/webhooks', label: 'Webhooks', icon: LinkIcon, permission: Permission.MANAGE_WEBHOOKS },
  { path: '/audit-logs', label: 'Journal d\'audit', icon: ClipboardDocumentListIcon, permission: Permission.VIEW_AUDIT_LOGS },
  { path: '/settings', label: 'Paramètres', icon: Cog6ToothIcon, permission: Permission.MANAGE_SETTINGS },
]

// Filter menu items based on user permissions
const filteredMenuItems = computed(() => {
  return menuItems.filter(item => {
    // If no permission required, show to all
    if (!item.permission) return true
    // Check if user has permission
    return authStore.hasPermission(item.permission)
  })
})

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

function goToSettings() {
  showUserMenu.value = false
  router.push('/settings')
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
