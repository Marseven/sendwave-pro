<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2">
            <ChartBarIcon class="w-8 h-8 text-primary" />
            <h1 class="text-3xl font-bold">Rapports & Statistiques</h1>
          </div>
          <p class="text-muted-foreground mt-2">Analysez les performances de vos campagnes</p>
        </div>
        <div class="flex gap-3">
          <button
            @click="exportReport('pdf')"
            :disabled="exporting"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            <span>Exporter PDF</span>
          </button>
          <button
            @click="exportReport('excel')"
            :disabled="exporting"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            <span>Exporter Excel</span>
          </button>
          <button
            @click="exportReport('csv')"
            :disabled="exporting"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            <span>Exporter CSV</span>
          </button>
        </div>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else>
        <!-- Période de sélection -->
        <div class="rounded-lg border bg-card p-4 mb-6">
          <div class="flex items-center gap-4">
            <div class="flex-1">
              <label class="text-sm font-medium mb-1 block">Date de début</label>
              <input
                v-model="startDate"
                type="date"
                @change="loadDashboard"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>
            <div class="flex-1">
              <label class="text-sm font-medium mb-1 block">Date de fin</label>
              <input
                v-model="endDate"
                type="date"
                @change="loadDashboard"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>
          </div>
        </div>

        <!-- Vue d'ensemble -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <PaperAirplaneIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold text-sm">SMS Envoyés</h3>
            </div>
            <div class="text-3xl font-bold text-primary mb-2">{{ formatNumber(dashboard?.overview.sms_sent || 0) }}</div>
            <div class="flex items-center gap-2">
              <ArrowTrendingUpIcon v-if="dashboard?.trends.sms_sent_change > 0" class="w-4 h-4 text-success" />
              <ArrowTrendingDownIcon v-else class="w-4 h-4 text-destructive" />
              <p class="text-xs" :class="dashboard?.trends.sms_sent_change > 0 ? 'text-success' : 'text-destructive'">
                {{ dashboard?.trends.sms_sent_change > 0 ? '+' : '' }}{{ dashboard?.trends.sms_sent_change.toFixed(1) }}%
              </p>
            </div>
          </div>

          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <CheckCircleIcon class="w-5 h-5 text-success" />
              <h3 class="font-semibold text-sm">Taux de livraison</h3>
            </div>
            <div class="text-3xl font-bold text-success mb-2">{{ dashboard?.overview.success_rate.toFixed(1) }}%</div>
            <p class="text-xs text-muted-foreground">{{ dashboard?.overview.sms_delivered.toLocaleString() }} livrés</p>
          </div>

          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <RocketLaunchIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold text-sm">Campagnes</h3>
            </div>
            <div class="text-3xl font-bold mb-2">{{ dashboard?.overview.campaigns_executed || 0 }}</div>
            <div class="flex items-center gap-2">
              <ArrowTrendingUpIcon v-if="dashboard?.trends.campaigns_change > 0" class="w-4 h-4 text-success" />
              <ArrowTrendingDownIcon v-else class="w-4 h-4 text-destructive" />
              <p class="text-xs" :class="dashboard?.trends.campaigns_change > 0 ? 'text-success' : 'text-destructive'">
                {{ dashboard?.trends.campaigns_change > 0 ? '+' : '' }}{{ dashboard?.trends.campaigns_change.toFixed(1) }}%
              </p>
            </div>
          </div>

          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-4">
              <CheckBadgeIcon class="w-5 h-5 text-warning" />
              <h3 class="font-semibold text-sm">Coût Total</h3>
            </div>
            <div class="text-3xl font-bold mb-2">{{ dashboard?.overview.total_cost.toLocaleString() }} FCFA</div>
            <p class="text-xs text-muted-foreground">{{ dashboard?.overview.average_cost_per_sms.toFixed(2) }} FCFA/SMS</p>
          </div>
        </div>

        <!-- Providers Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-6">
              <ChartBarIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold">Distribution par opérateur</h3>
            </div>
            <div class="space-y-4">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">Airtel</span>
                  <span class="text-sm font-semibold">{{ dashboard?.providers.airtel.percentage.toFixed(1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                  <div
                    class="bg-red-500 h-3 rounded-full transition-all"
                    :style="{ width: dashboard?.providers.airtel.percentage + '%' }"
                  ></div>
                </div>
                <p class="text-xs text-muted-foreground mt-1">{{ dashboard?.providers.airtel.count.toLocaleString() }} SMS</p>
              </div>
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">Moov</span>
                  <span class="text-sm font-semibold">{{ dashboard?.providers.moov.percentage.toFixed(1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                  <div
                    class="bg-blue-500 h-3 rounded-full transition-all"
                    :style="{ width: dashboard?.providers.moov.percentage + '%' }"
                  ></div>
                </div>
                <p class="text-xs text-muted-foreground mt-1">{{ dashboard?.providers.moov.count.toLocaleString() }} SMS</p>
              </div>
            </div>
          </div>

          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center gap-2 mb-6">
              <TrophyIcon class="w-5 h-5 text-primary" />
              <h3 class="font-semibold">Top Campagnes</h3>
            </div>
            <div v-if="dashboard?.campaigns.length === 0" class="text-center py-8">
              <ChartBarIcon class="w-12 h-12 text-muted-foreground mx-auto mb-2" />
              <p class="text-sm text-muted-foreground">Aucune campagne</p>
            </div>
            <div v-else class="space-y-3">
              <div v-for="campaign in dashboard?.campaigns.slice(0, 5)" :key="campaign.id" class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                <div class="flex-1">
                  <div class="font-medium text-sm">{{ campaign.name }}</div>
                  <div class="text-xs text-muted-foreground">{{ campaign.messages_sent.toLocaleString() }} envois</div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full" :class="getStatusClass(campaign.status)">
                  {{ campaign.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Analyse des coûts -->
        <div class="rounded-lg border bg-card p-6 mb-8">
          <div class="flex items-center gap-2 mb-6">
            <ChartBarIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Analyse des coûts</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <div class="text-sm text-muted-foreground mb-1">Coût Airtel</div>
              <div class="text-2xl font-bold">{{ dashboard?.cost_analysis.airtel_cost.toLocaleString() }} FCFA</div>
            </div>
            <div>
              <div class="text-sm text-muted-foreground mb-1">Coût Moov</div>
              <div class="text-2xl font-bold">{{ dashboard?.cost_analysis.moov_cost.toLocaleString() }} FCFA</div>
            </div>
            <div>
              <div class="text-sm text-muted-foreground mb-1">Moyenne journalière</div>
              <div class="text-2xl font-bold">{{ dashboard?.cost_analysis.average_daily_cost.toLocaleString() }} FCFA</div>
            </div>
          </div>
        </div>

        <!-- Distribution horaire -->
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-6">
            <ClockIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Distribution horaire des envois</h3>
          </div>
          <div v-if="dashboard?.hourly_distribution.length === 0" class="text-center py-12">
            <p class="text-muted-foreground">Aucune donnée disponible</p>
          </div>
          <div v-else class="grid grid-cols-12 gap-2">
            <div
              v-for="hour in dashboard?.hourly_distribution"
              :key="hour.hour"
              class="flex flex-col items-center"
            >
              <div
                class="w-full bg-primary/20 rounded-t"
                :style="{ height: getBarHeight(hour.count) + 'px' }"
              >
                <div
                  class="w-full bg-primary rounded-t transition-all"
                  :style="{ height: '100%' }"
                  :title="`${hour.count} SMS à ${hour.hour}h`"
                ></div>
              </div>
              <div class="text-xs text-muted-foreground mt-1">{{ hour.hour }}h</div>
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
  TrophyIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import analyticsService, { type AnalyticsDashboard } from '@/services/analyticsService'
import { showSuccess, showError } from '@/utils/notifications'

const loading = ref(true)
const exporting = ref(false)
const dashboard = ref<AnalyticsDashboard | null>(null)

// Date range for filtering
const startDate = ref('')
const endDate = ref('')

// Set default date range (last 30 days)
function setDefaultDateRange() {
  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - 30)

  endDate.value = end.toISOString().split('T')[0]
  startDate.value = start.toISOString().split('T')[0]
}

async function loadDashboard() {
  try {
    loading.value = true
    const response = await analyticsService.getDashboard('month')
    dashboard.value = response.data
  } catch (error: any) {
    console.error('Error loading dashboard:', error)
    showError(error.response?.data?.message || 'Erreur lors du chargement des statistiques')
  } finally {
    loading.value = false
  }
}

async function exportReport(format: 'pdf' | 'excel' | 'csv') {
  if (!startDate.value || !endDate.value) {
    showError('Veuillez sélectionner une période')
    return
  }

  try {
    exporting.value = true
    let blob: Blob
    let filename: string

    switch (format) {
      case 'pdf':
        blob = await analyticsService.exportPdf(startDate.value, endDate.value)
        filename = `rapport_${startDate.value}_${endDate.value}.pdf`
        break
      case 'excel':
        blob = await analyticsService.exportExcel(startDate.value, endDate.value)
        filename = `rapport_${startDate.value}_${endDate.value}.xlsx`
        break
      case 'csv':
        blob = await analyticsService.exportCsv(startDate.value, endDate.value)
        filename = `rapport_${startDate.value}_${endDate.value}.csv`
        break
    }

    analyticsService.downloadFile(blob, filename)
    showSuccess(`Rapport ${format.toUpperCase()} téléchargé avec succès`)
  } catch (error: any) {
    console.error('Error exporting report:', error)
    showError(error.response?.data?.message || 'Erreur lors de l\'export')
  } finally {
    exporting.value = false
  }
}

function formatNumber(num: number): string {
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M`
  }
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`
  }
  return num.toString()
}

function getStatusClass(status: string): string {
  const statusLower = status.toLowerCase()
  if (statusLower === 'actif' || statusLower === 'active' || statusLower === 'completed') {
    return 'bg-success/10 text-success'
  }
  if (statusLower === 'planifié' || statusLower === 'scheduled' || statusLower === 'pending') {
    return 'bg-warning/10 text-warning'
  }
  if (statusLower === 'terminé' || statusLower === 'sent') {
    return 'bg-blue-100 text-blue-700'
  }
  return 'bg-muted text-muted-foreground'
}

function getBarHeight(count: number): number {
  if (!dashboard.value?.hourly_distribution.length) return 0
  const maxCount = Math.max(...dashboard.value.hourly_distribution.map(h => h.count))
  if (maxCount === 0) return 0
  return Math.max(20, (count / maxCount) * 100)
}

onMounted(() => {
  setDefaultDateRange()
  loadDashboard()
})
</script>
