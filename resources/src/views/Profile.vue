<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-4 sm:mb-8">
        <div class="flex items-center gap-2">
          <UserCircleIcon class="w-6 h-6 sm:w-8 sm:h-8 text-primary" />
          <h1 class="text-2xl sm:text-3xl font-bold">Mon Profil</h1>
        </div>
        <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Gérez vos informations personnelles et votre sécurité</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Left Column - Profile Card & Quick Stats -->
        <div class="space-y-4 sm:space-y-6">
          <!-- Profile Card -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <div class="flex flex-col items-center text-center">
              <div class="relative mb-4">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center text-primary-foreground text-3xl sm:text-4xl font-bold shadow-lg">
                  {{ userInitials }}
                </div>
                <button
                  @click="showAvatarModal = true"
                  class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-background border-2 border-primary flex items-center justify-center hover:bg-accent transition-colors shadow-md"
                >
                  <CameraIcon class="w-4 h-4 text-primary" />
                </button>
              </div>
              <h2 class="text-xl font-bold">{{ profileData.name || 'Utilisateur' }}</h2>
              <p class="text-sm text-muted-foreground">{{ profileData.email }}</p>

              <!-- Status Badge -->
              <div class="mt-4 flex items-center gap-2">
                <span class="px-3 py-1 rounded-full bg-success/10 text-success text-xs font-medium flex items-center gap-1.5">
                  <CheckBadgeIcon class="w-4 h-4" />
                  <span>Compte actif</span>
                </span>
              </div>

              <!-- Member Since -->
              <p class="text-xs text-muted-foreground mt-3">
                Membre depuis {{ memberSince }}
              </p>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <h3 class="font-semibold text-sm mb-4 flex items-center gap-2">
              <ChartBarIcon class="w-5 h-5 text-primary" />
              <span>Activité</span>
            </h3>
            <div class="grid grid-cols-3 gap-2">
              <div class="text-center p-3 rounded-lg bg-muted/50">
                <p class="text-lg sm:text-xl font-bold text-primary">{{ stats.campaigns }}</p>
                <p class="text-xs text-muted-foreground">Campagnes</p>
              </div>
              <div class="text-center p-3 rounded-lg bg-muted/50">
                <p class="text-lg sm:text-xl font-bold text-primary">{{ formatNumber(stats.messagesSent) }}</p>
                <p class="text-xs text-muted-foreground">Messages</p>
              </div>
              <div class="text-center p-3 rounded-lg bg-muted/50">
                <p class="text-lg sm:text-xl font-bold text-primary">{{ stats.contacts }}</p>
                <p class="text-xs text-muted-foreground">Contacts</p>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <h3 class="font-semibold text-sm mb-4">Actions rapides</h3>
            <div class="space-y-2">
              <router-link
                to="/settings"
                class="flex items-center gap-3 p-3 rounded-lg hover:bg-muted/50 transition-colors"
              >
                <Cog6ToothIcon class="w-5 h-5 text-muted-foreground" />
                <span class="text-sm">Paramètres plateforme</span>
                <ChevronRightIcon class="w-4 h-4 text-muted-foreground ml-auto" />
              </router-link>
              <router-link
                to="/api-configuration"
                class="flex items-center gap-3 p-3 rounded-lg hover:bg-muted/50 transition-colors"
              >
                <KeyIcon class="w-5 h-5 text-muted-foreground" />
                <span class="text-sm">Clés API</span>
                <ChevronRightIcon class="w-4 h-4 text-muted-foreground ml-auto" />
              </router-link>
              <router-link
                to="/audit-logs"
                class="flex items-center gap-3 p-3 rounded-lg hover:bg-muted/50 transition-colors"
              >
                <ClipboardDocumentListIcon class="w-5 h-5 text-muted-foreground" />
                <span class="text-sm">Historique d'activité</span>
                <ChevronRightIcon class="w-4 h-4 text-muted-foreground ml-auto" />
              </router-link>
            </div>
          </div>
        </div>

        <!-- Right Column - Forms -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- Personal Information -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
              <IdentificationIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold">Informations personnelles</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium flex items-center gap-2">
                  <UserIcon class="w-4 h-4 text-muted-foreground" />
                  Nom complet
                </label>
                <input
                  v-model="profileData.name"
                  type="text"
                  placeholder="Votre nom complet"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium flex items-center gap-2">
                  <EnvelopeIcon class="w-4 h-4 text-muted-foreground" />
                  Adresse email
                </label>
                <input
                  v-model="profileData.email"
                  type="email"
                  placeholder="votre@email.com"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium flex items-center gap-2">
                  <PhoneIcon class="w-4 h-4 text-muted-foreground" />
                  Téléphone
                </label>
                <input
                  v-model="profileData.phone"
                  type="tel"
                  placeholder="+241 77 00 00 00"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium flex items-center gap-2">
                  <BuildingOfficeIcon class="w-4 h-4 text-muted-foreground" />
                  Entreprise
                </label>
                <input
                  v-model="profileData.company"
                  type="text"
                  placeholder="Nom de votre entreprise"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
                />
              </div>
              <div class="space-y-2 sm:col-span-2">
                <label class="text-sm font-medium flex items-center gap-2">
                  <MapPinIcon class="w-4 h-4 text-muted-foreground" />
                  Adresse
                </label>
                <input
                  v-model="profileData.address"
                  type="text"
                  placeholder="Ville, Pays"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
                />
              </div>
            </div>
          </div>

          <!-- Security -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
              <ShieldCheckIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold">Sécurité du compte</h3>
            </div>

            <!-- Change Password -->
            <div class="mb-6">
              <h4 class="text-sm font-medium mb-4">Changer le mot de passe</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium">Nouveau mot de passe</label>
                  <div class="relative">
                    <input
                      v-model="profileData.password"
                      :type="showPassword ? 'text' : 'password'"
                      placeholder="••••••••"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm focus:ring-2 focus:ring-primary"
                    />
                    <button
                      type="button"
                      @click="showPassword = !showPassword"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                      <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                      <EyeSlashIcon v-else class="w-4 h-4" />
                    </button>
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">Confirmer le mot de passe</label>
                  <input
                    v-model="profileData.password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="••••••••"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
                  />
                </div>
              </div>
              <p class="text-xs text-muted-foreground mt-2">Laisser vide pour conserver le mot de passe actuel</p>
            </div>

            <!-- Password Strength Indicator -->
            <div v-if="profileData.password" class="mb-6 p-4 rounded-lg bg-muted/50">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium">Force du mot de passe</span>
                <span class="text-xs" :class="passwordStrengthClass">{{ passwordStrengthText }}</span>
              </div>
              <div class="h-2 bg-muted rounded-full overflow-hidden">
                <div
                  class="h-full transition-all duration-300"
                  :class="passwordStrengthBarClass"
                  :style="{ width: passwordStrengthPercent + '%' }"
                ></div>
              </div>
              <ul class="mt-3 space-y-1 text-xs text-muted-foreground">
                <li :class="{ 'text-success': profileData.password.length >= 8 }">
                  <CheckIcon v-if="profileData.password.length >= 8" class="w-3 h-3 inline mr-1" />
                  <XMarkIcon v-else class="w-3 h-3 inline mr-1" />
                  Au moins 8 caractères
                </li>
                <li :class="{ 'text-success': /[A-Z]/.test(profileData.password) }">
                  <CheckIcon v-if="/[A-Z]/.test(profileData.password)" class="w-3 h-3 inline mr-1" />
                  <XMarkIcon v-else class="w-3 h-3 inline mr-1" />
                  Une majuscule
                </li>
                <li :class="{ 'text-success': /[0-9]/.test(profileData.password) }">
                  <CheckIcon v-if="/[0-9]/.test(profileData.password)" class="w-3 h-3 inline mr-1" />
                  <XMarkIcon v-else class="w-3 h-3 inline mr-1" />
                  Un chiffre
                </li>
              </ul>
            </div>

            <!-- Last Activity -->
            <div class="p-4 rounded-lg border bg-muted/30">
              <h4 class="text-sm font-medium mb-3">Dernière activité</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Dernière connexion</span>
                  <span>{{ lastLogin }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Adresse IP</span>
                  <span class="font-mono text-xs">{{ lastIp }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Messages -->
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
            <button
              @click="saveProfile"
              :disabled="saving"
              class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6 py-2 disabled:opacity-50"
            >
              <CheckCircleIcon v-if="!saving" class="w-4 h-4" />
              <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ saving ? 'Enregistrement...' : 'Enregistrer les modifications' }}</span>
            </button>
            <button
              @click="resetForm"
              :disabled="saving"
              class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium border border-input bg-background hover:bg-accent h-10 px-4 py-2"
            >
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
  MapPinIcon,
  ShieldCheckIcon,
  KeyIcon,
  ChartBarIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationCircleIcon,
  CameraIcon,
  IdentificationIcon,
  CheckBadgeIcon,
  Cog6ToothIcon,
  ChevronRightIcon,
  ClipboardDocumentListIcon,
  EyeIcon,
  EyeSlashIcon,
  CheckIcon,
  XMarkIcon
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
  address: string
  password: string
  password_confirmation: string
}

