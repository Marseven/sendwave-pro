<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-4 sm:mb-8">
        <div class="flex items-center gap-2">
          <UserCircleIcon class="w-6 h-6 sm:w-8 sm:h-8 text-primary" />
          <h1 class="text-2xl sm:text-3xl font-bold">Mon Profil</h1>
        </div>
        <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Gérez vos informations personnelles</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Left Column - Profile Info & Stats -->
        <div class="space-y-4 sm:space-y-6">
          <!-- Profile Card -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <div class="flex flex-col items-center text-center">
              <div class="relative mb-3 sm:mb-4">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-2xl sm:text-3xl font-bold">
                  {{ userInitials }}
                </div>
                <button class="absolute bottom-0 right-0 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-accent border-2 border-background flex items-center justify-center hover:bg-accent/80 transition-colors">
                  <CameraIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </button>
              </div>
              <h2 class="text-lg sm:text-xl font-bold">{{ profileData.name }}</h2>
              <p class="text-xs sm:text-sm text-muted-foreground">{{ profileData.email }}</p>
              <div class="mt-3 sm:mt-4 flex items-center gap-2">
                <span class="px-2 sm:px-3 py-1 rounded-full bg-success/10 text-success text-xs font-medium flex items-center gap-1">
                  <CheckBadgeIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                  <span>Actif</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Stats Card -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <h3 class="font-semibold text-sm sm:text-base mb-3 sm:mb-4 flex items-center gap-2">
              <ChartBarIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
              <span>Statistiques</span>
            </h3>
            <div class="space-y-2 sm:space-y-3">
              <div class="flex items-center justify-between p-2 sm:p-3 rounded-lg bg-muted/50">
                <div class="flex items-center gap-2">
                  <PaperAirplaneIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-muted-foreground" />
                  <span class="text-xs sm:text-sm">Campagnes</span>
                </div>
                <span class="font-bold text-sm sm:text-base">{{ stats.campaigns }}</span>
              </div>
              <div class="flex items-center justify-between p-2 sm:p-3 rounded-lg bg-muted/50">
                <div class="flex items-center gap-2">
                  <ChatBubbleLeftIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-muted-foreground" />
                  <span class="text-xs sm:text-sm">Messages</span>
                </div>
                <span class="font-bold text-sm sm:text-base">{{ stats.messagesSent }}</span>
              </div>
              <div class="flex items-center justify-between p-2 sm:p-3 rounded-lg bg-muted/50">
                <div class="flex items-center gap-2">
                  <UsersIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-muted-foreground" />
                  <span class="text-xs sm:text-sm">Contacts</span>
                </div>
                <span class="font-bold text-sm sm:text-base">{{ stats.contacts }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Forms -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- Personal Information -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <div class="flex items-center gap-2 mb-3 sm:mb-4">
              <IdentificationIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
              <h3 class="font-semibold text-sm sm:text-base">Informations personnelles</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              <div class="space-y-1.5 sm:space-y-2">
                <label class="text-xs sm:text-sm font-medium flex items-center gap-2">
                  <UserIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-muted-foreground" />
                  Nom complet
                </label>
                <input
                  v-model="profileData.name"
                  type="text"
                  placeholder="Votre nom complet"
                  class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-1.5 sm:space-y-2">
                <label class="text-xs sm:text-sm font-medium flex items-center gap-2">
                  <EnvelopeIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-muted-foreground" />
                  Email
                </label>
                <input
                  v-model="profileData.email"
                  type="email"
                  placeholder="votre@email.com"
                  class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-1.5 sm:space-y-2">
                <label class="text-xs sm:text-sm font-medium flex items-center gap-2">
                  <PhoneIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-muted-foreground" />
                  Téléphone
                </label>
                <input
                  v-model="profileData.phone"
                  type="tel"
                  placeholder="+33612345678"
                  class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-1.5 sm:space-y-2">
                <label class="text-xs sm:text-sm font-medium flex items-center gap-2">
                  <BuildingOfficeIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-muted-foreground" />
                  Entreprise
                </label>
                <input
                  v-model="profileData.company"
                  type="text"
                  placeholder="Nom de l'entreprise"
                  class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
            </div>
          </div>

          <!-- Security -->
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <KeyIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold">Sécurité</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium">Nouveau mot de passe</label>
                <input
                  v-model="profileData.password"
                  type="password"
                  placeholder="••••••••"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Confirmer le mot de passe</label>
                <input
                  v-model="profileData.password_confirmation"
                  type="password"
                  placeholder="••••••••"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
            </div>
            <p class="text-xs text-muted-foreground mt-2">Laisser vide pour ne pas modifier le mot de passe</p>
          </div>

          <!-- Preferences -->
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <BellIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold">Préférences de notifications</h3>
            </div>
            <div class="space-y-3">
              <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-muted/50 transition-colors">
                <input v-model="profileData.email_notifications" type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <EnvelopeIcon class="w-4 h-4 text-muted-foreground" />
                    <div class="font-medium text-sm">Notifications par email</div>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">Recevoir les alertes importantes par email</div>
                </div>
              </label>
              <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-muted/50 transition-colors">
                <input v-model="profileData.weekly_reports" type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <ChartBarIcon class="w-4 h-4 text-muted-foreground" />
                    <div class="font-medium text-sm">Rapports hebdomadaires</div>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">Recevoir les statistiques chaque lundi matin</div>
                </div>
              </label>
              <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-muted/50 transition-colors">
                <input v-model="profileData.campaign_alerts" type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <BellAlertIcon class="w-4 h-4 text-muted-foreground" />
                    <div class="font-medium text-sm">Alertes campagnes</div>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">Notifications lors de l'envoi ou de la fin d'une campagne</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Success/Error Messages -->
          <div v-if="successMessage" class="rounded-lg bg-success/10 border border-success/20 p-4">
            <div class="flex items-center gap-2 text-success">
              <CheckCircleIcon class="w-5 h-5" />
              <p class="font-medium">{{ successMessage }}</p>
            </div>
          </div>

          <div v-if="errorMessage" class="rounded-lg bg-destructive/10 border border-destructive/20 p-4">
            <div class="flex items-center gap-2 text-destructive">
              <ExclamationCircleIcon class="w-5 h-5" />
              <p class="font-medium">{{ errorMessage }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <button @click="saveProfile" :disabled="saving" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-4 sm:px-6 py-2">
              <CheckCircleIcon v-if="!saving" class="w-4 h-4" />
              <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span class="hidden sm:inline">{{ saving ? 'Enregistrement...' : 'Enregistrer les modifications' }}</span>
              <span class="sm:hidden">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</span>
            </button>
            <button @click="resetForm" :disabled="saving" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 sm:h-10 px-3 sm:px-4 py-2">
              <XCircleIcon class="w-4 h-4" />
              <span>Annuler</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  UserCircleIcon,
  UserIcon,
  EnvelopeIcon,
  PhoneIcon,
  BuildingOfficeIcon,
  KeyIcon,
  BellIcon,
  ChartBarIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationCircleIcon,
  CameraIcon,
  IdentificationIcon,
  PaperAirplaneIcon,
  ChatBubbleLeftIcon,
  UsersIcon,
  CheckBadgeIcon,
  BellAlertIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api'
import { campaignService } from '@/services/campaignService'
import { contactService } from '@/services/contactService'

interface ProfileData {
  name: string
  email: string
  phone: string
  company: string
  password: string
  password_confirmation: string
  email_notifications: boolean
  weekly_reports: boolean
  campaign_alerts: boolean
}

const authStore = useAuthStore()
const loading = ref(true)
const saving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const profileData = ref<ProfileData>({
  name: '',
  email: '',
  phone: '',
  company: '',
  password: '',
  password_confirmation: '',
  email_notifications: true,
  weekly_reports: true,
  campaign_alerts: true
})

const originalData = ref<ProfileData>({
  name: '',
  email: '',
  phone: '',
  company: '',
  password: '',
  password_confirmation: '',
  email_notifications: true,
  weekly_reports: true,
  campaign_alerts: true
})

const stats = ref({
  campaigns: 0,
  messagesSent: 0,
  contacts: 0
})

const userInitials = computed(() => {
  if (!profileData.value.name) return '?'
  return profileData.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

async function loadProfile() {
  loading.value = true
  try {
    // Load profile data
    const response = await apiClient.get('/user/profile')
    const user = response.data.data || response.data

    profileData.value = {
      name: user.name || '',
      email: user.email || '',
      phone: user.phone || '',
      company: user.company || '',
      password: '',
      password_confirmation: '',
      email_notifications: user.email_notifications ?? true,
      weekly_reports: user.weekly_reports ?? true,
      campaign_alerts: user.campaign_alerts ?? true
    }

    originalData.value = { ...profileData.value }

    // Load stats
    const [campaigns, contacts] = await Promise.all([
      campaignService.getAll(),
      contactService.getAll()
    ])

    stats.value.campaigns = campaigns.length
    stats.value.contacts = contacts.length
    stats.value.messagesSent = campaigns.reduce((sum: number, c: any) => sum + (c.messages_sent || c.sent || 0), 0)
  } catch (error) {
    console.error('Error loading profile:', error)
    // Fallback to auth store data
    if (authStore.user) {
      profileData.value.name = authStore.user.name || ''
      profileData.value.email = authStore.user.email || ''
      originalData.value = { ...profileData.value }
    }
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  saving.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    // Validate password confirmation
    if (profileData.value.password && profileData.value.password !== profileData.value.password_confirmation) {
      errorMessage.value = 'Les mots de passe ne correspondent pas'
      return
    }

    const updateData: any = {
      name: profileData.value.name,
      email: profileData.value.email,
      phone: profileData.value.phone,
      company: profileData.value.company,
      email_notifications: profileData.value.email_notifications,
      weekly_reports: profileData.value.weekly_reports,
      campaign_alerts: profileData.value.campaign_alerts
    }

    // Only include password if it's set
    if (profileData.value.password) {
      updateData.password = profileData.value.password
      updateData.password_confirmation = profileData.value.password_confirmation
    }

    const response = await apiClient.put('/user/profile', updateData)
    const updatedUser = response.data.data || response.data

    // Update auth store
    if (authStore.user) {
      authStore.user.name = updatedUser.name
      authStore.user.email = updatedUser.email
    }

    // Clear password fields
    profileData.value.password = ''
    profileData.value.password_confirmation = ''

    // Update original data
    originalData.value = { ...profileData.value }

    successMessage.value = 'Profil mis à jour avec succès'

    // Clear success message after 3 seconds
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Error saving profile:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors de la mise à jour du profil'
  } finally {
    saving.value = false
  }
}

function resetForm() {
  profileData.value = { ...originalData.value }
  successMessage.value = ''
  errorMessage.value = ''
}

onMounted(() => {
  loadProfile()
})
</script>
