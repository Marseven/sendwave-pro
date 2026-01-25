<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-6 sm:mb-8">
        <div class="flex items-center gap-2">
          <Cog6ToothIcon class="w-6 h-6 sm:w-8 sm:h-8 text-primary" />
          <h1 class="text-2xl sm:text-3xl font-bold">Paramètres</h1>
        </div>
        <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Configuration de la plateforme SMS</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Configuration SMS -->
        <div class="rounded-lg border bg-card p-4 sm:p-6">
          <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
            <ChatBubbleLeftRightIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Configuration SMS</h3>
          </div>
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Sender ID par défaut</label>
              <select
                v-model="settings.default_sender_id"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              >
                <option value="JOBSSMS">JOBSSMS</option>
                <option value="SendWave">SendWave</option>
                <option value="custom">Personnalisé</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Route SMS par défaut</label>
              <select
                v-model="settings.default_route"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              >
                <option value="auto">Automatique (recommandé)</option>
                <option value="airtel">Airtel Direct</option>
                <option value="moov">Moov Direct</option>
              </select>
              <p class="text-xs text-muted-foreground">La route automatique optimise le coût et la délivrabilité</p>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Signature par défaut</label>
              <input
                v-model="settings.default_signature"
                type="text"
                placeholder="Ex: - JOBS SMS"
                maxlength="30"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              />
              <p class="text-xs text-muted-foreground">Ajoutée automatiquement à la fin de vos messages (max 30 car.)</p>
            </div>
            <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50">
              <div>
                <p class="text-sm font-medium">Rapport de livraison</p>
                <p class="text-xs text-muted-foreground">Activer par défaut pour tous les envois</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.default_dlr" class="sr-only peer">
                <div class="w-11 h-6 bg-muted rounded-full peer peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-primary/20 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Notifications plateforme -->
        <div class="rounded-lg border bg-card p-4 sm:p-6">
          <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
            <BellIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Notifications</h3>
          </div>
          <div class="space-y-3">
            <label class="flex items-center justify-between p-3 rounded-lg hover:bg-muted/50 cursor-pointer">
              <div class="flex items-center gap-3">
                <EnvelopeIcon class="w-5 h-5 text-muted-foreground" />
                <div>
                  <p class="text-sm font-medium">Alertes par email</p>
                  <p class="text-xs text-muted-foreground">Recevoir les alertes importantes</p>
                </div>
              </div>
              <input v-model="settings.email_notifications" type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary" />
            </label>
            <label class="flex items-center justify-between p-3 rounded-lg hover:bg-muted/50 cursor-pointer">
              <div class="flex items-center gap-3">
                <ChartBarIcon class="w-5 h-5 text-muted-foreground" />
                <div>
                  <p class="text-sm font-medium">Rapports hebdomadaires</p>
                  <p class="text-xs text-muted-foreground">Statistiques chaque lundi</p>
                </div>
              </div>
              <input v-model="settings.weekly_reports" type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary" />
            </label>
            <label class="flex items-center justify-between p-3 rounded-lg hover:bg-muted/50 cursor-pointer">
              <div class="flex items-center gap-3">
                <ExclamationTriangleIcon class="w-5 h-5 text-muted-foreground" />
                <div>
                  <p class="text-sm font-medium">Alertes campagnes</p>
                  <p class="text-xs text-muted-foreground">Échecs et anomalies</p>
                </div>
              </div>
              <input v-model="settings.campaign_alerts" type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary" />
            </label>
            <label class="flex items-center justify-between p-3 rounded-lg hover:bg-muted/50 cursor-pointer">
              <div class="flex items-center gap-3">
                <CreditCardIcon class="w-5 h-5 text-muted-foreground" />
                <div>
                  <p class="text-sm font-medium">Alerte crédit faible</p>
                  <p class="text-xs text-muted-foreground">Quand le solde est bas</p>
                </div>
              </div>
              <input v-model="settings.low_credit_alert" type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary" />
            </label>
          </div>
        </div>

        <!-- Limites et quotas -->
        <div class="rounded-lg border bg-card p-4 sm:p-6">
          <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
            <AdjustmentsHorizontalIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Limites et quotas</h3>
          </div>
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Seuil alerte crédit (FCFA)</label>
              <input
                v-model.number="settings.credit_alert_threshold"
                type="number"
                min="0"
                step="1000"
                placeholder="5000"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              />
              <p class="text-xs text-muted-foreground">Alerte quand le solde passe en dessous</p>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Limite SMS par campagne</label>
              <input
                v-model.number="settings.max_sms_per_campaign"
                type="number"
                min="100"
                step="100"
                placeholder="10000"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              />
              <p class="text-xs text-muted-foreground">Nombre maximum de SMS par campagne</p>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Limite envois par minute</label>
              <select
                v-model="settings.rate_limit"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              >
                <option value="50">50 SMS/min (Standard)</option>
                <option value="100">100 SMS/min (Rapide)</option>
                <option value="200">200 SMS/min (Très rapide)</option>
                <option value="500">500 SMS/min (Maximum)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Préférences régionales -->
        <div class="rounded-lg border bg-card p-4 sm:p-6">
          <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
            <GlobeAltIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Préférences régionales</h3>
          </div>
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Langue de l'interface</label>
              <select
                v-model="settings.language"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              >
                <option value="fr">Français</option>
                <option value="en">English</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Fuseau horaire</label>
              <select
                v-model="settings.timezone"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              >
                <option value="Africa/Libreville">Africa/Libreville (GMT+1)</option>
                <option value="Africa/Lagos">Africa/Lagos (GMT+1)</option>
                <option value="Africa/Douala">Africa/Douala (GMT+1)</option>
                <option value="Europe/Paris">Europe/Paris (GMT+1/+2)</option>
                <option value="UTC">UTC (GMT+0)</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Format de date</label>
              <select
                v-model="settings.date_format"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              >
                <option value="dd/MM/yyyy">JJ/MM/AAAA (25/01/2026)</option>
                <option value="MM/dd/yyyy">MM/JJ/AAAA (01/25/2026)</option>
                <option value="yyyy-MM-dd">AAAA-MM-JJ (2026-01-25)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Thème et affichage -->
        <div class="rounded-lg border bg-card p-4 sm:p-6">
          <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
            <SwatchIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Thème et affichage</h3>
          </div>
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Thème de l'interface</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  @click="settings.theme = 'light'"
                  class="p-3 rounded-lg border-2 transition-all"
                  :class="settings.theme === 'light' ? 'border-primary bg-primary/5' : 'border-input hover:border-primary/50'"
                >
                  <SunIcon class="w-5 h-5 mx-auto mb-1" />
                  <p class="text-xs font-medium">Clair</p>
                </button>
                <button
                  @click="settings.theme = 'dark'"
                  class="p-3 rounded-lg border-2 transition-all"
                  :class="settings.theme === 'dark' ? 'border-primary bg-primary/5' : 'border-input hover:border-primary/50'"
                >
                  <MoonIcon class="w-5 h-5 mx-auto mb-1" />
                  <p class="text-xs font-medium">Sombre</p>
                </button>
                <button
                  @click="settings.theme = 'system'"
                  class="p-3 rounded-lg border-2 transition-all"
                  :class="settings.theme === 'system' ? 'border-primary bg-primary/5' : 'border-input hover:border-primary/50'"
                >
                  <ComputerDesktopIcon class="w-5 h-5 mx-auto mb-1" />
                  <p class="text-xs font-medium">Système</p>
                </button>
              </div>
            </div>
            <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50">
              <div>
                <p class="text-sm font-medium">Mode compact</p>
                <p class="text-xs text-muted-foreground">Réduire les espaces dans l'interface</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.compact_mode" class="sr-only peer">
                <div class="w-11 h-6 bg-muted rounded-full peer peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-primary/20 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Sécurité et accès -->
        <div class="rounded-lg border bg-card p-4 sm:p-6">
          <div class="flex items-center gap-2 mb-4 sm:mb-6 pb-4 border-b">
            <ShieldCheckIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Sécurité</h3>
          </div>
          <div class="space-y-4">
            <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50">
              <div>
                <p class="text-sm font-medium">Double authentification</p>
                <p class="text-xs text-muted-foreground">Sécuriser l'accès à votre compte</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.two_factor_enabled" class="sr-only peer">
                <div class="w-11 h-6 bg-muted rounded-full peer peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-primary/20 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
              </label>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Délai d'expiration de session</label>
              <select
                v-model="settings.session_timeout"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary"
              >
                <option value="30">30 minutes</option>
                <option value="60">1 heure</option>
                <option value="120">2 heures</option>
                <option value="480">8 heures</option>
                <option value="1440">24 heures</option>
              </select>
            </div>
            <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50">
              <div>
                <p class="text-sm font-medium">Journalisation des accès</p>
                <p class="text-xs text-muted-foreground">Enregistrer toutes les connexions</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.log_access" class="sr-only peer">
                <div class="w-11 h-6 bg-muted rounded-full peer peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-primary/20 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Messages et actions -->
      <div class="mt-6 max-w-2xl">
        <div v-if="successMessage" class="rounded-lg bg-success/10 border border-success/20 p-4 mb-4">
          <div class="flex items-center gap-2 text-success">
            <CheckCircleIcon class="w-5 h-5" />
            <p class="font-medium">{{ successMessage }}</p>
          </div>
        </div>

        <div v-if="errorMessage" class="rounded-lg bg-destructive/10 border border-destructive/20 p-4 mb-4">
          <div class="flex items-center gap-2 text-destructive">
            <ExclamationCircleIcon class="w-5 h-5" />
            <p class="font-medium">{{ errorMessage }}</p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
          <button @click="saveSettings" :disabled="saving" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6 py-2 disabled:opacity-50">
            <CheckCircleIcon v-if="!saving" class="w-4 h-4" />
            <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>{{ saving ? 'Enregistrement...' : 'Enregistrer les paramètres' }}</span>
          </button>
          <button @click="resetSettings" :disabled="saving" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-input bg-background hover:bg-accent h-10 px-4 py-2">
            <ArrowPathIcon class="w-4 h-4" />
            <span>Réinitialiser</span>
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
  ChatBubbleLeftRightIcon,
  BellIcon,
  EnvelopeIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  CreditCardIcon,
  GlobeAltIcon,
  AdjustmentsHorizontalIcon,
  SwatchIcon,
  SunIcon,
  MoonIcon,
  ComputerDesktopIcon,
  ShieldCheckIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import apiClient from '@/services/api'

