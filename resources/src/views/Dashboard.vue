<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header responsive -->
      <div class="mb-4 sm:mb-6 lg:mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-foreground">Tableau de bord</h1>
            <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Bienvenue {{ user?.name }}</p>
          </div>
          <!-- Period buttons - wrap on mobile -->
          <div class="flex flex-wrap gap-2">
            <button
              v-for="period in periods"
              :key="period.value"
              @click="selectedPeriod = period.value"
              class="px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors whitespace-nowrap"
              :class="selectedPeriod === period.value ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'"
            >
              {{ period.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="flex items-center justify-center py-8 sm:py-12">
        <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else>
        <!-- Statistiques principales -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6 lg:mb-8">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-3 sm:p-4 lg:p-6">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
              <h3 class="text-xs sm:text-sm font-medium text-muted-foreground">Messages Envoyés</h3>
              <PaperAirplaneIcon class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-primary flex-shrink-0" />
            </div>
            <p class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ formatNumber(analytics?.total_sent || 0) }}</p>
            <p class="text-xs mt-1 sm:mt-2 hidden sm:block" :class="getTrendClass(analytics?.trends?.sent)">
              {{ getTrendLabel(analytics?.trends?.sent) }}
            </p>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-3 sm:p-4 lg:p-6">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
              <h3 class="text-xs sm:text-sm font-medium text-muted-foreground">Messages Livrés</h3>
              <CheckCircleIcon class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-success flex-shrink-0" />
            </div>
            <p class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ formatNumber(analytics?.total_delivered || 0) }}</p>
            <p class="text-xs mt-1 sm:mt-2 hidden sm:block" :class="getTrendClass(analytics?.trends?.delivered)">
              {{ getTrendLabel(analytics?.trends?.delivered) }}
            </p>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-3 sm:p-4 lg:p-6">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
              <h3 class="text-xs sm:text-sm font-medium text-muted-foreground">Taux Livraison</h3>
              <ChartBarIcon class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-primary flex-shrink-0" />
            </div>
            <p class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ analytics?.delivery_rate?.toFixed(1) || 0 }}%</p>
            <p class="text-xs mt-1 sm:mt-2 hidden sm:block" :class="getDeliveryRateClass(analytics?.delivery_rate)">
              {{ getDeliveryRateLabel(analytics?.delivery_rate) }}
            </p>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-3 sm:p-4 lg:p-6">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
              <h3 class="text-xs sm:text-sm font-medium text-muted-foreground">Coût Total</h3>
              <CreditCardIcon class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-primary flex-shrink-0" />
            </div>
            <p class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ formatCurrency(analytics?.total_cost || 0) }}</p>
            <p class="text-xs mt-1 sm:mt-2 hidden sm:block" :class="getTrendClass(analytics?.trends?.cost)">
              {{ getTrendLabel(analytics?.trends?.cost) }}
            </p>
          </div>
        </div>

        <!-- Statistiques par fournisseur -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6 lg:mb-8">
          <div class="rounded-lg border bg-card shadow-sm">
            <div class="p-4 sm:p-6 border-b border-border">
              <h2 class="text-base sm:text-lg lg:text-xl font-semibold">Répartition par Fournisseur</h2>
            </div>
            <div class="p-4 sm:p-6">
              <div v-if="analytics?.by_provider && Object.keys(analytics.by_provider).length > 0" class="space-y-3 sm:space-y-4">
                <div v-for="(data, provider) in analytics.by_provider" :key="provider" class="space-y-1.5 sm:space-y-2">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="font-medium text-sm sm:text-base">{{ provider }}</span>
                    <span class="text-xs sm:text-sm text-muted-foreground">{{ formatNumber(data.sent) }} messages</span>
                  </div>
                  <div class="w-full bg-muted rounded-full h-2">
                    <div
                      class="h-2 rounded-full"
                      :class="provider === 'Airtel' ? 'bg-red-500' : 'bg-yellow-500'"
                      :style="{ width: `${(data.sent / analytics.total_sent * 100).toFixed(1)}%` }"
                    ></div>
                  </div>
                  <div class="flex flex-col sm:flex-row sm:justify-between gap-1 text-xs text-muted-foreground">
                    <span>Livrés: {{ data.delivered_rate?.toFixed(1) }}%</span>
                    <span>Coût: {{ formatCurrency(data.total_cost) }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-muted-foreground py-6 sm:py-8">
                <p class="text-xs sm:text-sm">Aucune donnée disponible</p>
              </div>
            </div>
          </div>

          <div class="rounded-lg border bg-card shadow-sm">
            <div class="p-4 sm:p-6 border-b border-border">
              <h2 class="text-base sm:text-lg lg:text-xl font-semibold">Crédits Restants</h2>
            </div>
            <div class="p-4 sm:p-6">
              <div v-if="analytics?.credits_remaining !== undefined" class="space-y-3 sm:space-y-4">
                <div class="text-center">
                  <p class="text-3xl sm:text-4xl lg:text-5xl font-bold text-primary">{{ formatNumber(analytics.credits_remaining) }}</p>
                  <p class="text-xs sm:text-sm text-muted-foreground mt-1 sm:mt-2">crédits disponibles</p>
                </div>
                <div class="w-full bg-muted rounded-full h-2 sm:h-3">
                  <div
                    class="h-full rounded-full transition-all"
                    :class="getCreditBarClass(analytics.credits_remaining, analytics.initial_credits)"
                    :style="{ width: `${getCreditPercentage(analytics.credits_remaining, analytics.initial_credits)}%` }"
                  ></div>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between gap-1 text-xs text-muted-foreground">
                  <span>Utilisés: {{ formatNumber((analytics.initial_credits || 0) - analytics.credits_remaining) }}</span>
                  <span>Total: {{ formatNumber(analytics.initial_credits || 0) }}</span>
                </div>
              </div>
              <div v-else class="text-center text-muted-foreground py-6 sm:py-8">
                <p class="text-xs sm:text-sm">Aucune donnée disponible</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Campagnes récentes et activité -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 lg:gap-6">
          <!-- Campagnes récentes -->
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="p-4 sm:p-6 border-b border-border">
              <h2 class="text-base sm:text-lg lg:text-xl font-semibold">Campagnes Récentes</h2>
            </div>
            <div class="p-4 sm:p-6">
              <div v-if="recentCampaigns.length > 0">
                <div v-for="campaign in recentCampaigns" :key="campaign.id" class="mb-3 sm:mb-4 last:mb-0 pb-3 sm:pb-4 last:pb-0 border-b last:border-b-0 border-border">
                  <div class="flex items-start justify-between gap-2 mb-1 sm:mb-2">
                    <h3 class="font-medium text-sm sm:text-base truncate">{{ campaign.name }}</h3>
                    <span
                      class="text-xs px-2 py-0.5 sm:py-1 rounded-full flex-shrink-0"
                      :class="getCampaignStatusClass(campaign.status)"
                    >
                      {{ getCampaignStatusLabel(campaign.status) }}
                    </span>
                  </div>
                  <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs sm:text-sm text-muted-foreground">
                    <span class="flex items-center gap-1">
                      <PaperAirplaneIcon class="w-3 h-3 sm:w-4 sm:h-4" />
                      {{ campaign.messages_sent || campaign.sent || 0 }} envoyés
                    </span>
                    <span class="flex items-center gap-1">
                      <CheckCircleIcon class="w-3 h-3 sm:w-4 sm:h-4" />
                      {{ campaign.delivery_rate || campaign.delivered || 0 }}% livrés
                    </span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-muted-foreground py-6 sm:py-8">
                <p class="text-xs sm:text-sm">Aucune campagne récente</p>
              </div>
            </div>
          </div>

          <!-- Activité récente -->
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="p-4 sm:p-6 border-b border-border">
              <h2 class="text-base sm:text-lg lg:text-xl font-semibold">Activité Récente</h2>
            </div>
            <div class="p-4 sm:p-6">
              <div v-if="recentActivities.length > 0">
                <div v-for="activity in recentActivities" :key="activity.id" class="mb-3 sm:mb-4 last:mb-0 flex items-start gap-2 sm:gap-3">
                  <component :is="activity.icon" class="w-4 h-4 sm:w-5 sm:h-5 text-muted-foreground flex-shrink-0 mt-0.5" />
                  <div class="flex-1 min-w-0">
                    <p class="text-xs sm:text-sm font-medium truncate">{{ activity.title }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5 sm:mt-1">{{ activity.time }}</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-muted-foreground py-6 sm:py-8">
                <p class="text-xs sm:text-sm">Aucune activité récente</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/components/MainLayout.vue'
import {
  UsersIcon,
  ChartBarIcon,
  PaperAirplaneIcon,
  CheckCircleIcon,
  CreditCardIcon
} from '@heroicons/vue/24/outline'
import analyticsService from '@/services/analyticsService'
import { campaignService } from '@/services/campaignService'
import { contactService } from '@/services/contactService'

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const periods = [
  { value: 'today', label: "Aujourd'hui" },
  { value: 'week', label: 'Cette semaine' },
  { value: 'month', label: 'Ce mois' },
  { value: 'year', label: 'Cette année' }
]

const selectedPeriod = ref('today')
const analytics = ref<any>(null)
const recentCampaigns = ref<any[]>([])
const recentActivities = ref<any[]>([])
const loading = ref(false)

function formatNumber(value: number): string {
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K`
  return value.toString()
}

function formatCurrency(value: number): string {
  return `${value.toLocaleString('fr-FR')} XAF`
}

function getTrendClass(trend?: number): string {
  if (!trend) return 'text-muted-foreground'
  if (trend > 0) return 'text-success'
  if (trend < 0) return 'text-destructive'
  return 'text-muted-foreground'
}

function getTrendLabel(trend?: number): string {
  if (!trend) return 'Aucun changement'
  const sign = trend > 0 ? '+' : ''
  return `${sign}${trend.toFixed(1)}% vs période précédente`
}

function getDeliveryRateClass(rate?: number): string {
  if (!rate) return 'text-muted-foreground'
  if (rate >= 95) return 'text-success'
  if (rate >= 85) return 'text-warning'
  return 'text-destructive'
}

function getDeliveryRateLabel(rate?: number): string {
  if (!rate) return 'Aucune donnée'
  if (rate >= 95) return 'Excellent'
  if (rate >= 85) return 'Bon'
  return 'À améliorer'
}

function getCreditPercentage(remaining: number, initial: number): number {
  if (!initial) return 0
  return (remaining / initial) * 100
}

function getCreditBarClass(remaining: number, initial: number): string {
  const percentage = getCreditPercentage(remaining, initial)
  if (percentage > 50) return 'bg-success'
  if (percentage > 20) return 'bg-warning'
  return 'bg-destructive'
}

async function loadDashboardData() {
  loading.value = true
  try {
    const [analyticsData, campaignsData] = await Promise.all([
      analyticsService.getDashboard(selectedPeriod.value),
      campaignService.getAll()
    ])

    // Mapper les données de l'API au format attendu par le template
    const data = analyticsData.data
    analytics.value = {
      total_sent: data.overview?.sms_sent || 0,
      total_delivered: data.overview?.sms_delivered || 0,
      total_failed: data.overview?.sms_failed || 0,
      delivery_rate: data.overview?.success_rate || 0,
      total_cost: data.overview?.total_cost || 0,
      campaigns_executed: data.overview?.campaigns_executed || 0,
      contacts_added: data.overview?.contacts_added || 0,
      trends: {
        sent: data.trends?.sms_sent_change || 0,
        delivered: data.trends?.success_rate_change || 0,
        cost: data.trends?.cost_change || 0,
        campaigns: data.trends?.campaigns_change || 0
      },
      by_provider: {
        Airtel: {
          sent: data.providers?.airtel?.count || 0,
          delivered_rate: data.providers?.airtel?.percentage || 0,
          total_cost: data.cost_analysis?.airtel_cost || 0
        },
        Moov: {
          sent: data.providers?.moov?.count || 0,
          delivered_rate: data.providers?.moov?.percentage || 0,
          total_cost: data.cost_analysis?.moov_cost || 0
        }
      },
      credits_remaining: user.value?.credits || 0,
      initial_credits: user.value?.initial_credits || user.value?.credits || 1000
    }

    // Utiliser les campagnes de l'API analytics si disponibles, sinon les campagnes récentes
    recentCampaigns.value = data.campaigns?.length > 0
      ? data.campaigns.slice(0, 3)
      : campaignsData.slice(0, 3)

    // Générer les activités récentes
    recentActivities.value = generateRecentActivities(campaignsData)
  } catch (err) {
    console.error('Error loading dashboard data:', err)
  } finally {
    loading.value = false
  }
}

watch(selectedPeriod, () => {
  loadDashboardData()
})

function generateRecentActivities(campaigns: any[]): any[] {
  const activities: any[] = []

  // Ajouter les campagnes récentes
  campaigns.slice(0, 4).forEach(campaign => {
    if (campaign.messages_sent || campaign.sent) {
      activities.push({
        id: `campaign-${campaign.id}`,
        icon: PaperAirplaneIcon,
        title: `Campagne "${campaign.name}" envoyée à ${campaign.messages_sent || campaign.sent} contacts`,
        time: formatRelativeTime(campaign.created_at)
      })
    }
  })

  return activities.slice(0, 4)
}

function formatRelativeTime(dateString?: string): string {
  if (!dateString) return 'Récemment'
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (hours < 1) return 'Il y a quelques minutes'
  if (hours < 24) return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`
  if (days === 1) return 'Il y a 1 jour'
  return `Il y a ${days} jours`
}

function getCampaignStatusClass(status: string): string {
  const lowerStatus = status?.toLowerCase()
  if (lowerStatus === 'active' || lowerStatus === 'actif') {
    return 'bg-success/10 text-success'
  }
  if (lowerStatus === 'scheduled' || lowerStatus === 'planifié') {
    return 'bg-warning/10 text-warning'
  }
  return 'bg-muted text-muted-foreground'
}

function getCampaignStatusLabel(status: string): string {
  const lowerStatus = status?.toLowerCase()
  if (lowerStatus === 'active' || lowerStatus === 'actif') return 'Active'
  if (lowerStatus === 'scheduled' || lowerStatus === 'planifié') return 'Planifiée'
  if (lowerStatus === 'completed' || lowerStatus === 'terminé') return 'Terminée'
  return status || 'Inconnu'
}

onMounted(() => {
  loadDashboardData()
})
</script>
