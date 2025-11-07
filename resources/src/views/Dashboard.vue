<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-foreground">Tableau de bord</h1>
          <p class="text-muted-foreground mt-2">Bienvenue {{ user?.name }}</p>
        </div>
        <div class="flex gap-2">
          <button
            v-for="period in periods"
            :key="period.value"
            @click="selectedPeriod = period.value"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="selectedPeriod === period.value ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'"
          >
            {{ period.label }}
          </button>
        </div>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else>
        <!-- Statistiques principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-muted-foreground">Messages Envoyés</h3>
              <PaperAirplaneIcon class="w-8 h-8 text-primary" />
            </div>
            <p class="text-3xl font-bold mt-2">{{ formatNumber(analytics?.total_sent || 0) }}</p>
            <p class="text-xs mt-2" :class="getTrendClass(analytics?.trends?.sent)">
              {{ getTrendLabel(analytics?.trends?.sent) }}
            </p>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-muted-foreground">Messages Livrés</h3>
              <CheckCircleIcon class="w-8 h-8 text-success" />
            </div>
            <p class="text-3xl font-bold mt-2">{{ formatNumber(analytics?.total_delivered || 0) }}</p>
            <p class="text-xs mt-2" :class="getTrendClass(analytics?.trends?.delivered)">
              {{ getTrendLabel(analytics?.trends?.delivered) }}
            </p>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-muted-foreground">Taux de Livraison</h3>
              <ChartBarIcon class="w-8 h-8 text-primary" />
            </div>
            <p class="text-3xl font-bold mt-2">{{ analytics?.delivery_rate?.toFixed(1) || 0 }}%</p>
            <p class="text-xs mt-2" :class="getDeliveryRateClass(analytics?.delivery_rate)">
              {{ getDeliveryRateLabel(analytics?.delivery_rate) }}
            </p>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-muted-foreground">Coût Total</h3>
              <CreditCardIcon class="w-8 h-8 text-primary" />
            </div>
            <p class="text-3xl font-bold mt-2">{{ formatCurrency(analytics?.total_cost || 0) }}</p>
            <p class="text-xs mt-2" :class="getTrendClass(analytics?.trends?.cost)">
              {{ getTrendLabel(analytics?.trends?.cost) }}
            </p>
          </div>
        </div>

        <!-- Statistiques par fournisseur -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div class="rounded-lg border bg-card shadow-sm">
            <div class="p-6 border-b border-border">
              <h2 class="text-xl font-semibold">Répartition par Fournisseur</h2>
            </div>
            <div class="p-6">
              <div v-if="analytics?.by_provider && Object.keys(analytics.by_provider).length > 0" class="space-y-4">
                <div v-for="(data, provider) in analytics.by_provider" :key="provider" class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="font-medium">{{ provider }}</span>
                    <span class="text-sm text-muted-foreground">{{ formatNumber(data.sent) }} messages</span>
                  </div>
                  <div class="w-full bg-muted rounded-full h-2">
                    <div
                      class="h-2 rounded-full"
                      :class="provider === 'Airtel' ? 'bg-red-500' : 'bg-yellow-500'"
                      :style="{ width: `${(data.sent / analytics.total_sent * 100).toFixed(1)}%` }"
                    ></div>
                  </div>
                  <div class="flex justify-between text-xs text-muted-foreground">
                    <span>Livrés: {{ data.delivered_rate?.toFixed(1) }}%</span>
                    <span>Coût: {{ formatCurrency(data.total_cost) }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-muted-foreground py-8">
                <p class="text-sm">Aucune donnée disponible</p>
              </div>
            </div>
          </div>

          <div class="rounded-lg border bg-card shadow-sm">
            <div class="p-6 border-b border-border">
              <h2 class="text-xl font-semibold">Crédits Restants</h2>
            </div>
            <div class="p-6">
              <div v-if="analytics?.credits_remaining !== undefined" class="space-y-4">
                <div class="text-center">
                  <p class="text-5xl font-bold text-primary">{{ formatNumber(analytics.credits_remaining) }}</p>
                  <p class="text-sm text-muted-foreground mt-2">crédits disponibles</p>
                </div>
                <div class="w-full bg-muted rounded-full h-3">
                  <div
                    class="h-3 rounded-full transition-all"
                    :class="getCreditBarClass(analytics.credits_remaining, analytics.initial_credits)"
                    :style="{ width: `${getCreditPercentage(analytics.credits_remaining, analytics.initial_credits)}%` }"
                  ></div>
                </div>
                <div class="flex justify-between text-xs text-muted-foreground">
                  <span>Utilisés: {{ formatNumber((analytics.initial_credits || 0) - analytics.credits_remaining) }}</span>
                  <span>Total: {{ formatNumber(analytics.initial_credits || 0) }}</span>
                </div>
              </div>
              <div v-else class="text-center text-muted-foreground py-8">
                <p class="text-sm">Aucune donnée disponible</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Campagnes récentes et activité -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Campagnes récentes -->
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
          <div class="p-6 border-b border-border">
            <h2 class="text-xl font-semibold">Campagnes Récentes</h2>
          </div>
          <div class="p-6">
            <div v-if="recentCampaigns.length > 0">
              <div v-for="campaign in recentCampaigns" :key="campaign.id" class="mb-4 last:mb-0 pb-4 last:pb-0 border-b last:border-b-0 border-border">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="font-medium">{{ campaign.name }}</h3>
                  <span
                    class="text-xs px-2 py-1 rounded-full"
                    :class="getCampaignStatusClass(campaign.status)"
                  >
                    {{ getCampaignStatusLabel(campaign.status) }}
                  </span>
                </div>
                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                  <span class="flex items-center gap-1">
                    <PaperAirplaneIcon class="w-4 h-4" />
                    {{ campaign.messages_sent || campaign.sent || 0 }} envoyés
                  </span>
                  <span class="flex items-center gap-1">
                    <CheckCircleIcon class="w-4 h-4" />
                    {{ campaign.delivery_rate || campaign.delivered || 0 }}% livrés
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted-foreground py-8">
              <p class="text-sm">Aucune campagne récente</p>
            </div>
          </div>
        </div>

        <!-- Activité récente -->
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
          <div class="p-6 border-b border-border">
            <h2 class="text-xl font-semibold">Activité Récente</h2>
          </div>
          <div class="p-6">
            <div v-if="recentActivities.length > 0">
              <div v-for="activity in recentActivities" :key="activity.id" class="mb-4 last:mb-0 flex items-start gap-3">
                <component :is="activity.icon" class="w-5 h-5 text-muted-foreground flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm font-medium">{{ activity.title }}</p>
                  <p class="text-xs text-muted-foreground mt-1">{{ activity.time }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted-foreground py-8">
              <p class="text-sm">Aucune activité récente</p>
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
  return `${value.toLocaleString('fr-FR')} XOF`
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
    analytics.value = analyticsData.data
    recentCampaigns.value = campaignsData.slice(0, 3)

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