interface PlatformSettings {
  // SMS Configuration
  default_sender_id: string
  default_route: string
  default_signature: string
  default_dlr: boolean
  // Notifications
  email_notifications: boolean
  weekly_reports: boolean
  campaign_alerts: boolean
  low_credit_alert: boolean
  // Limits
  credit_alert_threshold: number
  max_sms_per_campaign: number
  rate_limit: string
  // Regional
  language: string
  timezone: string
  date_format: string
  // Theme
  theme: string
  compact_mode: boolean
  // Security
  two_factor_enabled: boolean
  session_timeout: string
  log_access: boolean
}

const loading = ref(true)
const saving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const settings = ref<PlatformSettings>({
  default_sender_id: 'JOBSSMS',
  default_route: 'auto',
  default_signature: '',
  default_dlr: true,
  email_notifications: true,
  weekly_reports: true,
  campaign_alerts: true,
  low_credit_alert: true,
  credit_alert_threshold: 5000,
  max_sms_per_campaign: 10000,
  rate_limit: '100',
  language: 'fr',
  timezone: 'Africa/Libreville',
  date_format: 'dd/MM/yyyy',
  theme: 'system',
  compact_mode: false,
  two_factor_enabled: false,
  session_timeout: '60',
  log_access: true
})

const originalSettings = ref<PlatformSettings>({ ...settings.value })

async function loadSettings() {
  try {
    loading.value = true
    const response = await apiClient.get('/settings')
    const data = response.data.data || response.data

    settings.value = {
      ...settings.value,
      ...data
    }
    originalSettings.value = { ...settings.value }
  } catch (error) {
    console.error('Error loading settings:', error)
    // Use default values
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  try {
    saving.value = true
    successMessage.value = ''
    errorMessage.value = ''

    await apiClient.put('/settings', settings.value)

    originalSettings.value = { ...settings.value }
    successMessage.value = 'Paramètres enregistrés avec succès'

    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    console.error('Error saving settings:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'enregistrement'
  } finally {
    saving.value = false
  }
}

function resetSettings() {
  settings.value = { ...originalSettings.value }
  successMessage.value = ''
  errorMessage.value = ''
}

onMounted(() => {
  loadSettings()
})
</script>
