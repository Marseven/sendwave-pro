<template>
  <MainLayout>
    <div class="p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
              <CogIcon class="w-7 h-7 text-primary" />
              Transactionnel
            </h1>
            <p class="text-muted-foreground mt-1">Gerez vos Sender IDs, templates, brouillons et routes</p>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <TabNav
        v-model="activeTab"
        :tabs="tabs"
        class="mb-6"
      />

      <!-- Sender ID Tab -->
      <div v-if="activeTab === 'senderid'" class="space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Sender IDs</h2>
          <button
            @click="showAddSenderModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90"
          >
            <PlusIcon class="w-4 h-4" />
            Nouveau Sender ID
          </button>
        </div>

        <div class="rounded-lg border bg-card">
          <table class="w-full">
            <thead>
              <tr class="border-b bg-muted/50">
                <th class="text-left p-4 font-medium text-sm">Sender ID</th>
                <th class="text-left p-4 font-medium text-sm">Type</th>
                <th class="text-left p-4 font-medium text-sm">Statut</th>
                <th class="text-left p-4 font-medium text-sm">Date creation</th>
                <th class="text-right p-4 font-medium text-sm">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sender in senderIds" :key="sender.id" class="border-b last:border-0 hover:bg-muted/30">
                <td class="p-4">
                  <span class="font-medium">{{ sender.name }}</span>
                </td>
                <td class="p-4">
                  <span class="px-2 py-1 text-xs rounded-full" :class="sender.type === 'transactional' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                    {{ sender.type === 'transactional' ? 'Transactionnel' : 'Marketing' }}
                  </span>
                </td>
                <td class="p-4">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(sender.status)">
                    {{ getStatusLabel(sender.status) }}
                  </span>
                </td>
                <td class="p-4 text-sm text-muted-foreground">{{ formatDate(sender.created_at) }}</td>
                <td class="p-4 text-right">
                  <button @click="editSender(sender)" class="p-2 hover:bg-accent rounded-lg">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleteSender(sender)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="senderIds.length === 0">
                <td colspan="5" class="p-8 text-center text-muted-foreground">
                  <IdentificationIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                  <p>Aucun Sender ID configure</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Templates Tab -->
      <div v-if="activeTab === 'templates'" class="space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Templates de messages</h2>
          <button
            @click="showAddTemplateModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90"
          >
            <PlusIcon class="w-4 h-4" />
            Nouveau Template
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="template in templates"
            :key="template.id"
            class="rounded-lg border bg-card p-4 hover:shadow-md transition-shadow"
          >
            <div class="flex items-start justify-between mb-3">
              <div>
                <h3 class="font-semibold">{{ template.name }}</h3>
                <span class="text-xs text-muted-foreground">{{ template.category || 'General' }}</span>
              </div>
              <div class="flex gap-1">
                <button @click="editTemplate(template)" class="p-1.5 hover:bg-accent rounded">
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button @click="deleteTemplate(template)" class="p-1.5 hover:bg-destructive/10 text-destructive rounded">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <p class="text-sm text-muted-foreground line-clamp-3 mb-3">{{ template.content }}</p>
            <div class="flex items-center justify-between text-xs">
              <span class="text-muted-foreground">{{ template.content.length }} caracteres</span>
              <button
                @click="useTemplate(template)"
                class="text-primary hover:underline font-medium"
              >
                Utiliser
              </button>
            </div>
          </div>
          <div v-if="templates.length === 0" class="col-span-full p-8 text-center text-muted-foreground border rounded-lg">
            <DocumentTextIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>Aucun template disponible</p>
          </div>
        </div>
      </div>

      <!-- Drafts Tab -->
      <div v-if="activeTab === 'drafts'" class="space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Brouillons</h2>
          <button
            v-if="drafts.length > 0"
            @click="clearAllDrafts"
            class="inline-flex items-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium hover:bg-accent"
          >
            <TrashIcon class="w-4 h-4" />
            Tout supprimer
          </button>
        </div>

        <div class="rounded-lg border bg-card">
          <table class="w-full">
            <thead>
              <tr class="border-b bg-muted/50">
                <th class="text-left p-4 font-medium text-sm">Nom</th>
                <th class="text-left p-4 font-medium text-sm">Apercu</th>
                <th class="text-left p-4 font-medium text-sm">Destinataires</th>
                <th class="text-left p-4 font-medium text-sm">Sauvegarde le</th>
                <th class="text-right p-4 font-medium text-sm">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="draft in drafts" :key="draft.id" class="border-b last:border-0 hover:bg-muted/30">
                <td class="p-4 font-medium">{{ draft.name }}</td>
                <td class="p-4 text-sm text-muted-foreground max-w-xs truncate">{{ draft.content }}</td>
                <td class="p-4 text-sm">{{ draft.recipients_count || 0 }} contact(s)</td>
                <td class="p-4 text-sm text-muted-foreground">{{ formatDate(draft.created_at) }}</td>
                <td class="p-4 text-right">
                  <button
                    @click="loadDraft(draft)"
                    class="px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded hover:bg-primary/90 mr-2"
                  >
                    Charger
                  </button>
                  <button @click="deleteDraft(draft)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="drafts.length === 0">
                <td colspan="5" class="p-8 text-center text-muted-foreground">
                  <BookmarkIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                  <p>Aucun brouillon sauvegarde</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Routes Tab -->
      <div v-if="activeTab === 'routes'" class="space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Routes SMS</h2>
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <InformationCircleIcon class="w-4 h-4" />
            Configuration des passerelles disponibles
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Airtel Route -->
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                  <SignalIcon class="w-6 h-6 text-red-600" />
                </div>
                <div>
                  <h3 class="font-semibold">Airtel Gabon</h3>
                  <p class="text-sm text-muted-foreground">API HTTP directe</p>
                </div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="routes.airtel.enabled" @change="toggleRoute('airtel')" class="sr-only peer">
                <div class="w-11 h-6 bg-muted peer-focus:ring-2 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Prefixes</span>
                <span class="font-medium">77, 74, 76</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Cout/SMS</span>
                <span class="font-medium">{{ routes.airtel.cost }} FCFA</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Statut</span>
                <span :class="routes.airtel.enabled ? 'text-success' : 'text-muted-foreground'">
                  {{ routes.airtel.enabled ? 'Actif' : 'Inactif' }}
                </span>
              </div>
            </div>
            <router-link
              to="/sms-config"
              class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium hover:bg-accent"
            >
              <Cog6ToothIcon class="w-4 h-4" />
              Configurer
            </router-link>
          </div>

          <!-- Moov Route -->
          <div class="rounded-lg border bg-card p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                  <SignalIcon class="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <h3 class="font-semibold">Moov Gabon</h3>
                  <p class="text-sm text-muted-foreground">Protocole SMPP</p>
                </div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="routes.moov.enabled" @change="toggleRoute('moov')" class="sr-only peer">
                <div class="w-11 h-6 bg-muted peer-focus:ring-2 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Prefixes</span>
                <span class="font-medium">60, 62, 65, 66</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Cout/SMS</span>
                <span class="font-medium">{{ routes.moov.cost }} FCFA</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Statut</span>
                <span :class="routes.moov.enabled ? 'text-success' : 'text-muted-foreground'">
                  {{ routes.moov.enabled ? 'Actif' : 'Inactif' }}
                </span>
              </div>
            </div>
            <router-link
              to="/sms-config"
              class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium hover:bg-accent"
            >
              <Cog6ToothIcon class="w-4 h-4" />
              Configurer
            </router-link>
          </div>
        </div>

        <!-- Fallback Configuration -->
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-3 mb-4">
            <ArrowPathIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Configuration Fallback</h3>
          </div>
          <p class="text-sm text-muted-foreground mb-4">
            Lorsque le fallback est active, si l'operateur principal echoue, le systeme bascule automatiquement vers l'operateur de secours.
          </p>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="fallbackEnabled" @change="toggleFallback" class="w-4 h-4 rounded border-input">
              <span class="text-sm font-medium">Activer le fallback automatique</span>
            </label>
          </div>
          <div v-if="fallbackEnabled" class="mt-4 p-3 bg-muted/50 rounded-lg text-sm">
            <p class="flex items-center gap-2">
              <CheckCircleIcon class="w-4 h-4 text-success" />
              Airtel → Moov (si Airtel echoue)
            </p>
            <p class="flex items-center gap-2 mt-1">
              <CheckCircleIcon class="w-4 h-4 text-success" />
              Moov → Airtel (si Moov echoue)
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Sender ID Modal -->
    <div v-if="showAddSenderModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/50" @click="showAddSenderModal = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-md p-6 m-4">
        <h3 class="text-lg font-semibold mb-4">{{ editingSender ? 'Modifier' : 'Nouveau' }} Sender ID</h3>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom du Sender ID</label>
            <input
              v-model="senderForm.name"
              type="text"
              placeholder="Ex: JOBSSMS"
              maxlength="11"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
            <p class="text-xs text-muted-foreground">Maximum 11 caracteres alphanumeriques</p>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium">Type</label>
            <select v-model="senderForm.type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="transactional">Transactionnel</option>
              <option value="marketing">Marketing</option>
            </select>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button
            @click="saveSender"
            :disabled="!senderForm.name"
            class="flex-1 h-10 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            {{ editingSender ? 'Modifier' : 'Creer' }}
          </button>
          <button
            @click="showAddSenderModal = false; editingSender = null"
            class="px-4 h-10 border rounded-lg hover:bg-accent"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>

    <!-- Add Template Modal -->
    <div v-if="showAddTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/50" @click="showAddTemplateModal = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-lg p-6 m-4">
        <h3 class="text-lg font-semibold mb-4">{{ editingTemplate ? 'Modifier' : 'Nouveau' }} Template</h3>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom du template</label>
            <input
              v-model="templateForm.name"
              type="text"
              placeholder="Ex: Confirmation commande"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium">Categorie</label>
            <select v-model="templateForm.category" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="">-- Selectionner --</option>
              <option value="notification">Notification</option>
              <option value="marketing">Marketing</option>
              <option value="verification">Verification</option>
              <option value="reminder">Rappel</option>
            </select>
          </div>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium">Contenu</label>
              <span class="text-xs text-muted-foreground">{{ templateForm.content.length }}/480</span>
            </div>
            <textarea
              v-model="templateForm.content"
              placeholder="Tapez votre message... Variables: {name}, {phone}, {code}"
              rows="5"
              maxlength="480"
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
            ></textarea>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button
            @click="saveTemplate"
            :disabled="!templateForm.name || !templateForm.content"
            class="flex-1 h-10 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            {{ editingTemplate ? 'Modifier' : 'Creer' }}
          </button>
          <button
            @click="showAddTemplateModal = false; editingTemplate = null"
            class="px-4 h-10 border rounded-lg hover:bg-accent"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import TabNav from '@/components/ui/TabNav.vue'
