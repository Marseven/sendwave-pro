<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <div class="flex items-center gap-2">
          <KeyIcon class="w-8 h-8 text-primary" />
          <h1 class="text-3xl font-bold">Configuration des API SMS</h1>
        </div>
        <p class="text-muted-foreground mt-2">Configurez vos clés API pour les différents providers SMS</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="max-w-5xl mx-auto space-y-6">
        <!-- Provider Cards -->
        <div v-for="provider in providers" :key="provider.code" class="rounded-lg border bg-card shadow-sm overflow-hidden">
          <div class="p-6">
            <div class="flex items-start justify-between mb-6">
              <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-lg flex items-center justify-center text-2xl font-bold"
                  :class="provider.is_active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'">
                  {{ provider.code.substring(0, 2).toUpperCase() }}
                </div>
                <div>
                  <div class="flex items-center gap-3">
                    <h3 class="text-xl font-bold">{{ provider.name }}</h3>
                    <span v-if="provider.is_active" class="px-2 py-1 rounded-full bg-success/10 text-success text-xs font-medium flex items-center gap-1">
                      <CheckCircleIcon class="w-3 h-3" />
                      <span>Actif</span>
                    </span>
                    <span v-else class="px-2 py-1 rounded-full bg-muted text-muted-foreground text-xs font-medium flex items-center gap-1">
                      <XCircleIcon class="w-3 h-3" />
                      <span>Inactif</span>
                    </span>
                  </div>
                  <p class="text-sm text-muted-foreground mt-1">{{ provider.description }}</p>
                  <div class="flex items-center gap-4 mt-3">
                    <div class="flex items-center gap-1.5 text-xs">
                      <GlobeAltIcon class="w-4 h-4 text-muted-foreground" />
                      <span class="text-muted-foreground">{{ provider.website }}</span>
                    </div>
                    <div v-if="provider.status === 'connected'" class="flex items-center gap-1.5 text-xs text-success">
                      <SignalIcon class="w-4 h-4" />
                      <span>Connecté</span>
                    </div>
                    <div v-else-if="provider.status === 'error'" class="flex items-center gap-1.5 text-xs text-destructive">
                      <ExclamationTriangleIcon class="w-4 h-4" />
                      <span>Erreur de connexion</span>
                    </div>
                    <div v-else class="flex items-center gap-1.5 text-xs text-muted-foreground">
                      <SignalSlashIcon class="w-4 h-4" />
                      <span>Non configuré</span>
                    </div>
                  </div>
                </div>
              </div>
              <button
                @click="toggleExpand(provider.code)"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:bg-accent hover:text-accent-foreground h-9 w-9"
              >
                <ChevronDownIcon class="w-5 h-5 transition-transform" :class="expandedProviders.includes(provider.code) ? 'rotate-180' : ''" />
              </button>
            </div>

            <!-- Expanded Configuration -->
            <div v-if="expandedProviders.includes(provider.code)" class="pt-6 border-t space-y-6">
              <!-- API Credentials -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <KeyIcon class="w-4 h-4 text-muted-foreground" />
                    Clé API *
                  </label>
                  <div class="relative">
                    <input
                      v-model="provider.api_key"
                      :type="showApiKey[provider.code] ? 'text' : 'password'"
                      placeholder="Entrez votre clé API"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <button
                      @click="toggleApiKeyVisibility(provider.code)"
                      type="button"
                      class="absolute right-2 top-1/2 -translate-y-1/2 hover:bg-accent rounded p-1"
                    >
                      <EyeIcon v-if="!showApiKey[provider.code]" class="w-4 h-4 text-muted-foreground" />
                      <EyeSlashIcon v-else class="w-4 h-4 text-muted-foreground" />
                    </button>
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <IdentificationIcon class="w-4 h-4 text-muted-foreground" />
                    Sender ID / From
                  </label>
                  <input
                    v-model="provider.sender_id"
                    type="text"
                    placeholder="Ex: MyCompany, +1234567890"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>

                <div v-if="provider.code === 'msg91'" class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <HashtagIcon class="w-4 h-4 text-muted-foreground" />
                    Route (MSG91)
                  </label>
                  <select
                    v-model="provider.config.route"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  >
                    <option value="4">Transactional (Route 4)</option>
                    <option value="1">Promotional (Route 1)</option>
                  </select>
                </div>

                <div v-if="provider.code === 'whapi'" class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <DevicePhoneMobileIcon class="w-4 h-4 text-muted-foreground" />
                    Channel ID (WHAPI)
                  </label>
                  <input
                    v-model="provider.config.channel_id"
                    type="text"
                    placeholder="Votre channel ID"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>
              </div>

              <!-- Priority & Cost -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <ArrowUpCircleIcon class="w-4 h-4 text-muted-foreground" />
                    Priorité
                  </label>
                  <select
                    v-model="provider.priority"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  >
                    <option :value="1">1 - Haute priorité</option>
                    <option :value="2">2 - Moyenne</option>
                    <option :value="3">3 - Basse priorité</option>
                  </select>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <CurrencyDollarIcon class="w-4 h-4 text-muted-foreground" />
                    Coût par SMS (XAF)
                  </label>
                  <input
                    v-model.number="provider.cost_per_sms"
                    type="number"
                    step="1"
                    min="0"
                    placeholder="30"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium flex items-center gap-2">
                    <BoltIcon class="w-4 h-4 text-muted-foreground" />
                    Statut
                  </label>
                  <div class="flex items-center gap-3 h-10">
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input
                        v-model="provider.is_active"
                        type="checkbox"
                        class="sr-only peer"
                      />
                      <div class="w-11 h-6 bg-muted peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-success"></div>
                    </label>
                    <span class="text-sm font-medium">{{ provider.is_active ? 'Activé' : 'Désactivé' }}</span>
                  </div>
                </div>
              </div>

              <!-- API Documentation -->
              <div class="p-4 bg-muted/50 rounded-lg">
                <div class="flex items-start gap-3">
                  <InformationCircleIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                  <div class="flex-1 text-sm">
                    <p class="font-medium mb-2">Documentation API</p>
                    <ul class="space-y-1 text-muted-foreground">
                      <li v-if="provider.code === 'msg91'">
                        • Route 4 (Transactional) : Pour les OTP, alertes, notifications importantes<br>
                        • Route 1 (Promotional) : Pour les campagnes marketing
                      </li>
                      <li v-if="provider.code === 'smsala'">
                        • API REST simple avec authentification par clé API<br>
                        • Sender ID alphanumérique (3-11 caractères) ou numérique
                      </li>
                      <li v-if="provider.code === 'whapi'">
                        • WhatsApp Business API via WHAPI.cloud<br>
                        • Nécessite un Bearer token et un Channel ID valide<br>
                        • Documentation: <a href="https://whapi.cloud/docs" target="_blank" class="text-primary hover:underline">whapi.cloud/docs</a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-3">
                <button
                  @click="testConnection(provider)"
                  :disabled="!provider.api_key || testing[provider.code]"
                  class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
                >
                  <SignalIcon v-if="!testing[provider.code]" class="w-4 h-4" />
                  <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>
                  <span>{{ testing[provider.code] ? 'Test en cours...' : 'Tester la connexion' }}</span>
                </button>

                <button
                  @click="saveProvider(provider)"
                  :disabled="!provider.api_key || saving[provider.code]"
                  class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                >
                  <CheckCircleIcon v-if="!saving[provider.code]" class="w-4 h-4" />
                  <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                  <span>{{ saving[provider.code] ? 'Enregistrement...' : 'Enregistrer' }}</span>
                </button>
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
  SignalSlashIcon,
  ExclamationTriangleIcon,
  IdentificationIcon,
  HashtagIcon,
  DevicePhoneMobileIcon,
  ArrowUpCircleIcon,
  CurrencyDollarIcon,
  BoltIcon,
  InformationCircleIcon,
  EyeIcon,
  EyeSlashIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'