const authStore = useAuthStore()
const loading = ref(true)
const saving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const showPassword = ref(false)
const showAvatarModal = ref(false)

const profileData = ref<ProfileData>({
  name: '',
  email: '',
  phone: '',
  company: '',
  address: '',
  password: '',
  password_confirmation: ''
})

const originalData = ref<ProfileData>({ ...profileData.value })

const stats = ref({
  campaigns: 0,
  messagesSent: 0,
  contacts: 0
})

const lastLogin = ref('Aujourd\'hui à 10:30')
const lastIp = ref('192.168.1.xxx')
const memberSince = ref('Janvier 2026')

const userInitials = computed(() => {
  if (!profileData.value.name) return '?'
  return profileData.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// Password strength calculation
const passwordStrength = computed(() => {
  const pwd = profileData.value.password
  if (!pwd) return 0
  let score = 0
  if (pwd.length >= 8) score += 1
  if (pwd.length >= 12) score += 1
  if (/[A-Z]/.test(pwd)) score += 1
  if (/[a-z]/.test(pwd)) score += 1
  if (/[0-9]/.test(pwd)) score += 1
  if (/[^A-Za-z0-9]/.test(pwd)) score += 1
  return score
})

const passwordStrengthPercent = computed(() => {
  return (passwordStrength.value / 6) * 100
})

const passwordStrengthText = computed(() => {
  const s = passwordStrength.value
  if (s <= 2) return 'Faible'
  if (s <= 4) return 'Moyen'
  return 'Fort'
})

const passwordStrengthClass = computed(() => {
  const s = passwordStrength.value
  if (s <= 2) return 'text-destructive'
  if (s <= 4) return 'text-warning'
  return 'text-success'
})

const passwordStrengthBarClass = computed(() => {
  const s = passwordStrength.value
  if (s <= 2) return 'bg-destructive'
  if (s <= 4) return 'bg-warning'
  return 'bg-success'
})

function formatNumber(num: number): string {
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'k'
  }
  return num.toString()
}

async function loadProfile() {
  loading.value = true
  try {
    const response = await apiClient.get('/user/profile')
    const user = response.data.data || response.data

    profileData.value = {
      name: user.name || '',
      email: user.email || '',
      phone: user.phone || '',
      company: user.company || '',
      address: user.address || '',
      password: '',
      password_confirmation: ''
    }

    originalData.value = { ...profileData.value }

    // Format member since
    if (user.created_at) {
      const date = new Date(user.created_at)
      memberSince.value = date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
    }

    // Format last login
    if (user.last_login_at) {
      const date = new Date(user.last_login_at)
      const today = new Date()
      if (date.toDateString() === today.toDateString()) {
        lastLogin.value = `Aujourd'hui à ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`
      } else {
        lastLogin.value = date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
      }
    }

    if (user.last_login_ip) {
      lastIp.value = user.last_login_ip
    }

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
    // Validate password
    if (profileData.value.password) {
      if (profileData.value.password !== profileData.value.password_confirmation) {
        errorMessage.value = 'Les mots de passe ne correspondent pas'
        saving.value = false
        return
      }
      if (profileData.value.password.length < 8) {
        errorMessage.value = 'Le mot de passe doit contenir au moins 8 caractères'
        saving.value = false
        return
      }
    }

    const updateData: any = {
      name: profileData.value.name,
      email: profileData.value.email,
      phone: profileData.value.phone,
      company: profileData.value.company,
      address: profileData.value.address
    }

    if (profileData.value.password) {
      updateData.password = profileData.value.password
      updateData.password_confirmation = profileData.value.password_confirmation
    }

    const response = await apiClient.put('/user/profile', updateData)
    const updatedUser = response.data.data || response.data

    if (authStore.user) {
      authStore.user.name = updatedUser.name
      authStore.user.email = updatedUser.email
    }

    profileData.value.password = ''
    profileData.value.password_confirmation = ''
    originalData.value = { ...profileData.value }

    successMessage.value = 'Profil mis à jour avec succès'

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
