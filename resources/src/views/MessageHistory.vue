<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2">
            <ChatBubbleLeftIcon class="w-8 h-8 text-primary" />
            <h1 class="text-3xl font-bold">Historique des messages</h1>
          </div>
          <p class="text-muted-foreground mt-2">Consultez tous les messages SMS envoyés</p>
        </div>
        <router-link
          to="/send-message"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <PaperAirplaneIcon class="w-4 h-4" />
          <span>Envoyer un message</span>
        </router-link>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground mb-1">Total envoyé</p>
              <p class="text-2xl font-bold">{{ stats.total }}</p>
            </div>
            <ChatBubbleLeftIcon class="w-10 h-10 text-primary/20" />
          </div>
        </div>
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground mb-1">Livrés</p>
              <p class="text-2xl font-bold text-success">{{ stats.delivered }}</p>
            </div>
            <CheckCircleIcon class="w-10 h-10 text-success/20" />
          </div>
        </div>
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground mb-1">Échecs</p>
              <p class="text-2xl font-bold text-destructive">{{ stats.failed }}</p>
            </div>
            <ExclamationCircleIcon class="w-10 h-10 text-destructive/20" />
          </div>
        </div>
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground mb-1">Coût total</p>
              <p class="text-2xl font-bold text-primary">{{ stats.totalCost }} XAF</p>
            </div>
            <BanknotesIcon class="w-10 h-10 text-primary/20" />
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher destinataire..."
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
        </div>
        <div>
          <select
            v-model="filters.status"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="">Tous les statuts</option>
            <option value="delivered">Livré</option>
            <option value="pending">En attente</option>
            <option value="failed">Échoué</option>
          </select>
        </div>
        <div>
          <select
            v-model="filters.type"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="">Tous les types</option>
            <option value="immediate">Immédiat</option>
            <option value="campaign">Campagne</option>
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
      <div v-else-if="filteredMessages.length === 0" class="text-center py-16">
        <InboxIcon class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
        <h3 class="text-lg font-semibold mb-2">Aucun message trouvé</h3>
        <p class="text-muted-foreground mb-6">{{ filters.search || filters.status ? 'Essayez de modifier vos filtres' : 'Commencez par envoyer votre premier message' }}</p>
        <router-link
          to="/send-message"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <PaperAirplaneIcon class="w-4 h-4" />
          <span>Envoyer un message</span>
        </router-link>
      </div>

      <!-- Messages Table -->
      <div v-else class="rounded-lg border bg-card shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-muted/50">
              <tr>
                <th class="text-left p-4 text-sm font-semibold">Date</th>
                <th class="text-left p-4 text-sm font-semibold">Destinataire</th>
                <th class="text-left p-4 text-sm font-semibold">Message</th>
                <th class="text-left p-4 text-sm font-semibold">Type</th>
                <th class="text-left p-4 text-sm font-semibold">Statut</th>
                <th class="text-left p-4 text-sm font-semibold">Coût</th>
                <th class="text-left p-4 text-sm font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr
                v-for="message in filteredMessages"
                :key="message.id"
                class="hover:bg-muted/30 transition-colors"
              >
                <td class="p-4 text-sm">
                  <div class="flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-muted-foreground" />
                    <div>
                      <div>{{ formatDate(message.sent_at) }}</div>
                      <div class="text-xs text-muted-foreground">{{ formatTime(message.sent_at) }}</div>
                    </div>
                  </div>
                </td>
                <td class="p-4 text-sm">
                  <div class="flex items-center gap-2">
                    <PhoneIcon class="w-4 h-4 text-muted-foreground" />
                    <div>
                      <div class="font-medium">{{ message.recipient_name || 'Anonyme' }}</div>
                      <div class="text-xs text-muted-foreground font-mono">{{ message.recipient_phone }}</div>
                    </div>
                  </div>
                </td>
                <td class="p-4 text-sm max-w-xs">
                  <div class="truncate font-mono text-xs">{{ message.content }}</div>
                </td>
                <td class="p-4 text-sm">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="message.type === 'campaign' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                  >
                    {{ message.type === 'campaign' ? 'Campagne' : 'Immédiat' }}
                  </span>
                </td>
                <td class="p-4 text-sm">
                  <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getStatusClass(message.status)"
                  >
                    <component :is="getStatusIcon(message.status)" class="w-3.5 h-3.5" />
                    {{ getStatusLabel(message.status) }}
                  </span>
                </td>
                <td class="p-4 text-sm font-semibold text-primary">{{ message.cost }} XAF</td>
                <td class="p-4 text-sm">
                  <button
                    @click="viewDetails(message)"
                    class="text-primary hover:text-primary/80 transition-colors"
                  >
                    <EyeIcon class="w-5 h-5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
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

    <!-- Message Details Modal -->
    <div
      v-if="selectedMessage"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="selectedMessage = null"
    >
      <div class="bg-background rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
          <h2 class="text-2xl font-bold">Détails du message</h2>
          <button
            @click="selectedMessage = null"
            class="text-muted-foreground hover:text-foreground transition-colors"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
        <div class="p-6 space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h3 class="font-semibold mb-2">Destinataire</h3>
              <p class="text-muted-foreground">{{ selectedMessage.recipient_name || 'Anonyme' }}</p>
              <p class="text-sm text-muted-foreground font-mono mt-1">{{ selectedMessage.recipient_phone }}</p>
            </div>
            <div>
              <h3 class="font-semibold mb-2">Date d'envoi</h3>
              <p class="text-muted-foreground">{{ formatDate(selectedMessage.sent_at) }}</p>
              <p class="text-sm text-muted-foreground mt-1">{{ formatTime(selectedMessage.sent_at) }}</p>
            </div>
          </div>
          <div>
            <h3 class="font-semibold mb-2">Message</h3>
            <div class="p-4 bg-muted/50 rounded-lg font-mono text-sm whitespace-pre-wrap">{{ selectedMessage.content }}</div>
            <p class="text-xs text-muted-foreground mt-2">{{ selectedMessage.content.length }} caractères</p>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <h3 class="font-semibold mb-2">Type</h3>
              <span
                class="inline-block px-3 py-1 rounded-full text-sm font-medium"
                :class="selectedMessage.type === 'campaign' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
              >
                {{ selectedMessage.type === 'campaign' ? 'Campagne' : 'Immédiat' }}
              </span>
            </div>
            <div>
              <h3 class="font-semibold mb-2">Statut</h3>
              <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium"
                :class="getStatusClass(selectedMessage.status)"
              >
                <component :is="getStatusIcon(selectedMessage.status)" class="w-4 h-4" />
                {{ getStatusLabel(selectedMessage.status) }}
              </span>
            </div>
            <div>
              <h3 class="font-semibold mb-2">Coût</h3>
              <p class="text-2xl font-bold text-primary">{{ selectedMessage.cost }} XAF</p>
            </div>
          </div>
          <div v-if="selectedMessage.campaign_name">
            <h3 class="font-semibold mb-2">Campagne associée</h3>
            <p class="text-muted-foreground">{{ selectedMessage.campaign_name }}</p>
          </div>
          <div v-if="selectedMessage.provider">
            <h3 class="font-semibold mb-2">Provider</h3>
            <p class="text-muted-foreground uppercase">{{ selectedMessage.provider }}</p>
          </div>
          <div v-if="selectedMessage.error_message" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <h3 class="font-semibold mb-2 text-destructive">Erreur</h3>
            <p class="text-sm text-destructive">{{ selectedMessage.error_message }}</p>
          </div>
        </div>
        <div class="p-6 border-t flex justify-end">
          <button
            @click="selectedMessage = null"
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
import MainLayout from '@/components/MainLayout.vue'
import { messageHistoryService } from '@/services/messageHistoryService'
import { showError } from '@/utils/notifications'
import {
  ChatBubbleLeftIcon,
  PaperAirplaneIcon,
  InboxIcon,
  ClockIcon,
  PhoneIcon,
  EyeIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  XCircleIcon,
  BanknotesIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

interface Message {
  id: number
  recipient_name?: string
  recipient_phone: string
  content: string
  type: 'immediate' | 'campaign'
  status: 'delivered' | 'pending' | 'failed'
  sent_at: string
  cost: number
  campaign_name?: string
  provider?: string
  error_message?: string
}

const messages = ref<Message[]>([])
const loading = ref(false)
const selectedMessage = ref<Message | null>(null)
const currentPage = ref(1)
const itemsPerPage = 20

const filters = ref({
  search: '',
  status: '',
  type: '',
  dateFrom: '',
  dateTo: ''
})

const stats = computed(() => {
  return {
    total: messages.value.length,
    delivered: messages.value.filter(m => m.status === 'delivered').length,
    failed: messages.value.filter(m => m.status === 'failed').length,
    totalCost: messages.value.reduce((sum, m) => sum + m.cost, 0)
  }
})

const filteredMessages = computed(() => {
  let filtered = messages.value

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(m =>
      m.recipient_phone.includes(search) ||
      (m.recipient_name && m.recipient_name.toLowerCase().includes(search)) ||
      m.content.toLowerCase().includes(search)
    )
  }

  if (filters.value.status) {
    filtered = filtered.filter(m => m.status === filters.value.status)
  }

  if (filters.value.type) {
    filtered = filtered.filter(m => m.type === filters.value.type)
  }

  if (filters.value.dateFrom) {
    filtered = filtered.filter(m => m.sent_at >= filters.value.dateFrom)
  }

  if (filters.value.dateTo) {
    filtered = filtered.filter(m => m.sent_at <= filters.value.dateTo)
  }

  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filtered.slice(start, end)
})