import apiClient from '@/services/api'

interface Provider {
  id?: number
  code: string
  name: string
  description: string
  website: string
  api_key: string
  sender_id: string
  priority: number
  cost_per_sms: number
  is_active: boolean
  status: 'connected' | 'disconnected' | 'error'
  config: {
    route?: string
    channel_id?: string
  }
}

const loading = ref(true)
const expandedProviders = ref<string[]>([])
const showApiKey = reactive<Record<string, boolean>>({})
const testing = reactive<Record<string, boolean>>({})
const saving = reactive<Record<string, boolean>>({})
const successMessage = ref('')
const errorMessage = ref('')

const providers = ref<Provider[]>([
  {
    code: 'msg91',
    name: 'MSG91',
    description: 'Service SMS indien avec routes transactionnelles et promotionnelles',
    website: 'msg91.com',
    api_key: '',
    sender_id: '',
    priority: 1,
    cost_per_sms: 30,
    is_active: false,
    status: 'disconnected',
    config: {
      route: '4'
    }
  },
  {
    code: 'smsala',
    name: 'SMSALA',
    description: 'Service SMS africain avec couverture continentale',
    website: 'smsala.com',
    api_key: '',
    sender_id: '',
    priority: 2,
    cost_per_sms: 27,
    is_active: false,
    status: 'disconnected',
    config: {}
  },
  {
    code: 'whapi',
    name: 'WHAPI (WhatsApp)',
    description: 'WhatsApp Business API via WHAPI.cloud pour messages instantanés',
    website: 'whapi.cloud',
    api_key: '',
    sender_id: '',
    priority: 3,
    cost_per_sms: 20,
    is_active: false,
    status: 'disconnected',
    config: {
      channel_id: ''
    }
  }
])

