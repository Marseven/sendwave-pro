<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <div class="flex items-center gap-2">
          <Cog6ToothIcon class="w-8 h-8 text-primary" />
          <h1 class="text-3xl font-bold">Paramètres</h1>
        </div>
        <p class="text-muted-foreground mt-2">Configurez votre compte et vos préférences</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="max-w-2xl space-y-6">
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <UserCircleIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Profil</h3>
          </div>
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium flex items-center gap-2">
                <UserIcon class="w-4 h-4 text-muted-foreground" />
                Nom complet
              </label>
              <input
                v-model="formData.name"
                type="text"
                placeholder="Votre nom complet"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium flex items-center gap-2">
                <EnvelopeIcon class="w-4 h-4 text-muted-foreground" />
                Email
              </label>
              <input
                v-model="formData.email"
                type="email"
                placeholder="votre@email.com"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>
          </div>
        </div>

        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <KeyIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Sécurité</h3>
          </div>
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Nouveau mot de passe</label>
              <input
                v-model="formData.password"
                type="password"
                placeholder="Laisser vide pour ne pas changer"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Confirmer le mot de passe</label>
              <input
                v-model="formData.password_confirmation"
                type="password"
                placeholder="Confirmer le nouveau mot de passe"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>
          </div>
        </div>

        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <BellIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Notifications</h3>
          </div>
          <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-muted/50 transition-colors">
              <input v-model="formData.email_notifications" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <EnvelopeIcon class="w-4 h-4 text-muted-foreground" />
                  <div class="font-medium text-sm">Notifications par email</div>
                </div>
                <div class="text-xs text-muted-foreground mt-1">Recevoir les alertes importantes par email</div>
              </div>
            </label>
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-muted/50 transition-colors">
              <input v-model="formData.weekly_reports" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <ChartBarIcon class="w-4 h-4 text-muted-foreground" />
                  <div class="font-medium text-sm">Rapports hebdomadaires</div>
                </div>
                <div class="text-xs text-muted-foreground mt-1">Recevoir les statistiques chaque lundi matin</div>
              </div>
            </label>
          </div>
        </div>

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

        <div class="flex gap-3">
          <button @click="saveSettings" :disabled="saving" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
            <CheckCircleIcon v-if="!saving" class="w-4 h-4" />
            <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</span>
          </button>
          <button @click="resetForm" :disabled="saving" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
            <XCircleIcon class="w-4 h-4" />
            <span>Annuler</span>
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  Cog6ToothIcon,
  UserCircleIcon,
  UserIcon,
  EnvelopeIcon,
  KeyIcon,
  BellIcon,
  ChartBarIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api'

interface SettingsForm {
  name: string
  email: string
  password: string
  password_confirmation: string
  email_notifications: boolean
  weekly_reports: boolean
}

const authStore = useAuthStore()
const loading = ref(true)
const saving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const formData = ref<SettingsForm>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  email_notifications: true,
  weekly_reports: true
})

const originalData = ref<SettingsForm>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  email_notifications: true,
  weekly_reports: true
})

async function loadSettings() {
  try {
    loading.value = true
    const response = await apiClient.get('/user/profile')
    const user = response.data.data || response.data

    formData.value = {
      name: user.name || '',
      email: user.email || '',
      password: '',
      password_confirmation: '',
      email_notifications: user.email_notifications ?? true,
      weekly_reports: user.weekly_reports ?? true
    }

    originalData.value = { ...formData.value }
  } catch (error) {
    console.error('Error loading settings:', error)
    // Fallback to auth store data
    if (authStore.user) {
      formData.value.name = authStore.user.name || ''
      formData.value.email = authStore.user.email || ''
      originalData.value = { ...formData.value }
    }
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  try {
    saving.value = true
    successMessage.value = ''
    errorMessage.value = ''

    // Validate password confirmation
    if (formData.value.password && formData.value.password !== formData.value.password_confirmation) {
      errorMessage.value = 'Les mots de passe ne correspondent pas'
      return
    }

    const updateData: any = {
      name: formData.value.name,
      email: formData.value.email,
      email_notifications: formData.value.email_notifications,
      weekly_reports: formData.value.weekly_reports
    }

    // Only include password if it's set
    if (formData.value.password) {
      updateData.password = formData.value.password
      updateData.password_confirmation = formData.value.password_confirmation
    }

    const response = await apiClient.put('/user/profile', updateData)
    const updatedUser = response.data.data || response.data

    // Update auth store
    if (authStore.user) {
      authStore.user.name = updatedUser.name
      authStore.user.email = updatedUser.email
    }

    // Clear password fields
    formData.value.password = ''
    formData.value.password_confirmation = ''

    // Update original data
    originalData.value = { ...formData.value }

    successMessage.value = 'Paramètres enregistrés avec succès'

    // Clear success message after 3 seconds
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Error saving settings:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'enregistrement des paramètres'
  } finally {
    saving.value = false
  }
}

function resetForm() {
  formData.value = { ...originalData.value }
  successMessage.value = ''
  errorMessage.value = ''
}

onMounted(() => {
  loadSettings()
})
</script>
