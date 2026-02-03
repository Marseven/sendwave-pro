<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-xl sm:text-3xl font-bold">Clés API</h1>
          <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Gérez vos clés API et consultez la documentation complète</p>
        </div>
        <button
          v-if="activeTab === 'keys'"
          @click="openCreateModal"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span class="hidden sm:inline">Nouvelle clé API</span>
          <span class="sm:hidden">Nouvelle clé</span>
        </button>
      </div>

      <!-- Tab Navigation -->
      <TabNav v-model="activeTab" :tabs="tabs" class="mb-6" />

      <!-- Tab: Mes Clés -->
      <div v-if="activeTab === 'keys'" class="space-y-6">
        <div class="rounded-lg border bg-card">
          <div class="p-6 border-b">
            <h3 class="font-semibold">Clés API actives</h3>
            <p class="text-sm text-muted-foreground">Les clés permettent aux applications externes d'accéder à l'API SendWave</p>
          </div>

          <div v-if="loading" class="p-8 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>

          <div v-else-if="apiKeys.length === 0" class="p-8 text-center text-muted-foreground">
            <KeyIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>Aucune clé API créée</p>
            <p class="text-sm">Créez une clé pour commencer à utiliser l'API</p>
          </div>

          <div v-else class="divide-y">
            <div
              v-for="apiKey in apiKeys"
              :key="apiKey.id"
              class="p-4 hover:bg-muted/50"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ apiKey.name }}</span>
                    <span
                      class="px-2 py-0.5 text-xs rounded-full"
                      :class="apiKey.type === 'production' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'"
                    >
                      {{ apiKey.type === 'production' ? 'Production' : 'Test' }}
                    </span>
                    <span
                      v-if="apiKey.status === 'revoked'"
                      class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                    >
                      Révoquée
                    </span>
                  </div>
                  <div class="mt-1 flex items-center gap-2">
                    <code class="text-sm font-mono bg-muted px-2 py-1 rounded">{{ apiKey.key }}</code>
                    <button
                      v-if="apiKey.full_key"
                      @click="copyKey(apiKey.full_key)"
                      class="text-xs text-primary hover:underline"
                    >
                      Copier
                    </button>
                  </div>
                  <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                    <span>Créée le {{ formatDate(apiKey.created_at) }}</span>
                    <span v-if="apiKey.last_used_at">Dernière utilisation: {{ formatDate(apiKey.last_used_at) }}</span>
                    <span>Limite: {{ apiKey.rate_limit }} req/min</span>
                  </div>
                  <div class="mt-2 flex flex-wrap gap-1">
                    <span
                      v-for="perm in apiKey.permissions"
                      :key="perm"
                      class="px-2 py-0.5 text-xs rounded bg-primary/10 text-primary"
                    >
                      {{ permissionLabels[perm] || perm }}
                    </span>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button
                    @click="regenerateKey(apiKey.id)"
                    :disabled="apiKey.status === 'revoked'"
                    class="text-sm px-3 py-1 rounded border hover:bg-accent disabled:opacity-50"
                    title="Régénérer"
                  >
                    <ArrowPathIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click="revokeKey(apiKey.id)"
                    :disabled="apiKey.status === 'revoked'"
                    class="text-sm px-3 py-1 rounded border hover:bg-destructive/10 text-destructive disabled:opacity-50"
                    title="Révoquer"
                  >
                    <XMarkIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Documentation API -->
      <div v-if="activeTab === 'docs'" class="space-y-6">
        <!-- Search & Filters -->
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher un endpoint (ex: /messages/send, contacts, webhook...)"
              class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
          </div>
          <select
            v-model="methodFilter"
            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm min-w-[140px]"
          >
            <option value="">Toutes les méthodes</option>
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="DELETE">DELETE</option>
          </select>
        </div>

        <!-- Quick Info -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="rounded-lg border bg-card p-4">
            <div class="text-2xl font-bold text-primary">{{ totalEndpoints }}</div>
            <div class="text-sm text-muted-foreground">Endpoints API</div>
          </div>
          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm font-medium mb-1">URL de base</div>
            <code class="text-xs font-mono text-muted-foreground">{{ baseUrl }}/api</code>
          </div>
          <div class="rounded-lg border bg-card p-4">
            <div class="text-sm font-medium mb-1">Authentification</div>
            <code class="text-xs font-mono text-muted-foreground">Authorization: Bearer &#123;token&#125;</code>
          </div>
        </div>

        <!-- Categories -->
        <div class="space-y-3">
          <ApiCategorySection
            v-for="category in visibleCategories"
            :key="category.id"
            :category="category"
            :base-url="baseUrl"
            :search-query="searchQuery"
            :method-filter="methodFilter"
            :default-open="!!searchQuery || !!methodFilter"
          />
        </div>

        <div v-if="visibleCategories.length === 0" class="text-center py-12 text-muted-foreground">
          <MagnifyingGlassIcon class="w-10 h-10 mx-auto mb-3 opacity-50" />
          <p>Aucun endpoint trouvé pour "{{ searchQuery }}"</p>
        </div>

        <!-- Rate Limits -->
        <div class="p-4 bg-yellow-50 dark:bg-yellow-950 rounded-lg border border-yellow-200 dark:border-yellow-800">
          <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-1">Limites de requêtes</h4>
          <p class="text-sm text-yellow-700 dark:text-yellow-300">
            Chaque clé API a une limite de requêtes par minute. En cas de dépassement, vous recevrez une erreur 429 (Too Many Requests).
            L'endpoint <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">/api/messages/send</code> a un rate limit additionnel spécifique.
          </p>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showCreateModal = false"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Nouvelle clé API</h2>
          <button @click="showCreateModal = false" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="createKey" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom de la clé *</label>
            <input
              v-model="createForm.name"
              type="text"
              placeholder="Ex: Application mobile"
              required
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Type</label>
            <select
              v-model="createForm.type"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="production">Production</option>
              <option value="test">Test</option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Permissions</label>
            <div class="space-y-2">
              <label v-for="(label, key) in permissionLabels" :key="key" class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="key"
                  v-model="createForm.permissions"
                  class="rounded border-input"
                />
                <span class="text-sm">{{ label }}</span>
              </label>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Limite de requêtes (par minute)</label>
            <input
              v-model.number="createForm.rate_limit"
              type="number"
              min="1"
              max="10000"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="showCreateModal = false"
              class="flex-1 h-10 px-4 rounded-md border hover:bg-accent"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="flex-1 h-10 px-4 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
            >
              {{ creating ? 'Création...' : 'Créer la clé' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- New Key Modal -->
    <div
      v-if="showNewKeyModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="text-center mb-6">
          <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center mx-auto mb-4">
            <CheckCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
          </div>
          <h2 class="text-xl font-bold">Clé API créée</h2>
          <p class="text-sm text-muted-foreground mt-2">Copiez cette clé maintenant, elle ne sera plus affichée en entier.</p>
        </div>

        <div class="p-4 bg-muted rounded-lg mb-6">
          <code class="text-sm font-mono break-all">{{ newKey }}</code>
        </div>

        <div class="flex gap-3">
          <button
            @click="copyKey(newKey); showNewKeyModal = false"
            class="flex-1 h-10 px-4 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 flex items-center justify-center gap-2"
          >
            <ClipboardIcon class="w-4 h-4" />
            Copier et fermer
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import TabNav from '@/components/ui/TabNav.vue'
import ApiCategorySection from '@/components/ui/ApiCategorySection.vue'
import {
  KeyIcon,
  XMarkIcon,
  PlusIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ClipboardIcon,
  MagnifyingGlassIcon,
  BookOpenIcon
} from '@heroicons/vue/24/outline'
import { apiKeyService, type ApiKey } from '@/services/apiKeyService'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'
import { apiCategories, getTotalEndpoints } from '@/data/apiDocumentation'

const baseUrl = computed(() => window.location.origin)
const totalEndpoints = getTotalEndpoints()

// Tab state
const activeTab = ref('keys')
const tabs = [
  { id: 'keys', label: 'Mes Clés', icon: KeyIcon },
  { id: 'docs', label: 'Documentation API', icon: BookOpenIcon }
]

// Documentation filters
const searchQuery = ref('')
const methodFilter = ref('')

const visibleCategories = computed(() => {
  if (!searchQuery.value && !methodFilter.value) {
    return apiCategories
  }

  return apiCategories.filter(category => {
    const q = searchQuery.value.toLowerCase()
    const m = methodFilter.value

    return category.endpoints.some(e => {
      const matchMethod = !m || e.method === m
      const matchSearch = !q ||
        e.path.toLowerCase().includes(q) ||
        e.summary.toLowerCase().includes(q) ||
        (e.description?.toLowerCase().includes(q)) ||
        category.name.toLowerCase().includes(q)
      return matchMethod && matchSearch
    })
  })
})

// API Keys state
const apiKeys = ref<ApiKey[]>([])
const loading = ref(false)
const creating = ref(false)
const showCreateModal = ref(false)
const showNewKeyModal = ref(false)
const newKey = ref('')

const permissionLabels: Record<string, string> = {
  send_sms: 'Envoyer des SMS',
  view_history: 'Voir l\'historique',
  manage_contacts: 'Gérer les contacts',
  view_balance: 'Voir le solde'
}

const createForm = reactive({
  name: '',
  type: 'production' as 'production' | 'test',
  permissions: ['send_sms', 'view_history'] as string[],
  rate_limit: 100
})

async function loadApiKeys() {
  loading.value = true
  try {
    apiKeys.value = await apiKeyService.getAll()
  } catch (err) {
    console.error('Error loading API keys:', err)
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  createForm.name = ''
  createForm.type = 'production'
  createForm.permissions = ['send_sms', 'view_history']
  createForm.rate_limit = 100
  showCreateModal.value = true
}

async function createKey() {
  if (!createForm.name) {
    showError('Veuillez entrer un nom pour la clé')
    return
  }

  creating.value = true
  try {
    const result = await apiKeyService.create(createForm)
    showCreateModal.value = false

    newKey.value = result.full_key || result.key
    showNewKeyModal.value = true

    await loadApiKeys()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la création')
  } finally {
    creating.value = false
  }
}

async function revokeKey(id: number) {
  const confirmed = await showConfirm(
    'Révoquer la clé API ?',
    'Cette action est irréversible. Les applications utilisant cette clé ne pourront plus accéder à l\'API.'
  )

  if (confirmed) {
    try {
      await apiKeyService.revoke(id)
      showSuccess('Clé API révoquée')
      await loadApiKeys()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur lors de la révocation')
    }
  }
}

async function regenerateKey(id: number) {
  const confirmed = await showConfirm(
    'Régénérer la clé API ?',
    'L\'ancienne clé sera invalidée et remplacée par une nouvelle.'
  )

  if (confirmed) {
    try {
      const result = await apiKeyService.regenerate(id)
      newKey.value = result.full_key || result.key
      showNewKeyModal.value = true
      await loadApiKeys()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur lors de la régénération')
    }
  }
}

function copyKey(key: string) {
  navigator.clipboard.writeText(key)
  showSuccess('Clé copiée dans le presse-papier')
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadApiKeys()
})
</script>