function toggleExpand(code: string) {
  const index = expandedProviders.value.indexOf(code)
  if (index > -1) {
    expandedProviders.value.splice(index, 1)
  } else {
    expandedProviders.value.push(code)
  }
}

function toggleApiKeyVisibility(code: string) {
  showApiKey[code] = !showApiKey[code]
}

async function loadProviders() {
  loading.value = true
  try {
    const response = await apiClient.get('/sms-providers')
    const savedProviders = response.data.data || response.data

    // Merge saved data with default providers
    providers.value = providers.value.map(defaultProvider => {
      const saved = savedProviders.find((p: any) => p.code === defaultProvider.code)
      if (saved) {
        return {
          ...defaultProvider,
          ...saved,
          config: { ...defaultProvider.config, ...saved.config }
        }
      }
      return defaultProvider
    })

    // Expand active providers by default
    expandedProviders.value = providers.value
      .filter(p => p.is_active)
      .map(p => p.code)
  } catch (error) {
    console.error('Error loading providers:', error)
  } finally {
    loading.value = false
  }
}

async function testConnection(provider: Provider) {
  testing[provider.code] = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const response = await apiClient.post(`/sms-providers/${provider.code}/test`, {
      api_key: provider.api_key,
      sender_id: provider.sender_id,
      config: provider.config
    })

    provider.status = 'connected'
    successMessage.value = `✓ Connexion réussie avec ${provider.name} !`

    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Test connection error:', error)
    provider.status = 'error'
    errorMessage.value = error.response?.data?.message || `Échec de la connexion avec ${provider.name}`
  } finally {
    testing[provider.code] = false
  }
}

async function saveProvider(provider: Provider) {
  saving[provider.code] = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const data = {
      code: provider.code,
      name: provider.name,
      api_key: provider.api_key,
      sender_id: provider.sender_id,
      priority: provider.priority,
      cost_per_sms: provider.cost_per_sms,
      is_active: provider.is_active,
      config: provider.config
    }

    const response = await apiClient.post('/sms-providers', data)
    const savedProvider = response.data.data || response.data

    if (savedProvider.id) {
      provider.id = savedProvider.id
    }

    successMessage.value = `Configuration de ${provider.name} enregistrée avec succès !`

    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Save provider error:', error)
    errorMessage.value = error.response?.data?.message || `Erreur lors de l'enregistrement de ${provider.name}`
  } finally {
    saving[provider.code] = false
  }
}

onMounted(() => {
  loadProviders()
})
</script>
