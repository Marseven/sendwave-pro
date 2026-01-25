<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-4 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-xl sm:text-3xl font-bold">Liste Noire</h1>
          <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Gérez les numéros bloqués</p>
        </div>
        <div class="flex gap-2 sm:gap-3">
          <button
            @click="showCheckModal = true"
            class="inline-flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 sm:h-10 px-2 sm:px-4 py-2"
          >
            <MagnifyingGlassIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Vérifier</span>
          </button>
          <button
            @click="openAddModal"
            class="inline-flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-8 sm:h-10 px-2 sm:px-4 py-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Ajouter</span>
          </button>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="grid grid-cols-3 gap-2 sm:gap-6 mb-4 sm:mb-8">
        <div class="rounded-lg border bg-card p-3 sm:p-4">
          <div class="text-xs sm:text-sm text-muted-foreground">Bloqués</div>
          <div class="text-lg sm:text-2xl font-bold mt-1">{{ totalBlocked }}</div>
        </div>
        <div class="rounded-lg border bg-card p-3 sm:p-4">
          <div class="text-xs sm:text-sm text-muted-foreground">Ce mois</div>
          <div class="text-lg sm:text-2xl font-bold mt-1 text-destructive">{{ blockedThisMonth }}</div>
        </div>
        <div class="rounded-lg border bg-card p-3 sm:p-4">
          <div class="text-xs sm:text-sm text-muted-foreground">Évités</div>
          <div class="text-lg sm:text-2xl font-bold mt-1 text-success">{{ smsAvoided }}</div>
        </div>
      </div>

      <!-- Recherche -->
      <div class="rounded-lg border bg-card shadow-sm mb-4 sm:mb-6 p-3 sm:p-6">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher..."
          class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        />
      </div>

      <!-- Liste des numéros bloqués -->
      <div class="rounded-lg border bg-card shadow-sm">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-primary"></div>
        </div>
        <div v-else-if="filteredBlacklist.length === 0" class="flex flex-col items-center justify-center py-8 sm:py-12">
          <NoSymbolIcon class="w-12 h-12 sm:w-16 sm:h-16 text-muted-foreground mb-4" />
          <p class="text-base sm:text-lg font-medium">Aucun numéro bloqué</p>
          <p class="text-xs sm:text-sm text-muted-foreground mt-1">La liste noire est vide</p>
        </div>
        <template v-else>
          <!-- Desktop Table -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
              <thead class="border-b bg-muted/50">
                <tr>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Numéro</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Raison</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Date d'ajout</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in filteredBlacklist"
                  :key="item.id"
                  class="border-b transition-colors hover:bg-muted/50"
                >
                  <td class="p-4 font-medium font-mono">{{ item.phone_number }}</td>
                  <td class="p-4 text-sm text-muted-foreground">{{ item.reason || '-' }}</td>
                  <td class="p-4 text-sm text-muted-foreground">{{ formatDate(item.added_at || item.created_at) }}</td>
                  <td class="p-4">
                    <button
                      @click="removeFromBlacklist(item)"
                      class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-destructive/10 hover:text-destructive h-8 w-8"
                      title="Retirer"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Mobile Cards -->
          <div class="md:hidden divide-y">
            <div v-for="item in filteredBlacklist" :key="item.id" class="p-3 flex items-center justify-between">
              <div class="min-w-0 flex-1">
                <div class="font-medium font-mono text-sm">{{ item.phone_number }}</div>
                <div class="text-xs text-muted-foreground truncate">{{ item.reason || 'Aucune raison' }}</div>
                <div class="text-xs text-muted-foreground">{{ formatDate(item.added_at || item.created_at) }}</div>
              </div>
              <button
                @click="removeFromBlacklist(item)"
                class="ml-2 inline-flex items-center justify-center rounded-md transition-colors hover:bg-destructive/10 hover:text-destructive h-8 w-8"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="flex items-center justify-between mt-6">
        <p class="text-sm text-muted-foreground">
          Page {{ pagination.currentPage }} sur {{ pagination.lastPage }} ({{ pagination.total }} numéros)
        </p>
        <div class="flex gap-2">
          <button
            @click="loadBlacklist(pagination.currentPage - 1)"
            :disabled="pagination.currentPage <= 1"
            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
          >
            Précédent
          </button>
          <button
            @click="loadBlacklist(pagination.currentPage + 1)"
            :disabled="pagination.currentPage >= pagination.lastPage"
            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Ajouter numéro -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4"
      @click.self="closeAddModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-sm sm:max-w-md p-4 sm:p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Ajouter à la liste noire</h2>
          <button @click="closeAddModal" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="addToBlacklist" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Numéro de téléphone *</label>
            <input
              v-model="addForm.phone_number"
              type="tel"
              required
              placeholder="+24177000000"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Raison (optionnel)</label>
            <textarea
              v-model="addForm.reason"
              rows="3"
              placeholder="Ex: Demande de désinscription, spam..."
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            ></textarea>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeAddModal"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-destructive text-destructive-foreground hover:bg-destructive/90 h-10 px-4 py-2"
            >
              <div v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ saving ? 'Ajout...' : 'Bloquer ce numéro' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Vérifier numéro -->
    <div
      v-if="showCheckModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4"
      @click.self="showCheckModal = false"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-sm sm:max-w-md p-4 sm:p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Vérifier un numéro</h2>
          <button @click="showCheckModal = false" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Numéro de téléphone</label>
            <div class="flex gap-2">
              <input
                v-model="checkNumber"
                type="tel"
                placeholder="+24177000000"
                class="flex-1 h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
              <button
                @click="checkBlacklist"
                :disabled="checking || !checkNumber"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
              >
                <div v-if="checking" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span>{{ checking ? 'Vérification...' : 'Vérifier' }}</span>
              </button>
            </div>
          </div>

          <div v-if="checkResult !== null" class="p-4 rounded-lg" :class="checkResult ? 'bg-destructive/10' : 'bg-success/10'">
            <div class="flex items-center gap-3">
              <div v-if="checkResult" class="w-10 h-10 rounded-full bg-destructive/20 flex items-center justify-center">
                <NoSymbolIcon class="w-5 h-5 text-destructive" />
              </div>
              <div v-else class="w-10 h-10 rounded-full bg-success/20 flex items-center justify-center">
                <CheckIcon class="w-5 h-5 text-success" />
              </div>
              <div>
                <p class="font-medium" :class="checkResult ? 'text-destructive' : 'text-success'">
                  {{ checkResult ? 'Numéro bloqué' : 'Numéro autorisé' }}
                </p>
                <p class="text-sm text-muted-foreground">
                  {{ checkResult ? 'Ce numéro est dans la liste noire' : 'Ce numéro peut recevoir des SMS' }}
                </p>
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
  PlusIcon,
  TrashIcon,
  XMarkIcon,
  NoSymbolIcon,
  MagnifyingGlassIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'
