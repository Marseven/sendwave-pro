<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground">Tableau de bord</h1>
        <p class="text-muted-foreground mt-2">Bienvenue {{ user?.name }}</p>
      </div>

      <!-- Statistiques principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Total Contacts</h3>
            <UsersIcon class="w-8 h-8 text-primary" />
          </div>
          <p class="text-3xl font-bold mt-2">{{ totalContacts }}</p>
          <p class="text-xs text-muted-foreground mt-2">&nbsp;</p>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Campagnes Actives</h3>
            <ChartBarIcon class="w-8 h-8 text-primary" />
          </div>
          <p class="text-3xl font-bold mt-2">{{ activeCampaigns }}</p>
          <p class="text-xs text-muted-foreground mt-2">{{ scheduledCampaigns }} planifiées</p>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Messages Envoyés</h3>
            <PaperAirplaneIcon class="w-8 h-8 text-primary" />
          </div>
          <p class="text-3xl font-bold mt-2">{{ totalMessagesSent }}</p>
          <p class="text-xs text-muted-foreground mt-2">&nbsp;</p>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Taux de Livraison</h3>
            <CheckCircleIcon class="w-8 h-8 text-success" />
          </div>
          <p class="text-3xl font-bold mt-2">{{ averageDeliveryRate }}%</p>
          <p class="text-xs" :class="deliveryRateClass">{{ deliveryRateLabel }}</p>
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
  </MainLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/components/MainLayout.vue'
import {
  UsersIcon,
  ChartBarIcon,
  PaperAirplaneIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline'
import { contactService } from '@/services/contactService'
import { campaignService } from '@/services/campaignService'

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const recentCampaigns = ref<any[]>([])
const allCampaigns = ref<any[]>([])
const contacts = ref<any[]>([])
const stats = ref<any>(null)
const loading = ref(false)

const totalContacts = computed(() => contacts.value.length)
const activeCampaigns = computed(() => allCampaigns.value.filter(c => c.status === 'active' || c.status === 'Actif').length)
const scheduledCampaigns = computed(() => allCampaigns.value.filter(c => c.status === 'scheduled' || c.status === 'Planifié').length)

const totalMessagesSent = computed(() => {
  const total = allCampaigns.value.reduce((sum, c) => sum + (c.messages_sent || c.sent || 0), 0)
  if (total >= 1000) return `${(total / 1000).toFixed(1)}K`
  return total.toString()
})

const averageDeliveryRate = computed(() => {
  const campaignsWithRate = allCampaigns.value.filter(c => c.delivery_rate || c.delivered)
  if (campaignsWithRate.length === 0) return 0
  const avg = campaignsWithRate.reduce((sum, c) => sum + (c.delivery_rate || c.delivered || 0), 0) / campaignsWithRate.length
  return avg.toFixed(1)
})

const deliveryRateClass = computed(() => {
  const rate = parseFloat(averageDeliveryRate.value)
  if (rate >= 95) return 'text-success'
  if (rate >= 85) return 'text-warning'
  return 'text-destructive'
})

const deliveryRateLabel = computed(() => {
  const rate = parseFloat(averageDeliveryRate.value)
  if (rate >= 95) return 'Excellent'
  if (rate >= 85) return 'Bon'
  if (rate > 0) return 'À améliorer'
  return 'Aucune donnée'
})

const recentActivities = ref<any[]>([])

async function loadDashboardData() {
  loading.value = true
  try {
    const [campaignsData, contactsData] = await Promise.all([
      campaignService.getAll(),
      contactService.getAll()
    ])
    allCampaigns.value = campaignsData
    recentCampaigns.value = campaignsData.slice(0, 3)
    contacts.value = contactsData

    // Générer les activités récentes à partir des vraies données
    recentActivities.value = generateRecentActivities(campaignsData, contactsData)
  } catch (err) {
    console.error('Error loading dashboard data:', err)
  } finally {
    loading.value = false
  }
}

function generateRecentActivities(campaigns: any[], contacts: any[]): any[] {
  const activities: any[] = []

  // Ajouter les campagnes récentes
  campaigns.slice(0, 2).forEach(campaign => {
    if (campaign.messages_sent || campaign.sent) {
      activities.push({
        id: `campaign-${campaign.id}`,
        icon: PaperAirplaneIcon,
        title: `Campagne "${campaign.name}" envoyée à ${campaign.messages_sent || campaign.sent} contacts`,
        time: formatRelativeTime(campaign.created_at)
      })
    }
  })

  // Ajouter les contacts récents
  const recentContacts = contacts.slice(0, 1)
  if (recentContacts.length > 0) {
    activities.push({
      id: 'contacts-recent',
      icon: UsersIcon,
      title: `${recentContacts.length} nouveau(x) contact(s) importé(s)`,
      time: formatRelativeTime(recentContacts[0].created_at)
    })
  }

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