const totalPages = computed(() => {
  let filtered = messages.value
  if (filters.value.search || filters.value.status || filters.value.type || filters.value.dateFrom || filters.value.dateTo) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(m => {
      const matchSearch = !filters.value.search ||
        m.recipient_phone.includes(search) ||
        (m.recipient_name && m.recipient_name.toLowerCase().includes(search)) ||
        m.content.toLowerCase().includes(search)
      const matchStatus = !filters.value.status || m.status === filters.value.status
      const matchType = !filters.value.type || m.type === filters.value.type
      const matchDateFrom = !filters.value.dateFrom || m.sent_at >= filters.value.dateFrom
      const matchDateTo = !filters.value.dateTo || m.sent_at <= filters.value.dateTo
      return matchSearch && matchStatus && matchType && matchDateFrom && matchDateTo
    })
  }
  return Math.ceil(filtered.length / itemsPerPage)
})

function getStatusClass(status: string) {
  const classes = {
    delivered: 'bg-success/10 text-success',
    pending: 'bg-warning/10 text-warning',
    failed: 'bg-destructive/10 text-destructive'
  }
  return classes[status as keyof typeof classes] || 'bg-muted text-muted-foreground'
}

function getStatusLabel(status: string) {
  const labels = {
    delivered: 'Livré',
    pending: 'En attente',
    failed: 'Échoué'
  }
  return labels[status as keyof typeof labels] || status
}

function getStatusIcon(status: string) {
  const icons = {
    delivered: CheckCircleIcon,
    pending: ClockIcon,
    failed: XCircleIcon
  }
  return icons[status as keyof typeof icons] || ClockIcon
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatTime(date: string) {
  return new Date(date).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function viewDetails(message: Message) {
  selectedMessage.value = message
}

async function loadMessages() {
  loading.value = true
  try {
    messages.value = await messageHistoryService.getAll()
  } catch (error: any) {
    console.error('Error loading messages:', error)
    showError(error.response?.data?.message || 'Erreur lors du chargement des messages')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadMessages()
})
</script>
