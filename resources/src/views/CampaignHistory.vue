<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-4 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
          <div class="flex items-center gap-2">
            <ClockIcon class="w-6 h-6 sm:w-8 sm:h-8 text-primary" />
            <h1 class="text-xl sm:text-3xl font-bold">Historique des campagnes</h1>
          </div>
          <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Consultez toutes vos campagnes envoyées</p>
        </div>
        <router-link
          to="/campaigns/create"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span class="hidden sm:inline">Nouvelle campagne</span>
          <span class="sm:hidden">Nouvelle</span>
        </router-link>
      </div>

      <!-- Filters -->
      <div class="mb-4 sm:mb-6 grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4">
        <div class="col-span-2 md:col-span-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher..."
            class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
        </div>
        <div>
          <select
            v-model="filters.status"
            class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-2 sm:px-3 py-2 text-xs sm:text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="">Tous statuts</option>
            <option value="draft">Brouillon</option>
            <option value="scheduled">Planifié</option>
            <option value="sending">En cours</option>
            <option value="completed">Terminé</option>
            <option value="failed">Échoué</option>
            <option value="cancelled">Annulé</option>
          </select>
        </div>
        <div>
          <input
            v-model="filters.dateFrom"
            type="date"
            placeholder="Date de début"
            class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-2 sm:px-3 py-2 text-xs sm:text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
        </div>
        <div>
          <input
            v-model="filters.dateTo"
            type="date"
            placeholder="Date de fin"
            class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-2 sm:px-3 py-2 text-xs sm:text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredCampaigns.length === 0" class="text-center py-16">
        <FolderOpenIcon class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
        <h3 class="text-lg font-semibold mb-2">Aucune campagne trouvée</h3>
        <p class="text-muted-foreground mb-6">{{ filters.search || filters.status ? 'Essayez de modifier vos filtres' : 'Commencez par créer votre première campagne' }}</p>
        <router-link
          to="/campaigns/create"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Créer une campagne</span>
        </router-link>
      </div>

      <!-- Campaigns List -->
      <div v-else class="space-y-3 sm:space-y-4">
        <div
          v-for="campaign in filteredCampaigns"
          :key="campaign.id"
          class="rounded-lg border bg-card shadow-sm p-4 sm:p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 sm:gap-4 mb-3 sm:mb-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-2">
                <h3 class="text-base sm:text-lg font-semibold truncate">{{ campaign.name }}</h3>
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0"
                  :class="getStatusClass(campaign.status)"
                >
                  {{ getStatusLabel(campaign.status) }}
                </span>
              </div>
              <p class="text-xs sm:text-sm text-muted-foreground line-clamp-2">{{ campaign.message }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button
                @click="cloneCampaign(campaign)"
                :disabled="cloning === campaign.id"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 sm:h-9 px-2 sm:px-3"
                title="Dupliquer"
              >
                <DocumentDuplicateIcon v-if="cloning !== campaign.id" class="w-4 h-4" />
                <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
              </button>
              <button
                @click="viewDetails(campaign)"
                class="inline-flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 sm:h-9 px-2 sm:px-3"
              >
                <EyeIcon class="w-4 h-4" />
                <span class="hidden sm:inline">Détails</span>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 pt-3 sm:pt-4 border-t">
            <div>
              <p class="text-xs text-muted-foreground mb-0.5 sm:mb-1">Date</p>
              <p class="text-xs sm:text-sm font-medium flex items-center gap-1">
                <CalendarIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground hidden sm:block" />
                <span class="truncate">{{ formatDate(campaign.sent_at || campaign.scheduled_at) }}</span>
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-0.5 sm:mb-1">Destinataires</p>
              <p class="text-xs sm:text-sm font-medium flex items-center gap-1">
                <UsersIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground hidden sm:block" />
                {{ campaign.recipients_count }}
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-0.5 sm:mb-1">SMS</p>
              <p class="text-xs sm:text-sm font-medium flex items-center gap-1">
                <ChatBubbleLeftIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground hidden sm:block" />
                {{ campaign.sms_count }}
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-0.5 sm:mb-1">Livraison</p>
              <p class="text-xs sm:text-sm font-medium flex items-center gap-1">
                <ChartBarIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground hidden sm:block" />
                {{ campaign.delivery_rate }}%
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-0.5 sm:mb-1">Coût</p>
              <p class="text-xs sm:text-sm font-bold text-primary flex items-center gap-1">
                <BanknotesIcon class="w-3 h-3 sm:w-4 sm:h-4 hidden sm:block" />
                {{ campaign.cost }} XAF
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-4 sm:mt-6 flex justify-center gap-2">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 sm:h-9 px-2 sm:px-3"
        >
          <ChevronLeftIcon class="w-4 h-4" />
        </button>
        <span class="inline-flex items-center px-2 sm:px-4 text-xs sm:text-sm">
          {{ currentPage }}/{{ totalPages }}
        </span>
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 sm:h-9 px-2 sm:px-3"
        >
          <ChevronRightIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Campaign Details Modal -->
    <div
      v-if="selectedCampaign"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4"
      @click.self="selectedCampaign = null"
    >
      <div class="bg-background rounded-lg shadow-xl w-full max-w-sm sm:max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-4 sm:p-6 border-b flex items-center justify-between">
          <h2 class="text-lg sm:text-2xl font-bold">Détails de la campagne</h2>
          <button
            @click="selectedCampaign = null"
            class="text-muted-foreground hover:text-foreground transition-colors"
          >
            <XMarkIcon class="w-5 h-5 sm:w-6 sm:h-6" />
          </button>
        </div>
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
          <div>
            <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Nom de la campagne</h3>
            <p class="text-sm text-muted-foreground">{{ selectedCampaign.name }}</p>
          </div>
          <div>
            <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Message</h3>
            <div class="p-3 sm:p-4 bg-muted/50 rounded-lg font-mono text-xs sm:text-sm whitespace-pre-wrap">{{ selectedCampaign.message }}</div>
          </div>
          <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <div>
              <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Statut</h3>
              <span
                class="inline-block px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs sm:text-sm font-medium"
                :class="getStatusClass(selectedCampaign.status)"
              >
                {{ getStatusLabel(selectedCampaign.status) }}
              </span>
            </div>
            <div>
              <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Date d'envoi</h3>
              <p class="text-xs sm:text-sm text-muted-foreground">{{ formatDate(selectedCampaign.sent_at || selectedCampaign.scheduled_at) }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <div>
              <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Destinataires</h3>
              <p class="text-xl sm:text-2xl font-bold text-primary">{{ selectedCampaign.recipients_count }}</p>
            </div>
            <div>
              <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">SMS envoyés</h3>
              <p class="text-xl sm:text-2xl font-bold text-primary">{{ selectedCampaign.sms_count }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <div>
              <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Taux livraison</h3>
              <p class="text-xl sm:text-2xl font-bold">{{ selectedCampaign.delivery_rate }}%</p>
            </div>
            <div>
              <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Coût total</h3>
              <p class="text-xl sm:text-2xl font-bold text-primary">{{ selectedCampaign.cost }} XAF</p>
            </div>
          </div>
          <div v-if="selectedCampaign.groups && selectedCampaign.groups.length > 0">
            <h3 class="font-semibold text-sm sm:text-base mb-1 sm:mb-2">Groupes ciblés</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="group in selectedCampaign.groups"
                :key="group.id"
                class="px-2 sm:px-3 py-0.5 sm:py-1 bg-primary/10 text-primary rounded-full text-xs sm:text-sm"
              >
                {{ group.name }}
              </span>
            </div>
          </div>
        </div>
        <div class="p-4 sm:p-6 border-t flex justify-end">
          <button
            @click="selectedCampaign = null"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 sm:h-10 px-3 sm:px-4 py-2"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import { campaignHistoryService } from '@/services/campaignHistoryService'
import { campaignService } from '@/services/campaignService'
import { showSuccess, showError } from '@/utils/notifications'
import {
  ClockIcon,
  PlusIcon,
  FolderOpenIcon,
  EyeIcon,
  CalendarIcon,
  UsersIcon,
  ChatBubbleLeftIcon,
  ChartBarIcon,
  BanknotesIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  XMarkIcon,
  DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'

interface Campaign {
  id: number
  name: string
  message: string
  status: 'completed' | 'scheduled' | 'cancelled' | 'failed'
  sent_at?: string
  scheduled_at?: string
  recipients_count: number
  sms_count: number
  delivery_rate: number
  cost: number
  groups?: { id: number; name: string }[]
}

const router = useRouter()
const campaigns = ref<Campaign[]>([])
const loading = ref(false)
const cloning = ref<number | null>(null)
const selectedCampaign = ref<Campaign | null>(null)
const currentPage = ref(1)
const itemsPerPage = 10

const filters = ref({
  search: '',
  status: '',
  dateFrom: '',
  dateTo: ''
})

const filteredCampaigns = computed(() => {
  let filtered = campaigns.value

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(c =>
      c.name.toLowerCase().includes(search) ||
      c.message.toLowerCase().includes(search)
    )
  }

  if (filters.value.status) {
    filtered = filtered.filter(c => normalizeStatus(c.status) === filters.value.status)
  }

  if (filters.value.dateFrom) {
    filtered = filtered.filter(c => {
      const date = c.sent_at || c.scheduled_at
      return date && date >= filters.value.dateFrom
    })
  }

  if (filters.value.dateTo) {
    filtered = filtered.filter(c => {
      const date = c.sent_at || c.scheduled_at
      return date && date <= filters.value.dateTo
    })
  }

  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filtered.slice(start, end)
})

const totalPages = computed(() => {
  let filtered = campaigns.value
  if (filters.value.search || filters.value.status || filters.value.dateFrom || filters.value.dateTo) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(c => {
      const matchSearch = !filters.value.search ||
        c.name.toLowerCase().includes(search) ||
        c.message.toLowerCase().includes(search)
      const matchStatus = !filters.value.status || normalizeStatus(c.status) === filters.value.status
      const matchDateFrom = !filters.value.dateFrom || (c.sent_at || c.scheduled_at || '') >= filters.value.dateFrom
      const matchDateTo = !filters.value.dateTo || (c.sent_at || c.scheduled_at || '') <= filters.value.dateTo
      return matchSearch && matchStatus && matchDateFrom && matchDateTo
    })
  }
  return Math.ceil(filtered.length / itemsPerPage)
})

function normalizeStatus(status: string): string {
  const statusLower = status?.toLowerCase() || ''
  // Map legacy French statuses to new English ones
  if (statusLower === 'actif' || statusLower === 'sending') return 'sending'
  if (statusLower === 'terminé' || statusLower === 'termine') return 'completed'
  if (statusLower === 'planifié' || statusLower === 'planifie') return 'scheduled'
  return statusLower
}

function getStatusClass(status: string) {
  const normalized = normalizeStatus(status)
  const classes: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    scheduled: 'bg-primary/10 text-primary',
    sending: 'bg-warning/10 text-warning',
    completed: 'bg-success/10 text-success',
    failed: 'bg-destructive/10 text-destructive',
    cancelled: 'bg-muted text-muted-foreground'
  }
  return classes[normalized] || 'bg-muted text-muted-foreground'
}

function getStatusLabel(status: string) {
  const normalized = normalizeStatus(status)
  const labels: Record<string, string> = {
    draft: 'Brouillon',
    scheduled: 'Planifié',
    sending: 'En cours',
    completed: 'Terminé',
    failed: 'Échoué',
    cancelled: 'Annulé'
  }
  return labels[normalized] || status
}

function formatDate(date: string | undefined) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function viewDetails(campaign: Campaign) {
  selectedCampaign.value = campaign
}

async function cloneCampaign(campaign: Campaign) {
  cloning.value = campaign.id
  try {
    const clonedCampaign = await campaignService.clone(campaign.id)
    showSuccess(`Campagne "${clonedCampaign.name}" créée avec succès`)
    router.push('/campaign/create')
  } catch (error: any) {
    console.error('Error cloning campaign:', error)
    showError(error.response?.data?.message || 'Erreur lors du clonage de la campagne')
  } finally {
    cloning.value = null
  }
}

async function loadCampaigns() {
  loading.value = true
  try {
    campaigns.value = await campaignHistoryService.getAll()
  } catch (error: any) {
    console.error('Error loading campaigns:', error)
    showError(error.response?.data?.message || 'Erreur lors du chargement des campagnes')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCampaigns()
})
</script>
