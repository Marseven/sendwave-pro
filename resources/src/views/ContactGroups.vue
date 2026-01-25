<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="mb-4 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-xl sm:text-3xl font-bold">Groupes de Contacts</h1>
          <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Organisez vos contacts en groupes</p>
        </div>
        <button
          @click="openAddModal"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span class="hidden sm:inline">Nouveau groupe</span>
          <span class="sm:hidden">Nouveau</span>
        </button>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else-if="groups.length === 0" class="flex flex-col items-center justify-center py-8 sm:py-12 rounded-lg border bg-card">
        <UserGroupIcon class="w-12 h-12 sm:w-16 sm:h-16 text-muted-foreground mb-4" />
        <p class="text-base sm:text-lg font-medium">Aucun groupe trouvé</p>
        <p class="text-xs sm:text-sm text-muted-foreground mt-1">Créez des groupes pour organiser vos contacts</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
        <div
          v-for="group in groups"
          :key="group.id"
          class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="p-4 sm:p-6">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
              <div class="flex-1">
                <h3 class="font-semibold text-base sm:text-lg">{{ group.name }}</h3>
                <p v-if="group.description" class="text-xs sm:text-sm text-muted-foreground mt-1 line-clamp-2">
                  {{ group.description }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground mb-3 sm:mb-4">
              <UsersIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span>{{ group.contacts_count || 0 }} contact(s)</span>
            </div>

            <div class="text-xs text-muted-foreground">
              Créé le {{ formatDate(group.created_at) }}
            </div>
          </div>

          <div class="border-t p-3 sm:p-4 bg-muted/30 flex gap-2">
            <button
              @click="viewGroupContacts(group)"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9"
            >
              <EyeIcon class="w-4 h-4" />
              <span>Voir</span>
            </button>
            <button
              @click="openEditModal(group)"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
            >
              <PencilIcon class="w-4 h-4" />
            </button>
            <button
              @click="deleteGroup(group)"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:bg-destructive/10 hover:text-destructive h-9 w-9"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ajout/Modification -->
    <div
      v-if="showAddModal || showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4"
      @click.self="closeModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-sm sm:max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">{{ editingGroup ? 'Modifier le groupe' : 'Nouveau groupe' }}</h2>
            <button @click="closeModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <form @submit.prevent="saveGroup" class="p-6 space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom du groupe *</label>
            <input
              v-model="groupForm.name"
              type="text"
              required
              placeholder="Clients VIP"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Description</label>
            <textarea
              v-model="groupForm.description"
              rows="3"
              placeholder="Groupe de clients fidèles..."
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            ></textarea>
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
              <span>{{ saving ? 'Enregistrement...' : (editingGroup ? 'Modifier' : 'Créer') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Contacts du Groupe -->
    <div
      v-if="showContactsModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4"
      @click.self="closeContactsModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-sm sm:max-w-3xl max-h-[90vh] flex flex-col">
        <div class="border-b p-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold">{{ selectedGroup?.name }}</h2>
              <p class="text-sm text-muted-foreground mt-1">
                {{ groupContacts.length }} contact(s)
              </p>
            </div>
            <button @click="closeContactsModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="loadingContacts" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
          </div>

          <div v-else-if="groupContacts.length === 0" class="flex flex-col items-center justify-center py-12">
            <UsersIcon class="w-16 h-16 text-muted-foreground mb-4" />
            <p class="text-lg font-medium">Aucun contact dans ce groupe</p>
            <p class="text-sm text-muted-foreground mt-1">Ajoutez des contacts depuis la page Contacts</p>
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="contact in groupContacts"
              :key="contact.id"
              class="flex items-center justify-between p-4 rounded-lg border bg-card hover:bg-accent transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                  <UserIcon class="w-5 h-5 text-primary" />
                </div>
                <div>
                  <p class="font-medium">{{ contact.name }}</p>
                  <p class="text-sm text-muted-foreground">{{ contact.phone }}</p>
                </div>
              </div>
              <button
                @click="removeContactFromGroup(contact.id)"
                class="text-sm text-destructive hover:underline"
              >
                Retirer
              </button>
            </div>
          </div>
        </div>

        <div class="border-t p-6">
          <button
            @click="closeContactsModal"
            class="w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  PlusIcon, PencilIcon, TrashIcon, XMarkIcon, UserGroupIcon,
  EyeIcon, UsersIcon, UserIcon
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

interface ContactGroup {
  id: number
  name: string
  description: string | null
  contacts_count: number
  created_at: string
  updated_at: string
}

interface Contact {
  id: number
  name: string
  phone: string
  email: string | null
}

const groups = ref<ContactGroup[]>([])
const groupContacts = ref<Contact[]>([])
const loading = ref(false)
const loadingContacts = ref(false)
const saving = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showContactsModal = ref(false)
const editingGroup = ref<ContactGroup | null>(null)
const selectedGroup = ref<ContactGroup | null>(null)

const groupForm = ref({
  name: '',
  description: ''
})

async function loadGroups() {
  loading.value = true
  try {
    const response = await api.get('/contact-groups')
    groups.value = response.data.data
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement')
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingGroup.value = null
  groupForm.value = {
    name: '',
    description: ''
  }
  showAddModal.value = true
}

function openEditModal(group: ContactGroup) {
  editingGroup.value = group
  groupForm.value = {
    name: group.name,
    description: group.description || ''
  }
  showEditModal.value = true
}

function closeModal() {
  showAddModal.value = false
  showEditModal.value = false
  editingGroup.value = null
}

async function saveGroup() {
  saving.value = true
  try {
    if (editingGroup.value) {
      await api.put(`/contact-groups/${editingGroup.value.id}`, groupForm.value)
      showSuccess('Groupe modifié avec succès')
    } else {
      await api.post('/contact-groups', groupForm.value)
      showSuccess('Groupe créé avec succès')
    }
    closeModal()
    await loadGroups()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

async function deleteGroup(group: ContactGroup) {
  const confirmed = await showConfirm(
    'Supprimer le groupe ?',
    `Êtes-vous sûr de vouloir supprimer "${group.name}" ? Les contacts ne seront pas supprimés.`
  )

  if (confirmed) {
    try {
      await api.delete(`/contact-groups/${group.id}`)
      showSuccess('Groupe supprimé avec succès')
      await loadGroups()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur lors de la suppression')
    }
  }
}

async function viewGroupContacts(group: ContactGroup) {
  selectedGroup.value = group
  showContactsModal.value = true
  loadingContacts.value = true

  try {
    const response = await api.get(`/contact-groups/${group.id}/contacts`)
    groupContacts.value = response.data.data
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement des contacts')
  } finally {
    loadingContacts.value = false
  }
}

function closeContactsModal() {
  showContactsModal.value = false
  selectedGroup.value = null
  groupContacts.value = []
}

async function removeContactFromGroup(contactId: number) {
  if (!selectedGroup.value) return

  const confirmed = await showConfirm(
    'Retirer du groupe ?',
    'Voulez-vous retirer ce contact du groupe ?'
  )

  if (confirmed) {
    try {
      await api.post(`/contact-groups/${selectedGroup.value.id}/contacts/remove`, {
        contact_ids: [contactId]
      })
      showSuccess('Contact retiré du groupe')
      groupContacts.value = groupContacts.value.filter(c => c.id !== contactId)
      await loadGroups()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur lors du retrait')
    }
  }
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date)
}

onMounted(() => {
  loadGroups()
})
</script>
