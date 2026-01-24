<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold">Calendrier</h1>
        <p class="text-muted-foreground mt-2">Planifiez vos campagnes SMS</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <CalendarIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Campagnes planifiées</h3>
          </div>

          <div v-if="scheduledCampaigns.length === 0" class="text-center py-12">
            <CalendarDaysIcon class="w-16 h-16 text-muted-foreground mx-auto mb-4" />
            <p class="text-muted-foreground">Aucune campagne planifiée</p>
            <button @click="$router.push('/campaign/create')" class="mt-4 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
              <PlusIcon class="w-4 h-4" />
              <span>Planifier une campagne</span>
            </button>
          </div>

          <div v-else class="space-y-3">
            <div v-for="campaign in scheduledCampaigns" :key="campaign.id" class="flex items-center gap-4 p-4 border rounded-lg hover:bg-muted/50 transition-colors group">
              <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center font-semibold">
                <div class="text-center">
                  <div class="text-xs">{{ formatMonth(campaign.scheduled_at) }}</div>
                  <div class="text-lg">{{ formatDay(campaign.scheduled_at) }}</div>
                </div>
              </div>
              <div class="flex-1">
                <div class="font-medium">{{ campaign.name }}</div>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <ClockIcon class="w-4 h-4" />
                  <span>{{ formatTime(campaign.scheduled_at) }}</span>
                  <span>•</span>
                  <UsersIcon class="w-4 h-4" />
                  <span>{{ formatNumber(campaign.messages_sent || 0) }} destinataires</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-1 rounded-full bg-warning/10 text-warning flex items-center gap-1">
                  <ClockIcon class="w-3 h-3" />
                  <span>Planifiée</span>
                </span>
                <button
                  @click="openEditModal(campaign)"
                  class="opacity-0 group-hover:opacity-100 transition-opacity inline-flex items-center justify-center rounded-md text-sm font-medium hover:bg-accent h-8 w-8"
                  title="Modifier"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  @click="cancelCampaign(campaign)"
                  class="opacity-0 group-hover:opacity-100 transition-opacity inline-flex items-center justify-center rounded-md text-sm font-medium hover:bg-destructive/10 hover:text-destructive h-8 w-8"
                  title="Annuler"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <BellAlertIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">À venir</h3>
          </div>
          <div class="text-2xl font-bold mb-2">{{ upcomingCount }} campagne{{ upcomingCount > 1 ? 's' : '' }}</div>
          <p class="text-sm text-muted-foreground">dans les 7 prochains jours</p>

          <div class="mt-6 space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Ce mois-ci</span>
              <span class="font-semibold">{{ thisMonthCount }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Total planifiées</span>
              <span class="font-semibold">{{ scheduledCampaigns.length }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'édition -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="closeEditModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Reprogrammer la campagne</h2>
          <button @click="closeEditModal" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveCampaign" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom de la campagne</label>
            <input
              v-model="editForm.name"
              type="text"
              readonly
              class="flex h-10 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Date *</label>
              <input
                v-model="editForm.scheduled_date"
                type="date"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Heure *</label>
              <input
                v-model="editForm.scheduled_time"
                type="time"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeEditModal"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ saving ? 'Enregistrement...' : 'Reprogrammer' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import {
  CalendarIcon,
  CalendarDaysIcon,
  ClockIcon,
  UsersIcon,
  BellAlertIcon,
  PlusIcon,
  PencilIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { campaignService } from '@/services/campaignService'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

interface Campaign {
  id: number
  name: string
  status: string
  scheduled_at: string | null
  messages_sent: number
}

const router = useRouter()
const loading = ref(true)
const saving = ref(false)
const showEditModal = ref(false)
const campaigns = ref<Campaign[]>([])
const editingCampaign = ref<Campaign | null>(null)

const editForm = ref({
  name: '',
  scheduled_date: '',
  scheduled_time: ''
})

const scheduledCampaigns = computed(() => {
  const scheduledStatuses = ['Planifié', 'planifié', 'scheduled', 'draft']
  return campaigns.value
    .filter(c => scheduledStatuses.includes(c.status?.toLowerCase() || '') || scheduledStatuses.includes(c.status || ''))
    .filter(c => c.scheduled_at)
    .sort((a, b) => new Date(a.scheduled_at!).getTime() - new Date(b.scheduled_at!).getTime())
})

const upcomingCount = computed(() => {
  const now = new Date()
  const sevenDaysFromNow = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
  return scheduledCampaigns.value.filter(c => {
    const schedDate = new Date(c.scheduled_at!)
    return schedDate >= now && schedDate <= sevenDaysFromNow
  }).length
})

const thisMonthCount = computed(() => {
  const now = new Date()
  return scheduledCampaigns.value.filter(c => {
    const schedDate = new Date(c.scheduled_at!)
    return schedDate.getMonth() === now.getMonth() && schedDate.getFullYear() === now.getFullYear()
  }).length
})

function formatMonth(dateStr: string | null): string {
  if (!dateStr) return ''
  const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']
  return months[new Date(dateStr).getMonth()]
}

function formatDay(dateStr: string | null): string {
  if (!dateStr) return ''
  return new Date(dateStr).getDate().toString().padStart(2, '0')
}

function formatTime(dateStr: string | null): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`
}

function formatNumber(num: number): string {
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`
  }
  return num.toString()
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

function openEditModal(campaign: Campaign) {
  editingCampaign.value = campaign
  const scheduledDate = new Date(campaign.scheduled_at!)
  editForm.value = {
    name: campaign.name,
    scheduled_date: scheduledDate.toISOString().split('T')[0],
    scheduled_time: `${scheduledDate.getHours().toString().padStart(2, '0')}:${scheduledDate.getMinutes().toString().padStart(2, '0')}`
  }
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingCampaign.value = null
}

async function saveCampaign() {
  if (!editingCampaign.value) return

  saving.value = true
  try {
    const scheduled_at = `${editForm.value.scheduled_date} ${editForm.value.scheduled_time}`
    await campaignService.update(editingCampaign.value.id, { scheduled_at })
    showSuccess('Campagne reprogrammée avec succès')
    closeEditModal()
    await loadCampaigns()
  } catch (err: any) {
    showError(err.response?.data?.message || err.message || 'Erreur lors de la reprogrammation')
  } finally {
    saving.value = false
  }
}

async function cancelCampaign(campaign: Campaign) {
  const confirmed = await showConfirm(
    'Annuler la campagne ?',
    `Êtes-vous sûr de vouloir annuler la campagne "${campaign.name}" ?`
  )

  if (confirmed) {
    try {
      await campaignService.delete(campaign.id)
      showSuccess('Campagne annulée avec succès')
      await loadCampaigns()
    } catch (err: any) {
      showError(err.message || 'Erreur lors de l\'annulation')
    }
  }
}

onMounted(() => {
  loadCampaigns()
})
</script>
