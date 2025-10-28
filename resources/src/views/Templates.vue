<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Modèles SMS</h1>
          <p class="text-muted-foreground mt-2">Créez et gérez vos modèles de messages</p>
        </div>
        <button
          @click="openAddModal"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Nouveau modèle</span>
        </button>
      </div>

      <!-- Statistiques -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Total modèles</div>
          <div class="text-2xl font-bold mt-1">{{ templates.length }}</div>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Actifs</div>
          <div class="text-2xl font-bold mt-1 text-success">{{ activeTemplates }}</div>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Utilisations</div>
          <div class="text-2xl font-bold mt-1 text-primary">{{ totalUses.toLocaleString('fr-FR') }}</div>
        </div>
      </div>

      <!-- Grille de modèles -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
      <div v-else-if="templates.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
        <DocumentTextIcon class="w-16 h-16 text-muted-foreground mb-4" />
        <p class="text-lg font-medium">Aucun modèle trouvé</p>
        <p class="text-sm text-muted-foreground mt-1">Créez votre premier modèle pour commencer</p>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="template in templates"
          :key="template.id"
          class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="p-6">
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                  <component :is="getIconComponent(template.icon)" class="w-6 h-6 text-primary" />
                </div>
                <div>
                  <h3 class="font-semibold">{{ template.name }}</h3>
                  <p class="text-xs text-muted-foreground">{{ template.category }}</p>
                </div>
              </div>
              <span
                class="text-xs px-2 py-1 rounded-full"
                :class="template.status === 'active' ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'"
              >
                {{ template.status === 'active' ? 'Actif' : 'Inactif' }}
              </span>
            </div>

            <div class="mb-4 p-3 bg-muted/50 rounded-lg">
              <p class="text-sm text-muted-foreground line-clamp-3">{{ template.message }}</p>
            </div>

            <div class="flex items-center justify-between text-xs text-muted-foreground mb-4">
              <span>{{ template.uses || 0 }} utilisations</span>
              <span>{{ template.message.length }} caractères</span>
            </div>

            <div class="flex gap-2">
              <button
                @click="openEditModal(template)"
                class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9"
              >
                <PencilIcon class="w-4 h-4" />
                <span>Modifier</span>
              </button>
              <button
                @click="deleteTemplate(template)"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-destructive/10 hover:text-destructive h-9 w-9"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
              <button
                @click="useTemplate(template)"
                class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9"
              >
                <PaperAirplaneIcon class="w-4 h-4" />
                <span>Utiliser</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ajout/Modification -->
    <div
      v-if="showAddModal || showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="closeModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">{{ editingTemplate ? 'Modifier le modèle' : 'Nouveau modèle' }}</h2>
          <button @click="closeModal" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveTemplate" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Nom du modèle *</label>
              <input
                v-model="templateForm.name"
                type="text"
                required
                placeholder="Promotion été"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Catégorie *</label>
              <select
                v-model="templateForm.category"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              >
                <option value="">Sélectionner...</option>
                <option value="Marketing">Marketing</option>
                <option value="Transactionnel">Transactionnel</option>
                <option value="Notification">Notification</option>
                <option value="Rappel">Rappel</option>
                <option value="Confirmation">Confirmation</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium">Message *</label>
              <span class="text-xs text-muted-foreground">{{ templateForm.message.length }}/320 caractères</span>
            </div>
            <textarea
              v-model="templateForm.message"
              required
              rows="6"
              maxlength="320"
              placeholder="Votre message avec des variables {{name}}, {{email}}, etc."
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none font-mono"
            ></textarea>
          </div>

          <div class="p-4 bg-muted/50 rounded-lg">
            <p class="text-sm font-medium mb-3">Variables disponibles :</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="variable in variables"
                :key="variable"
                type="button"
                @click="insertVariable(variable)"
                class="text-xs px-3 py-1.5 rounded-md bg-background hover:bg-primary hover:text-primary-foreground border border-border hover:border-primary transition-colors font-mono"
              >
                {{ variable }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Icône</label>
              <select
                v-model="templateForm.icon"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              >
                <option value="document">Document</option>
                <option value="hand">Main levée</option>
                <option value="sun">Soleil</option>
                <option value="check">Check</option>
                <option value="calendar">Calendrier</option>
                <option value="cake">Gâteau</option>
                <option value="gift">Cadeau</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Statut</label>
              <select
                v-model="templateForm.status"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              >
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
              </select>
            </div>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeModal"
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
              <span>{{ saving ? 'Enregistrement...' : (editingTemplate ? 'Modifier' : 'Créer') }}</span>
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
import type { Component } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  PlusIcon,
  PencilIcon,
  PaperAirplaneIcon,
  TrashIcon,
  XMarkIcon,
  HandRaisedIcon,
  SunIcon,
  CheckCircleIcon,
  CalendarIcon,
  CakeIcon,
  GiftIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import { templateService, type Template } from '@/services/templateService'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

const router = useRouter()
const showAddModal = ref(false)
const showEditModal = ref(false)
const templates = ref<Template[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const editingTemplate = ref<Template | null>(null)

const variables = ['{{name}}', '{{email}}', '{{phone}}', '{{code}}']

const templateForm = ref({
  name: '',
  category: '',
  message: '',
  icon: 'document',
  status: 'active' as 'active' | 'inactive'
})

const iconMap: Record<string, Component> = {
  'hand': HandRaisedIcon,
  'sun': SunIcon,
  'check': CheckCircleIcon,
  'calendar': CalendarIcon,
  'cake': CakeIcon,
  'gift': GiftIcon,
  'document': DocumentTextIcon
}

function getIconComponent(iconName: string): Component {
  return iconMap[iconName] || DocumentTextIcon
}

const activeTemplates = computed(() => {
  return templates.value.filter(t => t.status === 'active').length
})

const totalUses = computed(() => {
  return templates.value.reduce((sum, t) => sum + (t.uses || 0), 0)
})

async function loadTemplates() {
  loading.value = true
  error.value = ''
  try {
    templates.value = await templateService.getAll()
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des modèles'
    console.error('Error loading templates:', err)
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingTemplate.value = null
  templateForm.value = {
    name: '',
    category: '',
    message: '',
    icon: 'document',
    status: 'active'
  }
  showAddModal.value = true
}

function openEditModal(template: Template) {
  editingTemplate.value = template
  templateForm.value = {
    name: template.name,
    category: template.category,
    message: template.message,
    icon: template.icon || 'document',
    status: template.status
  }
  showEditModal.value = true
}

function closeModal() {
  showAddModal.value = false
  showEditModal.value = false
  editingTemplate.value = null
}

function insertVariable(variable: string) {
  templateForm.value.message += variable
}

async function saveTemplate() {
  saving.value = true
  try {
    if (editingTemplate.value) {
      await templateService.update(editingTemplate.value.id, templateForm.value)
      showSuccess('Modèle modifié avec succès')
    } else {
      await templateService.create(templateForm.value)
      showSuccess('Modèle créé avec succès')
    }
    closeModal()
    await loadTemplates()
  } catch (err: any) {
    showError(err.response?.data?.message || err.message || 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

async function deleteTemplate(template: Template) {
  const confirmed = await showConfirm(
    'Supprimer le modèle ?',
    `Êtes-vous sûr de vouloir supprimer "${template.name}" ?`
  )

  if (confirmed) {
    try {
      await templateService.delete(template.id)
      showSuccess('Modèle supprimé avec succès')
      await loadTemplates()
    } catch (err: any) {
      showError(err.message || 'Erreur lors de la suppression')
    }
  }
}

function useTemplate(template: Template) {
  router.push({
    path: '/campaign/create',
    query: { templateId: template.id.toString() }
  })
}

onMounted(() => {
  loadTemplates()
})
</script>
