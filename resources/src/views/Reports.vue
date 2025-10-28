<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <div class="flex items-center gap-2">
          <ChartBarIcon class="w-8 h-8 text-primary" />
          <h1 class="text-3xl font-bold">Rapports & Statistiques</h1>
        </div>
        <p class="text-muted-foreground mt-2">Analysez les performances de vos campagnes</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <PaperAirplaneIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold">Envois cette semaine</h3>
            </div>
            <div class="text-4xl font-bold text-primary mb-2">{{ weeklyStats.total }}</div>
            <div class="flex items-center gap-2">
              <ArrowTrendingUpIcon v-if="weeklyStats.trend > 0" class="w-4 h-4 text-success" />
              <ArrowTrendingDownIcon v-else class="w-4 h-4 text-destructive" />
              <p class="text-sm" :class="weeklyStats.trend > 0 ? 'text-success' : 'text-destructive'">
                {{ weeklyStats.trend > 0 ? '+' : '' }}{{ weeklyStats.trend }}% vs semaine dernière
              </p>
            </div>
          </div>
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <CheckCircleIcon class="w-5 h-5 text-success" />
              <h3 class="font-semibold">Taux de livraison moyen</h3>
            </div>
            <div class="text-4xl font-bold text-success mb-2">{{ averageDeliveryRate }}%</div>
            <p class="text-sm text-muted-foreground">{{ deliveryRateLabel }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-2">
              <RocketLaunchIcon class="w-5 h-5 text-primary" />
              <h3 class="text-sm font-medium text-muted-foreground">Campagnes actives</h3>
            </div>
            <div class="text-3xl font-bold">{{ activeCampaignsCount }}</div>
          </div>
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-2">
              <ClockIcon class="w-5 h-5 text-warning" />
              <h3 class="text-sm font-medium text-muted-foreground">Campagnes planifiées</h3>
            </div>
            <div class="text-3xl font-bold">{{ scheduledCampaignsCount }}</div>
          </div>
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-2">
              <CheckBadgeIcon class="w-5 h-5 text-success" />
              <h3 class="text-sm font-medium text-muted-foreground">Campagnes terminées</h3>
            </div>
            <div class="text-3xl font-bold">{{ completedCampaignsCount }}</div>
          </div>
        </div>

        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-6">
            <TrophyIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Performance des campagnes</h3>
          </div>

          <div v-if="campaigns.length === 0" class="text-center py-12">
            <ChartBarIcon class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
            <p class="text-muted-foreground">Aucune campagne à afficher</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="campaign in campaigns" :key="campaign.id" class="flex items-center justify-between p-4 bg-muted/50 rounded-lg hover:bg-muted transition-colors">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <div class="font-medium">{{ campaign.name }}</div>
                  <span class="text-xs px-2 py-1 rounded-full" :class="getStatusClass(campaign.status)">
                    {{ campaign.status }}
                  </span>
                </div>
                <div class="flex items-center gap-4 text-sm text-muted-foreground mt-1">
                  <div class="flex items-center gap-1">
                    <PaperAirplaneIcon class="w-4 h-4" />
                    <span>{{ formatNumber(campaign.messages_sent || campaign.sent || 0) }} envois</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <CheckCircleIcon class="w-4 h-4" />
                    <span>{{ campaign.delivery_rate || campaign.delivered || 0 }}% livrés</span>
                  </div>
                </div>
              </div>
              <div class="text-right">
                <div class="font-semibold" :class="getPerformanceClass(campaign.delivery_rate || campaign.delivered || 0)">
                  {{ getPerformanceLabel(campaign.delivery_rate || campaign.delivered || 0) }}
                </div>
              </div>
            </div>
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
  ChartBarIcon,
  PaperAirplaneIcon,
  CheckCircleIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  RocketLaunchIcon,
  ClockIcon,
  CheckBadgeIcon,
  TrophyIcon
} from '@heroicons/vue/24/outline'
import { campaignService } from '@/services/campaignService'

interface Campaign {
  id: number
  name: string
  status: string
  messages_sent?: number
  sent?: number
  delivery_rate?: number
  delivered?: number
  created_at?: string
}

const loading = ref(true)
const campaigns = ref<Campaign[]>([])

const activeCampaignsCount = computed(() => {
  return campaigns.value.filter(c => c.status === 'Actif' || c.status === 'active').length
})

const scheduledCampaignsCount = computed(() => {
  return campaigns.value.filter(c => c.status === 'Planifié' || c.status === 'scheduled').length
})

const completedCampaignsCount = computed(() => {
  return campaigns.value.filter(c => c.status === 'Terminé' || c.status === 'completed').length
})

const averageDeliveryRate = computed(() => {
  const campaignsWithRate = campaigns.value.filter(c => c.delivery_rate || c.delivered)
  if (campaignsWithRate.length === 0) return '0.0'
  const avg = campaignsWithRate.reduce((sum, c) => sum + (c.delivery_rate || c.delivered || 0), 0) / campaignsWithRate.length
  return avg.toFixed(1)
})

const deliveryRateLabel = computed(() => {
  const rate = parseFloat(averageDeliveryRate.value)
  if (rate >= 98) return 'Excellent taux'
  if (rate >= 95) return 'Très bon taux'
  if (rate >= 90) return 'Bon taux'
  return 'À améliorer'
})

const weeklyStats = computed(() => {
  const now = new Date()
  const oneWeekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
  const twoWeeksAgo = new Date(now.getTime() - 14 * 24 * 60 * 60 * 1000)

  const thisWeek = campaigns.value
    .filter(c => c.created_at && new Date(c.created_at) >= oneWeekAgo)
    .reduce((sum, c) => sum + (c.messages_sent || c.sent || 0), 0)

  const lastWeek = campaigns.value
    .filter(c => c.created_at && new Date(c.created_at) >= twoWeeksAgo && new Date(c.created_at) < oneWeekAgo)
    .reduce((sum, c) => sum + (c.messages_sent || c.sent || 0), 0)

  const trend = lastWeek > 0 ? ((thisWeek - lastWeek) / lastWeek * 100).toFixed(1) : '0'

  return {
    total: formatNumber(thisWeek),
    trend: parseFloat(trend)
  }
})

function formatNumber(num: number): string {
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`
  }
  return num.toString()
}

function getStatusClass(status: string): string {
  const statusLower = status.toLowerCase()
  if (statusLower === 'actif' || statusLower === 'active') {
    return 'bg-success/10 text-success'
  }
  if (statusLower === 'planifié' || statusLower === 'scheduled') {
    return 'bg-warning/10 text-warning'
  }
  if (statusLower === 'terminé' || statusLower === 'completed') {
    return 'bg-muted text-muted-foreground'
  }
  return 'bg-muted text-muted-foreground'
}

function getPerformanceLabel(rate: number): string {
  if (rate >= 98) return 'Excellent'
  if (rate >= 95) return 'Très bon'
  if (rate >= 90) return 'Bon'
  if (rate >= 80) return 'Moyen'
  return 'À améliorer'
}

function getPerformanceClass(rate: number): string {
  if (rate >= 95) return 'text-success'
  if (rate >= 90) return 'text-primary'
  if (rate >= 80) return 'text-warning'
  return 'text-destructive'
}

async function loadCampaigns() {
  try {
    loading.value = true
    campaigns.value = await campaignService.getAll()
  } catch (error) {
    console.error('Error loading campaigns:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCampaigns()
})
</script>
