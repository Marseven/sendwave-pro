<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
              <CircleStackIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
              Base de données
            </h1>
            <p class="text-sm text-muted-foreground mt-1">Gérez vos contacts et groupes</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="showCreateGroup = true"
              class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 h-9 sm:h-10"
            >
              <PlusIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Nouveau groupe</span>
              <span class="sm:hidden">Nouveau</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <TabNav
        v-model="activeTab"
        :tabs="tabs"
        class="mb-6"
      />

      <!-- My Groups Tab -->
      <div v-if="activeTab === 'groups'" class="space-y-6">
        <!-- Search and Filter -->
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1 relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
            <input
              v-model="groupSearch"
              type="text"
              placeholder="Rechercher un groupe..."
              class="w-full h-10 pl-10 pr-4 rounded-md border border-input bg-background text-sm"
            />
          </div>
          <select
            v-model="groupSort"
            class="h-10 px-3 rounded-md border border-input bg-background text-sm"
          >
            <option value="name">Trier par nom</option>
            <option value="contacts">Par nombre de contacts</option>
            <option value="recent">Plus recents</option>
          </select>
        </div>

        <!-- Groups Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="group in filteredGroups"
            :key="group.id"
            class="rounded-lg border bg-card p-5 hover:shadow-md transition-shadow"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                  <FolderIcon class="w-5 h-5 text-primary" />
                </div>
                <div>
                  <h3 class="font-semibold">{{ group.name }}</h3>
                  <p class="text-xs text-muted-foreground">{{ group.description || 'Aucune description' }}</p>
                </div>
              </div>
              <div class="relative">
                <button
                  @click="toggleGroupMenu(group.id)"
                  class="p-1 rounded hover:bg-accent"
                >
                  <EllipsisVerticalIcon class="w-5 h-5 text-muted-foreground" />
                </button>
                <div
                  v-if="openGroupMenu === group.id"
                  class="absolute right-0 mt-1 w-40 bg-card border rounded-md shadow-lg z-10"
                >
                  <button
                    @click="editGroup(group)"
                    class="w-full px-3 py-2 text-sm text-left hover:bg-accent flex items-center gap-2"
                  >
                    <PencilIcon class="w-4 h-4" />
                    Modifier
                  </button>
                  <button
                    @click="viewGroupContacts(group)"
                    class="w-full px-3 py-2 text-sm text-left hover:bg-accent flex items-center gap-2"
                  >
                    <UsersIcon class="w-4 h-4" />
                    Voir contacts
                  </button>
                  <button
                    @click="deleteGroup(group)"
                    class="w-full px-3 py-2 text-sm text-left hover:bg-destructive/10 text-destructive flex items-center gap-2"
                  >
                    <TrashIcon class="w-4 h-4" />
                    Supprimer
                  </button>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
              <div class="flex items-center gap-1.5 text-muted-foreground">
                <UsersIcon class="w-4 h-4" />
                <span>{{ group.contacts_count || 0 }} contacts</span>
              </div>
              <div class="flex items-center gap-1.5 text-muted-foreground">
                <ClockIcon class="w-4 h-4" />
                <span>{{ formatDate(group.created_at) }}</span>
              </div>
            </div>
            <div class="mt-4 pt-4 border-t flex gap-2">
              <button
                @click="sendToGroup(group)"
                class="flex-1 h-9 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90"
              >
                Envoyer SMS
              </button>
              <button
                @click="exportGroup(group)"
                class="h-9 px-3 text-sm font-medium rounded-md border hover:bg-accent"
              >
                <ArrowDownTrayIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredGroups.length === 0" class="text-center py-12">
          <FolderIcon class="w-16 h-16 mx-auto text-muted-foreground mb-4" />
          <h3 class="text-lg font-semibold mb-2">Aucun groupe</h3>
          <p class="text-muted-foreground mb-4">Creez votre premier groupe de contacts</p>
          <button
            @click="showCreateGroup = true"
            class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium"
          >
            Creer un groupe
          </button>
        </div>
      </div>

      <!-- Import Contact Tab -->
      <div v-if="activeTab === 'import'" class="max-w-2xl mx-auto space-y-6">
        <div class="rounded-lg border bg-card p-6 space-y-6">
          <div class="flex items-center gap-2 pb-4 border-b">
            <ArrowUpTrayIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Importer des contacts</h3>
          </div>

          <!-- Select Group -->
          <div class="space-y-2">
            <label class="text-sm font-medium">Groupe de destination *</label>
            <div class="flex gap-2">
              <select
                v-model="importGroup"
                class="flex-1 h-10 px-3 rounded-md border border-input bg-background text-sm"
              >
                <option value="">Selectionner un groupe...</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }}
                </option>
              </select>
              <button
                @click="showCreateGroup = true"
                class="h-10 px-3 border rounded-md hover:bg-accent"
              >
                <PlusIcon class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- File Upload -->
          <div
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="handleFileDrop"
            class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
            :class="isDragging ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'"
          >
            <input
              type="file"
              ref="fileInput"
              @change="handleFileSelect"
              accept=".csv,.xlsx,.xls"
              class="hidden"
            />
            <CloudArrowUpIcon class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
            <p class="text-sm font-medium mb-2">Glissez-deposez votre fichier ici</p>
            <p class="text-xs text-muted-foreground mb-4">ou</p>
            <button
              @click="$refs.fileInput.click()"
              class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90"
            >
              Parcourir
            </button>
            <p class="text-xs text-muted-foreground mt-4">
              Formats acceptes: CSV, Excel (.xlsx, .xls)
            </p>
          </div>

          <!-- File Preview -->
          <div v-if="importFile" class="space-y-4">
            <div class="p-4 bg-muted/50 rounded-lg flex items-center justify-between">
              <div class="flex items-center gap-3">
                <DocumentTextIcon class="w-8 h-8 text-primary" />
                <div>
                  <p class="font-medium">{{ importFile.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ importFile.size }}</p>
                </div>
              </div>
              <button @click="importFile = null" class="text-muted-foreground hover:text-destructive">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- Column Mapping -->
            <div class="space-y-4">
              <h4 class="font-medium">Mapping des colonnes</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium">Colonne Telephone *</label>
                  <select v-model="columnMapping.phone" class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm">
                    <option v-for="col in importFile.columns" :key="col" :value="col">{{ col }}</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">Colonne Nom</label>
                  <select v-model="columnMapping.name" class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm">
                    <option value="">-- Aucune --</option>
                    <option v-for="col in importFile.columns" :key="col" :value="col">{{ col }}</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">Colonne Email</label>
                  <select v-model="columnMapping.email" class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm">
                    <option value="">-- Aucune --</option>
                    <option v-for="col in importFile.columns" :key="col" :value="col">{{ col }}</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Import Options -->
            <div class="space-y-3">
              <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-accent/50">
                <input type="checkbox" v-model="importOptions.skipDuplicates" class="w-4 h-4 rounded" />
                <div>
                  <p class="font-medium text-sm">Ignorer les doublons</p>
                  <p class="text-xs text-muted-foreground">Ne pas importer les numeros existants</p>
                </div>
              </label>
              <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-accent/50">
                <input type="checkbox" v-model="importOptions.validateNumbers" class="w-4 h-4 rounded" />
                <div>
                  <p class="font-medium text-sm">Valider les numeros</p>
                  <p class="text-xs text-muted-foreground">Verifier le format Gabon</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Import Button -->
          <button
            @click="processImport"
            :disabled="!canImport || importing"
            class="w-full h-12 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            <span v-if="importing" class="flex items-center justify-center gap-2">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
              Importation en cours...
            </span>
            <span v-else>Importer les contacts</span>
          </button>
        </div>

        <!-- Import Template -->
        <div class="rounded-lg border bg-card p-6">
          <h3 class="font-semibold mb-4">Modele de fichier</h3>
          <p class="text-sm text-muted-foreground mb-4">
            Telechargez notre modele pour formater correctement vos contacts
          </p>
          <button
            @click="downloadTemplate"
            class="inline-flex items-center gap-2 px-4 py-2 border rounded-lg hover:bg-accent text-sm font-medium"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Telecharger le modele CSV
          </button>
        </div>
      </div>

      <!-- Export Contact Tab -->
      <div v-if="activeTab === 'export'" class="max-w-2xl mx-auto space-y-6">
        <div class="rounded-lg border bg-card p-6 space-y-6">
          <div class="flex items-center gap-2 pb-4 border-b">
            <ArrowDownTrayIcon class="w-5 h-5 text-primary" />
            <h3 class="font-semibold">Exporter des contacts</h3>
          </div>

          <!-- Export Source -->
          <div class="space-y-4">
            <label class="text-sm font-medium">Source des contacts</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label
                class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50"
                :class="exportSource === 'all' ? 'border-primary bg-primary/5' : 'border-border'"
              >
                <input type="radio" v-model="exportSource" value="all" class="w-4 h-4" />
                <div>
                  <p class="font-medium text-sm">Tous les contacts</p>
                  <p class="text-xs text-muted-foreground">{{ totalContacts }} contacts</p>
                </div>
              </label>
              <label
                class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50"
                :class="exportSource === 'group' ? 'border-primary bg-primary/5' : 'border-border'"
              >
                <input type="radio" v-model="exportSource" value="group" class="w-4 h-4" />
                <div>
                  <p class="font-medium text-sm">Un groupe specifique</p>
                  <p class="text-xs text-muted-foreground">Selectionner ci-dessous</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Group Selection -->
          <div v-if="exportSource === 'group'" class="space-y-2">
            <label class="text-sm font-medium">Groupe a exporter</label>
            <select
              v-model="exportGroupId"
              class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm"
            >
              <option value="">Selectionner un groupe...</option>
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.name }} ({{ group.contacts_count }} contacts)
              </option>
            </select>
          </div>

          <!-- Export Format -->
          <div class="space-y-2">
            <label class="text-sm font-medium">Format d'export</label>
            <div class="grid grid-cols-3 gap-3">
              <label
                v-for="format in exportFormats"
                :key="format.value"
                class="flex flex-col items-center gap-2 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50"
                :class="exportFormat === format.value ? 'border-primary bg-primary/5' : 'border-border'"
              >
                <input type="radio" v-model="exportFormat" :value="format.value" class="hidden" />
                <component :is="format.icon" class="w-8 h-8 text-muted-foreground" />
                <span class="font-medium text-sm">{{ format.label }}</span>
              </label>
            </div>
          </div>

          <!-- Export Fields -->
          <div class="space-y-2">
            <label class="text-sm font-medium">Champs a exporter</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                v-for="field in exportFields"
                :key="field.value"
                class="flex items-center gap-2 p-2 rounded border hover:bg-accent/50 cursor-pointer"
              >
                <input
                  type="checkbox"
                  v-model="selectedExportFields"
                  :value="field.value"
                  class="w-4 h-4 rounded"
                />
                <span class="text-sm">{{ field.label }}</span>
              </label>
            </div>
          </div>

          <!-- Export Button -->
          <button
            @click="processExport"
            :disabled="!canExport || exporting"
            class="w-full h-12 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            <span v-if="exporting" class="flex items-center justify-center gap-2">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
              Exportation en cours...
            </span>
            <span v-else>Exporter les contacts</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Create Group Modal -->
    <div v-if="showCreateGroup" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/50" @click="showCreateGroup = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-md p-6 m-4">
        <h3 class="text-lg font-semibold mb-4">{{ editingGroup ? 'Modifier le groupe' : 'Nouveau groupe' }}</h3>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom du groupe *</label>
            <input
              v-model="groupForm.name"
              type="text"
              placeholder="Ex: Clients VIP"
              class="w-full h-10 px-3 rounded-md border border-input bg-background text-sm"
            />
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium">Description</label>
            <textarea
              v-model="groupForm.description"
              placeholder="Description du groupe..."
              rows="3"
              class="w-full px-3 py-2 rounded-md border border-input bg-background text-sm resize-none"
            ></textarea>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button
            @click="saveGroup"
            :disabled="!groupForm.name"
            class="flex-1 h-10 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            {{ editingGroup ? 'Enregistrer' : 'Creer' }}
          </button>
          <button
            @click="closeGroupModal"
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import TabNav from '@/components/ui/TabNav.vue'
import {
  CircleStackIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  FolderIcon,
  UsersIcon,
  ClockIcon,
  EllipsisVerticalIcon,
  PencilIcon,
  TrashIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  CloudArrowUpIcon,
  DocumentTextIcon,
  XMarkIcon,
  TableCellsIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline'
import apiClient from '@/services/api'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

interface Group {
  id: number
  name: string
  description: string | null
  contacts_count: number
  created_at: string
}

const router = useRouter()

// Tabs
const tabs = [
  { id: 'groups', label: 'My Groups' },
  { id: 'import', label: 'Import Contact' },
  { id: 'export', label: 'Export Contact' }
]
const activeTab = ref('groups')

// Groups
const groups = ref<Group[]>([])
const groupSearch = ref('')
const groupSort = ref('name')
const openGroupMenu = ref<number | null>(null)
const showCreateGroup = ref(false)
const editingGroup = ref<Group | null>(null)
const groupForm = ref({ name: '', description: '' })

// Import
const fileInput = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const importFile = ref<{ name: string; size: string; columns: string[] } | null>(null)
const importGroup = ref('')
const columnMapping = ref({ phone: '', name: '', email: '' })
const importOptions = ref({ skipDuplicates: true, validateNumbers: true })
const importing = ref(false)

// Export
const exportSource = ref('all')
const exportGroupId = ref('')
const exportFormat = ref('csv')
const selectedExportFields = ref(['phone', 'name', 'email'])
const exporting = ref(false)
const totalContacts = ref(0)

const exportFormats = [
  { value: 'csv', label: 'CSV', icon: DocumentTextIcon },
  { value: 'xlsx', label: 'Excel', icon: TableCellsIcon },
  { value: 'txt', label: 'TXT', icon: DocumentIcon }
]

const exportFields = [
  { value: 'phone', label: 'Telephone' },
  { value: 'name', label: 'Nom' },
  { value: 'email', label: 'Email' },
  { value: 'created_at', label: 'Date creation' },
  { value: 'status', label: 'Statut' }
]

// Computed
const filteredGroups = computed(() => {
  let result = [...groups.value]

  if (groupSearch.value) {
    const search = groupSearch.value.toLowerCase()
    result = result.filter(g => g.name.toLowerCase().includes(search))
  }

  if (groupSort.value === 'name') {
    result.sort((a, b) => a.name.localeCompare(b.name))
  } else if (groupSort.value === 'contacts') {
    result.sort((a, b) => (b.contacts_count || 0) - (a.contacts_count || 0))
  } else if (groupSort.value === 'recent') {
    result.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
  }

  return result
})

const canImport = computed(() => {
  return importGroup.value && importFile.value && columnMapping.value.phone
})

const canExport = computed(() => {
  if (exportSource.value === 'group' && !exportGroupId.value) return false
  return selectedExportFields.value.length > 0
})

// Methods
async function loadGroups() {
  try {
    const response = await apiClient.get('/contact-groups')
    groups.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error loading groups:', error)
  }
}

async function loadTotalContacts() {
  try {
    const response = await apiClient.get('/contacts?per_page=1')
    totalContacts.value = response.data.total || response.data.data?.length || 0
  } catch (error) {
    console.error('Error loading contacts count:', error)
  }
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

function toggleGroupMenu(id: number) {
  openGroupMenu.value = openGroupMenu.value === id ? null : id
}

function editGroup(group: Group) {
  editingGroup.value = group
  groupForm.value = { name: group.name, description: group.description || '' }
  showCreateGroup.value = true
  openGroupMenu.value = null
}

function viewGroupContacts(group: Group) {
  router.push(`/contacts?group=${group.id}`)
  openGroupMenu.value = null
}

async function deleteGroup(group: Group) {
  openGroupMenu.value = null
  const confirmed = await showConfirm(
    'Supprimer le groupe',
    `Etes-vous sur de vouloir supprimer "${group.name}" ? Les contacts ne seront pas supprimes.`
  )

  if (confirmed) {
    try {
      await apiClient.delete(`/contact-groups/${group.id}`)
      showSuccess('Groupe supprime')
      loadGroups()
    } catch (error: any) {
      showError(error.response?.data?.message || 'Erreur lors de la suppression')
    }
  }
}

function sendToGroup(group: Group) {
  router.push(`/send-sms?group=${group.id}`)
}

function exportGroup(group: Group) {
  activeTab.value = 'export'
  exportSource.value = 'group'
  exportGroupId.value = String(group.id)
}

function closeGroupModal() {
  showCreateGroup.value = false
  editingGroup.value = null
  groupForm.value = { name: '', description: '' }
}

async function saveGroup() {
  try {
    if (editingGroup.value) {
      await apiClient.put(`/contact-groups/${editingGroup.value.id}`, groupForm.value)
      showSuccess('Groupe modifie')
    } else {
      await apiClient.post('/contact-groups', groupForm.value)
      showSuccess('Groupe cree')
    }
    closeGroupModal()
    loadGroups()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur lors de l\'enregistrement')
  }
}

function handleFileDrop(e: DragEvent) {
  isDragging.value = false
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    processFile(files[0])
  }
}

function handleFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    processFile(target.files[0])
  }
}

function processFile(file: File) {
  const size = file.size < 1024 * 1024
    ? `${(file.size / 1024).toFixed(1)} KB`
    : `${(file.size / (1024 * 1024)).toFixed(1)} MB`

  importFile.value = {
    name: file.name,
    size,
    columns: ['phone', 'name', 'email', 'company'] // Placeholder - would parse actual file
  }
  columnMapping.value = { phone: 'phone', name: 'name', email: 'email' }
}

async function processImport() {
  if (!canImport.value) return

  importing.value = true

  try {
    // In real implementation, send file to API
    await new Promise(resolve => setTimeout(resolve, 1500)) // Simulate

    showSuccess('Contacts importes avec succes')
    importFile.value = null
    loadGroups()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur lors de l\'import')
  } finally {
    importing.value = false
  }
}

function downloadTemplate() {
  const csv = 'phone,name,email\n+241771234567,Jean Dupont,jean@example.com\n+241621234567,Marie Martin,marie@example.com'
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'contacts_template.csv'
  a.click()
  URL.revokeObjectURL(url)
}

async function processExport() {
  if (!canExport.value) return

  exporting.value = true

  try {
    const params: Record<string, any> = {
      format: exportFormat.value,
      fields: selectedExportFields.value.join(',')
    }

    if (exportSource.value === 'group') {
      params.group_id = exportGroupId.value
    }

    const response = await apiClient.get('/contacts/export', {
      params,
      responseType: 'blob'
    })

    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `contacts_export.${exportFormat.value}`
    a.click()
    URL.revokeObjectURL(url)

    showSuccess('Export termine')
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur lors de l\'export')
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  loadGroups()
  loadTotalContacts()
})
</script>
