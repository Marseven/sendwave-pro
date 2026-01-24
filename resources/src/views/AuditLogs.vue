<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Journal d'Audit</h1>
          <p class="text-muted-foreground mt-2">Consultez l'historique des actions sur votre compte</p>
        </div>
        <button
          @click="exportLogs"
          :disabled="exporting || logs.length === 0"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
        >
          <ArrowDownTrayIcon class="w-4 h-4" />
          <span>{{ exporting ? 'Export...' : 'Exporter' }}</span>
        </button>
      </div>

      <!-- Filtres -->
      <div class="rounded-lg border bg-card shadow-sm mb-6 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Action</label>
            <select
              v-model="filters.action"
              @change="loadLogs"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <option value="">Toutes les actions</option>
              <option v-for="action in availableActions" :key="action" :value="action">
                {{ formatAction(action) }}
              </option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Date début</label>
            <input
              v-model="filters.start_date"
              type="date"
              @change="loadLogs"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Date fin</label>
            <input
              v-model="filters.end_date"
              type="date"
              @change="loadLogs"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">&nbsp;</label>
            <button
              @click="resetFilters"
              class="flex h-10 w-full items-center justify-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <XMarkIcon class="w-4 h-4" />
              <span>Réinitialiser</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Liste des logs -->
      <div class="rounded-lg border bg-card shadow-sm">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
        <div v-else-if="logs.length === 0" class="flex flex-col items-center justify-center py-12">
          <ClipboardDocumentListIcon class="w-16 h-16 text-muted-foreground mb-4" />
          <p class="text-lg font-medium">Aucun log trouvé</p>
          <p class="text-sm text-muted-foreground mt-1">Le journal d'audit est vide pour cette période</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b bg-muted/50">
              <tr>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Date</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Action</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Utilisateur</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Détails</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">IP</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="log in logs"
                :key="log.id"
                class="border-b transition-colors hover:bg-muted/50"
              >
                <td class="p-4 text-sm text-muted-foreground whitespace-nowrap">
                  {{ formatDateTime(log.created_at) }}
                </td>
                <td class="p-4">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="getActionBadgeClass(log.action)"
                  >
                    {{ formatAction(log.action) }}
                  </span>
                </td>
                <td class="p-4 text-sm">
                  <div v-if="log.sub_account">
                    <span class="font-medium">{{ log.sub_account.name }}</span>
                    <span class="text-xs text-muted-foreground ml-1">(sous-compte)</span>
                  </div>
                  <div v-else-if="log.user">
                    <span class="font-medium">{{ log.user.name }}</span>
                  </div>
                  <span v-else class="text-muted-foreground">-</span>
                </td>
                <td class="p-4 text-sm text-muted-foreground max-w-xs truncate">
                  {{ getLogSummary(log) }}
                </td>
                <td class="p-4 text-sm font-mono text-muted-foreground">
                  {{ log.ip_address || '-' }}
                </td>
                <td class="p-4">
                  <button
                    @click="showDetails(log)"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-8 w-8"
                    title="Voir les détails"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="flex items-center justify-between mt-6">
        <p class="text-sm text-muted-foreground">
          Page {{ pagination.currentPage }} sur {{ pagination.lastPage }} ({{ pagination.total }} entrées)
        </p>
        <div class="flex gap-2">
          <button
            @click="loadLogs(pagination.currentPage - 1)"
            :disabled="pagination.currentPage <= 1"
            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
          >
            Précédent
          </button>
          <button
            @click="loadLogs(pagination.currentPage + 1)"
            :disabled="pagination.currentPage >= pagination.lastPage"
            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Détails -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="selectedLog = null"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Détails du log</h2>
          <button @click="selectedLog = null" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-sm text-muted-foreground">Date</label>
              <p class="font-medium">{{ formatDateTime(selectedLog.created_at) }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-sm text-muted-foreground">Action</label>
              <p>
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  :class="getActionBadgeClass(selectedLog.action)"
                >
                  {{ formatAction(selectedLog.action) }}
                </span>
              </p>
            </div>
            <div class="space-y-1">
              <label class="text-sm text-muted-foreground">Utilisateur</label>
              <p class="font-medium">
                {{ selectedLog.sub_account?.name || selectedLog.user?.name || '-' }}
                <span v-if="selectedLog.sub_account" class="text-xs text-muted-foreground">(sous-compte)</span>
              </p>
            </div>
            <div class="space-y-1">
              <label class="text-sm text-muted-foreground">Adresse IP</label>
              <p class="font-mono">{{ selectedLog.ip_address || '-' }}</p>
            </div>
          </div>

          <div v-if="selectedLog.model_type" class="space-y-1">
            <label class="text-sm text-muted-foreground">Objet concerné</label>
            <p class="font-medium">{{ selectedLog.model_type }} #{{ selectedLog.model_id }}</p>
          </div>

          <div v-if="selectedLog.old_values && Object.keys(selectedLog.old_values).length > 0" class="space-y-2">
            <label class="text-sm text-muted-foreground">Anciennes valeurs</label>
            <div class="bg-red-50 dark:bg-red-950 rounded-lg p-4 border border-red-200 dark:border-red-800">
              <pre class="text-sm overflow-x-auto">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
            </div>
          </div>

          <div v-if="selectedLog.new_values && Object.keys(selectedLog.new_values).length > 0" class="space-y-2">
            <label class="text-sm text-muted-foreground">Nouvelles valeurs</label>
            <div class="bg-green-50 dark:bg-green-950 rounded-lg p-4 border border-green-200 dark:border-green-800">
              <pre class="text-sm overflow-x-auto">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
            </div>
          </div>

          <div v-if="selectedLog.user_agent" class="space-y-1">
            <label class="text-sm text-muted-foreground">User Agent</label>
            <p class="text-sm text-muted-foreground break-all">{{ selectedLog.user_agent }}</p>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  ArrowDownTrayIcon,
  XMarkIcon,
  ClipboardDocumentListIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'
import { showSuccess, showError } from '@/utils/notifications'
import api from '@/services/api'

interface AuditLog {
  id: number
  user_id: number
  sub_account_id: number | null
  action: string
  model_type: string | null
  model_id: number | null
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
  user?: { name: string; email: string }
  sub_account?: { name: string; email: string }
}

interface Pagination {
  currentPage: number
  lastPage: number
  total: number
}

const loading = ref(true)
const exporting = ref(false)
const logs = ref<AuditLog[]>([])
const availableActions = ref<string[]>([])
const selectedLog = ref<AuditLog | null>(null)

const pagination = ref<Pagination>({
  currentPage: 1,
  lastPage: 1,
  total: 0
})

const filters = reactive({
  action: '',
  start_date: '',
  end_date: ''
})

const actionLabels: Record<string, string> = {
  'login': 'Connexion',
  'logout': 'Déconnexion',
  'message.sent': 'SMS envoyé',
  'message.failed': 'SMS échoué',
  'campaign.created': 'Campagne créée',
  'campaign.sent': 'Campagne envoyée',
  'campaign.completed': 'Campagne terminée',
  'contact.created': 'Contact créé',
  'contact.updated': 'Contact modifié',
  'contact.deleted': 'Contact supprimé',
  'group.created': 'Groupe créé',
  'group.updated': 'Groupe modifié',
  'group.deleted': 'Groupe supprimé',
  'template.created': 'Template créé',
  'template.updated': 'Template modifié',
  'template.deleted': 'Template supprimé',
  'webhook.created': 'Webhook créé',
  'webhook.updated': 'Webhook modifié',
  'webhook.deleted': 'Webhook supprimé',
  'blacklist.add': 'Numéro bloqué',
  'blacklist.remove': 'Numéro débloqué',
  'sub_account.created': 'Sous-compte créé',
  'sub_account.suspended': 'Sous-compte suspendu',
  'sub_account.activated': 'Sous-compte activé',
  'api_key.created': 'Clé API créée',
  'api_key.revoked': 'Clé API révoquée'
}

function formatAction(action: string): string {
  return actionLabels[action] || action.replace(/[._]/g, ' ')
}

function formatDateTime(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getActionBadgeClass(action: string): string {
  if (action.includes('delete') || action.includes('remove') || action.includes('failed') || action.includes('suspended') || action.includes('revoked')) {
    return 'bg-destructive/10 text-destructive'
  }
  if (action.includes('create') || action.includes('sent') || action.includes('completed') || action.includes('activated') || action.includes('add')) {
    return 'bg-success/10 text-success'
  }
  if (action.includes('update') || action.includes('updated')) {
    return 'bg-warning/10 text-warning'
  }
  return 'bg-muted text-muted-foreground'
}

function getLogSummary(log: AuditLog): string {
  if (log.new_values) {
    const keys = Object.keys(log.new_values)
    if (keys.length > 0) {
      return `${keys.slice(0, 3).join(', ')}${keys.length > 3 ? '...' : ''}`
    }
  }
  if (log.model_type) {
    return `${log.model_type} #${log.model_id}`
  }
  return '-'
}

async function loadLogs(page = 1) {
  loading.value = true
  try {
    const params: any = { page }
    if (filters.action) params.action = filters.action
    if (filters.start_date) params.start_date = filters.start_date
    if (filters.end_date) params.end_date = filters.end_date

    const response = await api.get('/audit-logs', { params })
    logs.value = response.data.data
    pagination.value = {
      currentPage: response.data.current_page,
      lastPage: response.data.last_page,
      total: response.data.total
    }
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement')
  } finally {
    loading.value = false
  }
}

async function loadActions() {
  try {
    const response = await api.get('/audit-logs/actions')
    availableActions.value = response.data.data
  } catch (err) {
    console.error('Error loading actions:', err)
  }
}

function resetFilters() {
  filters.action = ''
  filters.start_date = ''
  filters.end_date = ''
  loadLogs()
}

function showDetails(log: AuditLog) {
  selectedLog.value = log
}

async function exportLogs() {
  exporting.value = true
  try {
    const csvContent = [
      ['Date', 'Action', 'Utilisateur', 'IP', 'Model', 'Model ID'].join(','),
      ...logs.value.map(log => [
        formatDateTime(log.created_at),
        formatAction(log.action),
        log.sub_account?.name || log.user?.name || '',
        log.ip_address || '',
        log.model_type || '',
        log.model_id || ''
      ].map(v => `"${v}"`).join(','))
    ].join('\n')

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `audit_logs_${new Date().toISOString().split('T')[0]}.csv`
    link.click()
    showSuccess('Export terminé')
  } catch (err) {
    showError('Erreur lors de l\'export')
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  loadLogs()
  loadActions()
})
</script>
