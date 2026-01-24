<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold">Configuration SMS</h1>
        <p class="text-muted-foreground mt-2">Configurez les opérateurs SMS (Airtel et Moov Gabon)</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Airtel Config -->
        <div class="rounded-lg border bg-card shadow-sm">
          <div class="p-6 border-b">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                  <SignalIcon class="w-6 h-6 text-red-600" />
                </div>
                <div>
                  <h2 class="text-xl font-bold">Airtel Gabon</h2>
                  <p class="text-sm text-muted-foreground">Préfixes: 77, 74, 76</p>
                </div>
              </div>
              <button
                @click="toggleConfig('airtel')"
                :disabled="togglingAirtel"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                :class="configs.airtel?.is_active ? 'bg-success' : 'bg-muted'"
              >
                <span
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                  :class="configs.airtel?.is_active ? 'translate-x-5' : 'translate-x-0'"
                />
              </button>
            </div>
          </div>

          <form @submit.prevent="saveConfig('airtel')" class="p-6 space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">URL API</label>
              <input
                v-model="airtelForm.api_url"
                type="text"
                placeholder="https://messaging.airtel.ga:9002/smshttp/qs/"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium">Nom d'utilisateur</label>
                <input
                  v-model="airtelForm.username"
                  type="text"
                  placeholder="Username"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Mot de passe</label>
                <input
                  v-model="airtelForm.password"
                  type="password"
                  placeholder="********"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium">Expéditeur (Origin)</label>
                <input
                  v-model="airtelForm.origin_addr"
                  type="text"
                  placeholder="SENDWAVE"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Coût par SMS (FCFA)</label>
                <input
                  v-model.number="airtelForm.cost_per_sms"
                  type="number"
                  min="0"
                  placeholder="20"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
            </div>

            <div class="flex gap-3 pt-4 border-t">
              <button
                type="submit"
                :disabled="savingAirtel"
                class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
              >
                <div v-if="savingAirtel" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span>{{ savingAirtel ? 'Enregistrement...' : 'Enregistrer' }}</span>
              </button>
              <button
                type="button"
                @click="openTestModal('airtel')"
                :disabled="!configs.airtel?.is_active"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
              >
                <BeakerIcon class="w-4 h-4" />
                <span>Tester</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Moov Config -->
        <div class="rounded-lg border bg-card shadow-sm">
          <div class="p-6 border-b">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                  <SignalIcon class="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <h2 class="text-xl font-bold">Moov Gabon</h2>
                  <p class="text-sm text-muted-foreground">Préfixes: 60, 62, 65, 66 (SMPP)</p>
                </div>
              </div>
              <button
                @click="toggleConfig('moov')"
                :disabled="togglingMoov"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                :class="configs.moov?.is_active ? 'bg-success' : 'bg-muted'"
              >
                <span
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                  :class="configs.moov?.is_active ? 'translate-x-5' : 'translate-x-0'"
                />
              </button>
            </div>
          </div>

          <form @submit.prevent="saveConfig('moov')" class="p-6 space-y-4">
            <div class="p-3 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
              <p class="text-sm text-blue-700 dark:text-blue-300">
                Moov utilise le protocole SMPP v3.4 sur le port 12775
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium">Hôte SMPP</label>
                <input
                  v-model="moovForm.api_url"
                  type="text"
                  placeholder="172.16.59.66"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Port SMPP</label>
                <input
                  v-model.number="moovForm.port"
                  type="number"
                  placeholder="12775"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium">System ID</label>
                <input
                  v-model="moovForm.username"
                  type="text"
                  placeholder="system_id"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Mot de passe</label>
                <input
                  v-model="moovForm.password"
                  type="password"
                  placeholder="********"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium">Source Address</label>
                <input
                  v-model="moovForm.origin_addr"
                  type="text"
                  placeholder="SENDWAVE"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Coût par SMS (FCFA)</label>
                <input
                  v-model.number="moovForm.cost_per_sms"
                  type="number"
                  min="0"
                  placeholder="20"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
            </div>

            <div class="flex gap-3 pt-4 border-t">
              <button
                type="submit"
                :disabled="savingMoov"
                class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
              >
                <div v-if="savingMoov" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span>{{ savingMoov ? 'Enregistrement...' : 'Enregistrer' }}</span>
              </button>
              <button
                type="button"
                @click="openTestModal('moov')"
                :disabled="!configs.moov?.is_active"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
              >
                <BeakerIcon class="w-4 h-4" />
                <span>Tester</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Info Card -->
      <div class="mt-6 p-4 bg-primary/10 rounded-lg border border-primary/20">
        <div class="flex items-start gap-3">
          <InformationCircleIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
          <div class="text-sm">
            <p class="font-medium mb-2">Configuration des opérateurs</p>
            <ul class="space-y-1 text-muted-foreground">
              <li>Les SMS sont automatiquement routés vers l'opérateur approprié selon le préfixe du numéro</li>
              <li>Airtel: HTTP API classique | Moov: Protocole SMPP v3.4</li>
              <li>Les mots de passe sont chiffrés en base de données</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Test -->
    <div
      v-if="showTestModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showTestModal = false"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Tester {{ testProvider === 'airtel' ? 'Airtel' : 'Moov' }}</h2>
          <button @click="showTestModal = false" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Numéro de test *</label>
            <input
              v-model="testForm.phone_number"
              type="tel"
              :placeholder="testProvider === 'airtel' ? '+24177000000' : '+24160000000'"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Message (optionnel)</label>
            <textarea
              v-model="testForm.message"
              rows="3"
              placeholder="Test de configuration API..."
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            ></textarea>
          </div>

          <div v-if="testResult" class="p-4 rounded-lg" :class="testResult.success ? 'bg-success/10' : 'bg-destructive/10'">
            <p class="font-medium" :class="testResult.success ? 'text-success' : 'text-destructive'">
              {{ testResult.success ? 'Test réussi !' : 'Test échoué' }}
            </p>
            <p class="text-sm text-muted-foreground mt-1">{{ testResult.message }}</p>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="showTestModal = false"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Fermer
            </button>
            <button
              type="button"
              @click="runTest"
              :disabled="testing || !testForm.phone_number"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="testing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ testing ? 'Envoi...' : 'Envoyer le test' }}</span>
            </button>
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
  SignalIcon,
  BeakerIcon,
  XMarkIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'
