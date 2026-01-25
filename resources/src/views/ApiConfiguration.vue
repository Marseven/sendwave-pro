<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-4 sm:mb-8">
        <div class="flex items-center gap-2">
          <KeyIcon class="w-6 h-6 sm:w-8 sm:h-8 text-primary" />
          <h1 class="text-xl sm:text-3xl font-bold">Configuration des API SMS</h1>
        </div>
        <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Configurez les API des opérateurs Airtel et Moov Gabon</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="max-w-5xl mx-auto space-y-6">
        <!-- Airtel Configuration -->
        <div class="rounded-lg border bg-card shadow-sm overflow-hidden">
          <div class="p-6">
            <div class="flex items-start justify-between mb-6">
              <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-lg flex items-center justify-center text-2xl font-bold"
                  :class="airtelConfig.is_active ? 'bg-red-100 text-red-600' : 'bg-muted text-muted-foreground'">
                  AI
                </div>
                <div>
                  <div class="flex items-center gap-3">
                    <h3 class="text-xl font-bold">Airtel Gabon</h3>
                    <span v-if="airtelConfig.is_active" class="px-2 py-1 rounded-full bg-success/10 text-success text-xs font-medium flex items-center gap-1">
                      <CheckCircleIcon class="w-3 h-3" />
                      <span>Actif</span>
                    </span>
                    <span v-else class="px-2 py-1 rounded-full bg-muted text-muted-foreground text-xs font-medium flex items-center gap-1">
                      <XCircleIcon class="w-3 h-3" />
                      <span>Inactif</span>
                    </span>
                  </div>
                  <p class="text-sm text-muted-foreground mt-1">API SMS Airtel - Préfixes: 77, 74, 76</p>
                  <div class="flex items-center gap-4 mt-3">
                    <div class="flex items-center gap-1.5 text-xs">
                      <GlobeAltIcon class="w-4 h-4 text-muted-foreground" />
                      <span class="text-muted-foreground">messaging.airtel.ga</span>
                    </div>
                  </div>
                </div>
              </div>
              <button
                @click="toggleExpand('airtel')"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:bg-accent hover:text-accent-foreground h-9 w-9"
              >
                <ChevronDownIcon class="w-5 h-5 transition-transform" :class="expandedProviders.includes('airtel') ? 'rotate-180' : ''" />
              </button>
            </div>

            <!-- Expanded Configuration -->
            <div v-if="expandedProviders.includes('airtel')" class="pt-6 border-t space-y-6">
              <!-- API Credentials -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <UserIcon class="w-4 h-4 text-muted-foreground" />
                    Username *
                  </label>
                  <input
                    v-model="airtelConfig.username"
                    type="text"
                    placeholder="Entrez le username"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <KeyIcon class="w-4 h-4 text-muted-foreground" />
                    Password *
                  </label>
                  <div class="relative">
                    <input
                      v-model="airtelConfig.password"
                      :type="showPassword.airtel ? 'text' : 'password'"
                      placeholder="Entrez le password"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <button
                      @click="togglePasswordVisibility('airtel')"
                      type="button"
                      class="absolute right-2 top-1/2 -translate-y-1/2 hover:bg-accent rounded p-1"
                    >
                      <EyeIcon v-if="!showPassword.airtel" class="w-4 h-4 text-muted-foreground" />
                      <EyeSlashIcon v-else class="w-4 h-4 text-muted-foreground" />
                    </button>
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <IdentificationIcon class="w-4 h-4 text-muted-foreground" />
                    Origin Address / Sender ID *
                  </label>
                  <input
                    v-model="airtelConfig.origin_addr"
                    type="text"
                    placeholder="Ex: JOBSSMS"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <CurrencyDollarIcon class="w-4 h-4 text-muted-foreground" />
                    Coût par SMS (FCFA)
                  </label>
                  <input
                    v-model.number="airtelConfig.cost_per_sms"
                    type="number"
                    step="1"
                    min="0"
                    placeholder="20"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>
              </div>

              <!-- API URL -->
              <div class="space-y-2">
                <label class="text-sm font-medium flex items-center gap-2">
                  <GlobeAltIcon class="w-4 h-4 text-muted-foreground" />
                  URL de l'API
                </label>
                <input
                  v-model="airtelConfig.api_url"
                  type="text"
                  readonly
                  class="flex h-10 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground"
                />
              </div>

              <!-- Actions -->
              <div class="flex gap-3">
                <button
                  @click="toggleActive('airtel')"
                  :disabled="saving.airtel"
                  class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
                >
                  <BoltIcon class="w-4 h-4" />
                  <span>{{ airtelConfig.is_active ? 'Désactiver' : 'Activer' }}</span>
                </button>

                <button
                  @click="testConnection('airtel')"
                  :disabled="!airtelConfig.username || !airtelConfig.password || testing.airtel"
                  class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
                >
                  <SignalIcon v-if="!testing.airtel" class="w-4 h-4" />
                  <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>
                  <span>{{ testing.airtel ? 'Test en cours...' : 'Tester' }}</span>
                </button>

                <button
                  @click="saveConfig('airtel')"
                  :disabled="!airtelConfig.username || !airtelConfig.password || saving.airtel"
                  class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                >
                  <CheckCircleIcon v-if="!saving.airtel" class="w-4 h-4" />
                  <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                  <span>{{ saving.airtel ? 'Enregistrement...' : 'Enregistrer' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Moov Configuration -->
        <div class="rounded-lg border bg-card shadow-sm overflow-hidden opacity-60">
          <div class="p-6">
            <div class="flex items-start justify-between mb-6">
              <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-lg flex items-center justify-center text-2xl font-bold bg-muted text-muted-foreground">
                  MV
                </div>
                <div>
                  <div class="flex items-center gap-3">
                    <h3 class="text-xl font-bold">Moov Gabon</h3>
                    <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium">
                      Bientôt disponible
                    </span>
                  </div>
                  <p class="text-sm text-muted-foreground mt-1">API SMS Moov - Préfixes: 60, 62, 65, 66</p>
                  <p class="text-xs text-muted-foreground mt-2">L'API Moov sera configurée dès qu'elle sera disponible</p>
                </div>
              </div>
            </div>
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
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  KeyIcon,
  CheckCircleIcon,
  XCircleIcon,
  ChevronDownIcon,
  GlobeAltIcon,
  SignalIcon,
  IdentificationIcon,
  CurrencyDollarIcon,
  BoltIcon,
  EyeIcon,
  EyeSlashIcon,
  ExclamationCircleIcon,
  UserIcon
} from '@heroicons/vue/24/outline'
import apiClient from '@/services/api'

interface SmsConfig {
  provider: string
  api_url: string
  username: string
  password: string
  origin_addr: string
  cost_per_sms: number
  is_active: boolean
}

const loading = ref(true)
const expandedProviders = ref<string[]>(['airtel'])
const showPassword = reactive<Record<string, boolean>>({ airtel: false, moov: false })
const testing = reactive<Record<string, boolean>>({ airtel: false, moov: false })
const saving = reactive<Record<string, boolean>>({ airtel: false, moov: false })
const successMessage = ref('')
const errorMessage = ref('')

const airtelConfig = ref<SmsConfig>({
  provider: 'airtel',
  api_url: 'https://messaging.airtel.ga:9002/smshttp/qs/',
  username: '',
  password: '',
  origin_addr: '',
  cost_per_sms: 20,
  is_active: false
})

const moovConfig = ref<SmsConfig>({
  provider: 'moov',
  api_url: '',
  username: '',
  password: '',
  origin_addr: '',
  cost_per_sms: 20,
  is_active: false
})

function toggleExpand(provider: string) {
  const index = expandedProviders.value.indexOf(provider)
  if (index > -1) {
    expandedProviders.value.splice(index, 1)
  } else {
    expandedProviders.value.push(provider)
  }
}

function togglePasswordVisibility(provider: string) {
  showPassword[provider] = !showPassword[provider]
}

async function loadConfigs() {
  loading.value = true
  try {
    const response = await apiClient.get('/sms-configs')
    const configs = response.data.data || response.data

    configs.forEach((config: SmsConfig) => {
      if (config.provider === 'airtel') {
        airtelConfig.value = { ...airtelConfig.value, ...config }
      } else if (config.provider === 'moov') {
        moovConfig.value = { ...moovConfig.value, ...config }
      }
    })
  } catch (error) {
    console.error('Error loading configs:', error)
  } finally {
    loading.value = false
  }
}

async function saveConfig(provider: string) {
  saving[provider] = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const config = provider === 'airtel' ? airtelConfig.value : moovConfig.value

    await apiClient.put(`/sms-configs/${provider}`, {
      api_url: config.api_url,
      username: config.username,
      password: config.password,
      origin_addr: config.origin_addr,
      cost_per_sms: config.cost_per_sms,
      is_active: config.is_active
    })

    successMessage.value = `Configuration ${provider.toUpperCase()} enregistrée avec succès !`

    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Save config error:', error)
    errorMessage.value = error.response?.data?.message || `Erreur lors de l'enregistrement`
  } finally {
    saving[provider] = false
  }
}

async function toggleActive(provider: string) {
  saving[provider] = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const response = await apiClient.post(`/sms-configs/${provider}/toggle`)
    const updated = response.data.data

    if (provider === 'airtel') {
      airtelConfig.value.is_active = updated.is_active
    }

    successMessage.value = response.data.message

    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Toggle error:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors du changement de statut'
  } finally {
    saving[provider] = false
  }
}

async function testConnection(provider: string) {
  testing[provider] = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const response = await apiClient.post(`/sms-configs/${provider}/test`, {
      phone_number: '+241 77 75 07 37', // Numéro de test
      message: `Test API ${provider.toUpperCase()} - ${new Date().toLocaleString()}`
    })

    successMessage.value = `✓ Test réussi avec ${provider.toUpperCase()} !`

    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Test error:', error)
    errorMessage.value = error.response?.data?.message || `Échec du test ${provider.toUpperCase()}`
  } finally {
    testing[provider] = false
  }
}

onMounted(() => {
  loadConfigs()
})
</script>