import {
  CogIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  DocumentTextIcon,
  BookmarkIcon,
  SignalIcon,
  Cog6ToothIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  InformationCircleIcon,
  IdentificationIcon
} from '@heroicons/vue/24/outline'
import apiClient from '@/services/api'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

interface SenderId {
  id: number
  name: string
  type: 'transactional' | 'marketing'
  status: 'pending' | 'approved' | 'rejected'
  created_at: string
}

interface Template {
  id: number
  name: string
  content: string
  category?: string
  created_at: string
}

interface Draft {
  id: number
  name: string
  content: string
  recipients_count: number
  created_at: string
}

const router = useRouter()

// Tabs
const tabs = [
  { id: 'senderid', label: 'Sender Id' },
  { id: 'templates', label: 'Templates' },
  { id: 'drafts', label: 'Drafts' },
  { id: 'routes', label: 'Routes' }
]
const activeTab = ref('senderid')

// Sender IDs
const senderIds = ref<SenderId[]>([])
const showAddSenderModal = ref(false)
const editingSender = ref<SenderId | null>(null)
const senderForm = ref({ name: '', type: 'transactional' as const })

// Templates
const templates = ref<Template[]>([])
const showAddTemplateModal = ref(false)
const editingTemplate = ref<Template | null>(null)
const templateForm = ref({ name: '', content: '', category: '' })