import { showSuccess, showError } from '@/utils/notifications'
import api from '@/services/api'

interface SmsConfigData {
  id?: number
  provider: string
  api_url: string
  port?: number
  username: string
  password?: string
  origin_addr: string
  cost_per_sms: number
  is_active: boolean
}

const loading = ref(true)
const savingAirtel = ref(false)
const savingMoov = ref(false)
const togglingAirtel = ref(false)
const togglingMoov = ref(false)
const showTestModal = ref(false)
const testProvider = ref<'airtel' | 'moov'>('airtel')
const testing = ref(false)
const testResult = ref<{ success: boolean; message: string } | null>(null)

const configs = reactive<{ airtel?: SmsConfigData; moov?: SmsConfigData }>({})

const airtelForm = reactive({
  api_url: '',
  username: '',
  password: '',
  origin_addr: '',
  cost_per_sms: 20
})

const moovForm = reactive({
  api_url: '',
  port: 12775,
  username: '',
  password: '',
  origin_addr: '',
  cost_per_sms: 20
})

const testForm = reactive({
  phone_number: '',
  message: ''
})

async function loadConfigs() {
  loading.value = true
  try {
    const response = await api.get('/sms-configs')
    const data = response.data.data as SmsConfigData[]

    data.forEach(config => {
      if (config.provider === 'airtel') {
        configs.airtel = config
        Object.assign(airtelForm, {
          api_url: config.api_url || '',
          username: config.username || '',
          password: '',
          origin_addr: config.origin_addr || '',
          cost_per_sms: config.cost_per_sms || 20
        })
      } else if (config.provider === 'moov') {
        configs.moov = config
        Object.assign(moovForm, {
          api_url: config.api_url || '',
          port: config.port || 12775,
          username: config.username || '',
          password: '',
          origin_addr: config.origin_addr || '',
          cost_per_sms: config.cost_per_sms || 20
        })
      }
    })
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement')
  } finally {
    loading.value = false
  }
}

async function saveConfig(provider: 'airtel' | 'moov') {
  const form = provider === 'airtel' ? airtelForm : moovForm
  const savingRef = provider === 'airtel' ? savingAirtel : savingMoov

  savingRef.value = true
  try {
    const payload: any = { ...form }
    if (!payload.password) delete payload.password

    const response = await api.put(`/sms-configs/${provider}`, payload)
    if (provider === 'airtel') {
      configs.airtel = response.data.data
    } else {
      configs.moov = response.data.data
    }
    showSuccess('Configuration enregistrée')
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'enregistrement')
  } finally {
    savingRef.value = false
  }
}

async function toggleConfig(provider: 'airtel' | 'moov') {
  const togglingRef = provider === 'airtel' ? togglingAirtel : togglingMoov

  togglingRef.value = true
  try {
    const response = await api.post(`/sms-configs/${provider}/toggle`)
    if (provider === 'airtel') {
      configs.airtel = response.data.data
    } else {
      configs.moov = response.data.data
    }
    showSuccess(response.data.message)
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du changement de statut')
  } finally {
    togglingRef.value = false
  }
}

function openTestModal(provider: 'airtel' | 'moov') {
  testProvider.value = provider
  testForm.phone_number = ''
  testForm.message = ''
  testResult.value = null
  showTestModal.value = true
}

async function runTest() {
  if (!testForm.phone_number) {
    showError('Veuillez entrer un numéro de test')
    return
  }

  testing.value = true
  testResult.value = null
  try {
    const response = await api.post(`/sms-configs/${testProvider.value}/test`, testForm)
    testResult.value = {
      success: true,
      message: response.data.message
    }
  } catch (err: any) {
    testResult.value = {
      success: false,
      message: err.response?.data?.error || err.response?.data?.message || 'Erreur lors du test'
    }
  } finally {
    testing.value = false
  }
}

onMounted(() => {
  loadConfigs()
})
</script>