import api from '@/services/api'

interface BlacklistItem {
  id: number
  phone_number: string
  reason: string | null
  added_at: string
  created_at: string
}

interface Pagination {
  currentPage: number
  lastPage: number
  total: number
}

const searchQuery = ref('')
const blacklist = ref<BlacklistItem[]>([])
const loading = ref(false)
const saving = ref(false)
const checking = ref(false)
const showAddModal = ref(false)
const showCheckModal = ref(false)
const checkNumber = ref('')
const checkResult = ref<boolean | null>(null)

const pagination = ref<Pagination>({
  currentPage: 1,
  lastPage: 1,
  total: 0
})

const addForm = ref({
  phone_number: '',
  reason: ''
})

const filteredBlacklist = computed(() => {
  if (!searchQuery.value) return blacklist.value

  const query = searchQuery.value.toLowerCase()
  return blacklist.value.filter(item =>
    item.phone_number.toLowerCase().includes(query) ||
    (item.reason && item.reason.toLowerCase().includes(query))
  )
})

const totalBlocked = computed(() => pagination.value.total)

const blockedThisMonth = computed(() => {
  const now = new Date()
  const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
  return blacklist.value.filter(item => {
    const addedAt = new Date(item.added_at || item.created_at)
    return addedAt >= startOfMonth
  }).length
})

const smsAvoided = computed(() => {
  // Estimation basée sur le nombre de numéros bloqués
  return totalBlocked.value * 5
})

function formatDate(dateString?: string): string {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function loadBlacklist(page = 1) {
  loading.value = true
  try {
    const response = await api.get('/blacklist', { params: { page } })
    blacklist.value = response.data.data
    pagination.value = {
      currentPage: response.data.current_page,
      lastPage: response.data.last_page,
      total: response.data.total
    }
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement')
    console.error('Error loading blacklist:', err)
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  addForm.value = { phone_number: '', reason: '' }
  showAddModal.value = true
}

function closeAddModal() {
  showAddModal.value = false
  addForm.value = { phone_number: '', reason: '' }
}

async function addToBlacklist() {
  if (!addForm.value.phone_number.trim()) {
    showError('Le numéro de téléphone est requis')
    return
  }

  saving.value = true
  try {
    await api.post('/blacklist', addForm.value)
    showSuccess('Numéro ajouté à la liste noire')
    closeAddModal()
    await loadBlacklist()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'ajout')
  } finally {
    saving.value = false
  }
}

async function removeFromBlacklist(item: BlacklistItem) {
  const confirmed = await showConfirm(
    'Retirer de la liste noire ?',
    `Êtes-vous sûr de vouloir débloquer ${item.phone_number} ?`
  )

  if (confirmed) {
    try {
      await api.delete(`/blacklist/${item.id}`)
      showSuccess('Numéro retiré de la liste noire')
      blacklist.value = blacklist.value.filter(b => b.id !== item.id)
      pagination.value.total--
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur lors de la suppression')
    }
  }
}

async function checkBlacklist() {
  if (!checkNumber.value.trim()) {
    showError('Veuillez entrer un numéro')
    return
  }

  checking.value = true
  checkResult.value = null
  try {
    const response = await api.post('/blacklist/check', {
      phone_number: checkNumber.value
    })
    checkResult.value = response.data.is_blacklisted
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la vérification')
  } finally {
    checking.value = false
  }
}

onMounted(() => {
  loadBlacklist()
})
</script>