// Drafts
const drafts = ref<Draft[]>([])

// Routes
const routes = ref({
  airtel: { enabled: true, cost: 20 },
  moov: { enabled: false, cost: 20 }
})
const fallbackEnabled = ref(true)

// Methods
async function loadSenderIds() {
  // For now, use mock data - implement API endpoint later
  senderIds.value = [
    { id: 1, name: 'JOBSSMS', type: 'transactional', status: 'approved', created_at: '2026-01-15' },
    { id: 2, name: 'SendWave', type: 'transactional', status: 'approved', created_at: '2026-01-10' },
  ]
}

async function loadTemplates() {
  try {
    const response = await apiClient.get('/templates')
    templates.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error loading templates:', error)
  }
}

async function loadDrafts() {
  try {
    const response = await apiClient.get('/templates')
    // Filter templates that are drafts (based on name pattern)
    drafts.value = (response.data.data || response.data || [])
      .filter((t: Template) => t.name.toLowerCase().includes('brouillon'))
      .map((t: Template) => ({
        ...t,
        recipients_count: 0
      }))
  } catch (error) {
    console.error('Error loading drafts:', error)
  }
}

async function loadRoutes() {
  try {
    const response = await apiClient.get('/sms-configs')
    const configs = response.data.data || response.data || []

    const airtelConfig = configs.find((c: any) => c.provider === 'airtel')
    const moovConfig = configs.find((c: any) => c.provider === 'moov')

    if (airtelConfig) {
      routes.value.airtel.enabled = airtelConfig.is_active
      routes.value.airtel.cost = airtelConfig.cost_per_sms || 20
    }
    if (moovConfig) {
      routes.value.moov.enabled = moovConfig.is_active
      routes.value.moov.cost = moovConfig.cost_per_sms || 20
    }
  } catch (error) {
    console.error('Error loading routes:', error)
  }
}

