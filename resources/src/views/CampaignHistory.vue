<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2">
            <ClockIcon class="w-8 h-8 text-primary" />
            <h1 class="text-3xl font-bold">Historique des campagnes</h1>
          </div>
          <p class="text-muted-foreground mt-2">Consultez toutes vos campagnes envoyées</p>
        </div>
        <router-link
          to="/campaigns/create"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Nouvelle campagne</span>
        </router-link>
      </div>

      <!-- Filters -->
      <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher..."
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
        </div>
        <div>
          <select
            v-model="filters.status"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="">Tous les statuts</option>
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
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
        </div>
        <div>
          <input
            v-model="filters.dateTo"
            type="date"
            placeholder="Date de fin"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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
      <div v-else class="space-y-4">
        <div
          v-for="campaign in filteredCampaigns"
          :key="campaign.id"
          class="rounded-lg border bg-card shadow-sm p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg font-semibold">{{ campaign.name }}</h3>
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getStatusClass(campaign.status)"
                >
                  {{ getStatusLabel(campaign.status) }}
                </span>
              </div>
              <p class="text-sm text-muted-foreground line-clamp-2">{{ campaign.message }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="cloneCampaign(campaign)"
                :disabled="cloning === campaign.id"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
                title="Dupliquer"
              >
                <DocumentDuplicateIcon v-if="cloning !== campaign.id" class="w-4 h-4" />
                <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
              </button>
              <button
                @click="viewDetails(campaign)"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
              >
                <EyeIcon class="w-4 h-4" />
                <span>Détails</span>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-5 gap-4 pt-4 border-t">
            <div>
              <p class="text-xs text-muted-foreground mb-1">Date d'envoi</p>
              <p class="text-sm font-medium flex items-center gap-1.5">
                <CalendarIcon class="w-4 h-4 text-muted-foreground" />
                {{ formatDate(campaign.sent_at || campaign.scheduled_at) }}
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-1">Destinataires</p>
              <p class="text-sm font-medium flex items-center gap-1.5">
                <UsersIcon class="w-4 h-4 text-muted-foreground" />
                {{ campaign.recipients_count }}
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-1">SMS envoyés</p>
              <p class="text-sm font-medium flex items-center gap-1.5">
                <ChatBubbleLeftIcon class="w-4 h-4 text-muted-foreground" />
                {{ campaign.sms_count }}
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-1">Taux de livraison</p>
              <p class="text-sm font-medium flex items-center gap-1.5">
                <ChartBarIcon class="w-4 h-4 text-muted-foreground" />
                {{ campaign.delivery_rate }}%
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-1">Coût total</p>
              <p class="text-sm font-bold text-primary flex items-center gap-1.5">
                <BanknotesIcon class="w-4 h-4" />
                {{ campaign.cost }} XAF
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-6 flex justify-center gap-2">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
        >
          <ChevronLeftIcon class="w-4 h-4" />
        </button>
        <span class="inline-flex items-center px-4 text-sm">
          Page {{ currentPage }} sur {{ totalPages }}
        </span>
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
        >
          <ChevronRightIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Campaign Details Modal -->
    <div
      v-if="selectedCampaign"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="selectedCampaign = null"
    >
      <div class="bg-background rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
          <h2 class="text-2xl font-bold">Détails de la campagne</h2>
          <button
            @click="selectedCampaign = null"
            class="text-muted-foreground hover:text-foreground transition-colors"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
        <div class="p-6 space-y-6">
          <div>
            <h3 class="font-semibold mb-2">Nom de la campagne</h3>
            <p class="text-muted-foreground">{{ selectedCampaign.name }}</p>
          </div>
          <div>
            <h3 class="font-semibold mb-2">Message</h3>
            <div class="p-4 bg-muted/50 rounded-lg font-mono text-sm whitespace-pre-wrap">{{ selectedCampaign.message }}</div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h3 class="font-semibold mb-2">Statut</h3>
              <span
                class="inline-block px-3 py-1 rounded-full text-sm font-medium"
                :class="getStatusClass(selectedCampaign.status)"
              >
                {{ getStatusLabel(selectedCampaign.status) }}
              </span>
            </div>
            <div>
              <h3 class="font-semibold mb-2">Date d'envoi</h3>
              <p class="text-muted-foreground">{{ formatDate(selectedCampaign.sent_at || selectedCampaign.scheduled_at) }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h3 class="font-semibold mb-2">Destinataires</h3>
              <p class="text-2xl font-bold text-primary">{{ selectedCampaign.recipients_count }}</p>
            </div>
            <div>
              <h3 class="font-semibold mb-2">SMS envoyés</h3>
              <p class="text-2xl font-bold text-primary">{{ selectedCampaign.sms_count }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h3 class="font-semibold mb-2">Taux de livraison</h3>
              <p class="text-2xl font-bold">{{ selectedCampaign.delivery_rate }}%</p>
            </div>
            <div>
              <h3 class="font-semibold mb-2">Coût total</h3>
              <p class="text-2xl font-bold text-primary">{{ selectedCampaign.cost }} XAF</p>
            </div>
          </div>
          <div v-if="selectedCampaign.groups && selectedCampaign.groups.length > 0">
            <h3 class="font-semibold mb-2">Groupes ciblés</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="group in selectedCampaign.groups"
                :key="group.id"
                class="px-3 py-1 bg-primary/10 text-primary rounded-full text-sm"
              >
                {{ group.name }}
              </span>
            </div>
          </div>
        </div>
        <div class="p-6 border-t flex justify-end">
          <button
            @click="selectedCampaign = null"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
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