function editSender(sender: SenderId) {
  editingSender.value = sender
  senderForm.value = { name: sender.name, type: sender.type }
  showAddSenderModal.value = true
}

async function saveSender() {
  if (!senderForm.value.name) return

  try {
    // Mock save - implement API endpoint later
    if (editingSender.value) {
      const index = senderIds.value.findIndex(s => s.id === editingSender.value!.id)
      if (index !== -1) {
        senderIds.value[index] = { ...senderIds.value[index], ...senderForm.value }
      }
      showSuccess('Sender ID modifie')
    } else {
      senderIds.value.push({
        id: Date.now(),
        ...senderForm.value,
        status: 'pending',
        created_at: new Date().toISOString()
      })
      showSuccess('Sender ID cree')
    }

    showAddSenderModal.value = false
    editingSender.value = null
    senderForm.value = { name: '', type: 'transactional' }
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function deleteSender(sender: SenderId) {
  const confirmed = await showConfirm('Supprimer ce Sender ID ?', `${sender.name} sera supprime definitivement.`)
  if (!confirmed) return

  senderIds.value = senderIds.value.filter(s => s.id !== sender.id)
  showSuccess('Sender ID supprime')
}

function editTemplate(template: Template) {
  editingTemplate.value = template
  templateForm.value = {
    name: template.name,
    content: template.content,
    category: template.category || ''
  }
  showAddTemplateModal.value = true
}

async function saveTemplate() {
  if (!templateForm.value.name || !templateForm.value.content) return

  try {
    if (editingTemplate.value) {
      await apiClient.put(`/templates/${editingTemplate.value.id}`, templateForm.value)
      showSuccess('Template modifie')
    } else {
      await apiClient.post('/templates', templateForm.value)
      showSuccess('Template cree')
    }

    showAddTemplateModal.value = false
    editingTemplate.value = null
    templateForm.value = { name: '', content: '', category: '' }
    loadTemplates()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function deleteTemplate(template: Template) {
  const confirmed = await showConfirm('Supprimer ce template ?', `"${template.name}" sera supprime definitivement.`)
  if (!confirmed) return

  try {
    await apiClient.delete(`/templates/${template.id}`)
    showSuccess('Template supprime')
    loadTemplates()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

function useTemplate(template: Template) {
  router.push({ path: '/send-sms', query: { template: template.id.toString() } })
}

function loadDraft(draft: Draft) {
  router.push({ path: '/send-sms', query: { draft: draft.id.toString() } })
}

async function deleteDraft(draft: Draft) {
  const confirmed = await showConfirm('Supprimer ce brouillon ?', 'Cette action est irreversible.')
  if (!confirmed) return

  try {
    await apiClient.delete(`/templates/${draft.id}`)
    showSuccess('Brouillon supprime')
    loadDrafts()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function clearAllDrafts() {
  const confirmed = await showConfirm('Supprimer tous les brouillons ?', 'Cette action est irreversible.')
  if (!confirmed) return

  try {
    for (const draft of drafts.value) {
      await apiClient.delete(`/templates/${draft.id}`)
    }
    showSuccess('Brouillons supprimes')
    loadDrafts()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function toggleRoute(provider: 'airtel' | 'moov') {
  try {
    await apiClient.post(`/sms-configs/${provider}/toggle`)
    showSuccess(`Route ${provider} ${routes.value[provider].enabled ? 'activee' : 'desactivee'}`)
  } catch (error: any) {
    // Revert
    routes.value[provider].enabled = !routes.value[provider].enabled
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function toggleFallback() {
  showSuccess(`Fallback ${fallbackEnabled.value ? 'active' : 'desactive'}`)
}

function getStatusClass(status: string): string {
  switch (status) {
    case 'approved': return 'bg-success/10 text-success'
    case 'pending': return 'bg-warning/10 text-warning'
    case 'rejected': return 'bg-destructive/10 text-destructive'
    default: return 'bg-muted text-muted-foreground'
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'approved': return 'Approuve'
    case 'pending': return 'En attente'
    case 'rejected': return 'Rejete'
    default: return status
  }
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

onMounted(() => {
  loadSenderIds()
  loadTemplates()
  loadDrafts()
  loadRoutes()
})
</script>
