<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Sous-comptes</h1>
          <p class="text-muted-foreground mt-2">Gérez les accès cloisonnés avec permissions et crédits SMS</p>
        </div>
        <button
          @click="openAddModal"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Ajouter un sous-compte</span>
        </button>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else-if="accounts.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
        <UserGroupIcon class="w-16 h-16 text-muted-foreground mb-4" />
        <p class="text-lg font-medium">Aucun sous-compte trouvé</p>
        <p class="text-sm text-muted-foreground mt-1">Créez des comptes avec accès cloisonné</p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <div
          v-for="account in accounts"
          :key="account.id"
          class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="p-6">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="font-semibold text-lg">{{ account.name }}</h3>
                <p class="text-sm text-muted-foreground mt-1">{{ account.email }}</p>
              </div>
              <span
                class="text-xs px-2.5 py-1 rounded-full font-medium"
                :class="{
                  'bg-green-100 text-green-700': account.status === 'active',
                  'bg-red-100 text-red-700': account.status === 'suspended',
                  'bg-gray-100 text-gray-700': account.status === 'inactive'
                }"
              >
                {{ statusLabels[account.status] }}
              </span>
            </div>

            <div class="flex items-center gap-2 mb-4">
              <span
                class="text-xs px-2.5 py-1 rounded-md font-medium"
                :class="{
                  'bg-purple-100 text-purple-700': account.role === 'admin',
                  'bg-blue-100 text-blue-700': account.role === 'manager',
                  'bg-teal-100 text-teal-700': account.role === 'sender',
                  'bg-gray-100 text-gray-700': account.role === 'viewer'
                }"
              >
                {{ roleLabels[account.role] }}
              </span>
            </div>

            <div class="space-y-3 mb-4">
              <div class="flex justify-between items-center text-sm">
                <span class="text-muted-foreground">Crédits SMS</span>
                <span class="font-semibold">
                  {{ account.remaining_credits === null ? 'Illimité' : account.remaining_credits.toLocaleString('fr-FR') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-muted-foreground">Utilisés</span>
                <span>{{ (account.sms_used || 0).toLocaleString('fr-FR') }}</span>
              </div>
              <div v-if="account.remaining_credits !== null" class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="bg-primary h-2 rounded-full transition-all"
                  :style="{ width: getUsagePercentage(account) + '%' }"
                ></div>
              </div>
              <div v-if="account.last_connection" class="text-xs text-muted-foreground">
                Dernière connexion: {{ formatDate(account.last_connection) }}
              </div>
            </div>
          </div>

          <div class="border-t p-4 bg-muted/30 flex gap-2">
            <button
              @click="openEditModal(account)"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9"
            >
              <PencilIcon class="w-4 h-4" />
              <span>Modifier</span>
            </button>
            <button
              @click="openPermissionsModal(account)"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
            >
              <KeyIcon class="w-4 h-4" />
            </button>
            <button
              v-if="account.status === 'active'"
              @click="suspendAccount(account)"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:bg-destructive/10 hover:text-destructive h-9 w-9"
            >
              <LockClosedIcon class="w-4 h-4" />
            </button>
            <button
              v-else-if="account.status === 'suspended'"
              @click="activateAccount(account)"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:bg-success/10 hover:text-success h-9 w-9"
            >
              <LockOpenIcon class="w-4 h-4" />
            </button>
            <button
              @click="deleteAccount(account)"
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
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-background border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">{{ editingAccount ? 'Modifier le sous-compte' : 'Nouveau sous-compte' }}</h2>
            <button @click="closeModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <form @submit.prevent="saveAccount" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Nom complet *</label>
              <input
                v-model="accountForm.name"
                type="text"
                required
                placeholder="Jean Dupont"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Email *</label>
              <input
                v-model="accountForm.email"
                type="email"
                required
                :disabled="!!editingAccount"
                placeholder="jean@example.com"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
              />
            </div>
          </div>

          <div v-if="!editingAccount" class="space-y-2">
            <label class="text-sm font-medium">Mot de passe *</label>
            <input
              v-model="accountForm.password"
              type="password"
              :required="!editingAccount"
              placeholder="Minimum 8 caractères"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
            <p class="text-xs text-muted-foreground">Laissez vide pour ne pas modifier (mode édition)</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Rôle *</label>
              <select
                v-model="accountForm.role"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              >
                <option value="admin">Admin (tous les droits)</option>
                <option value="manager">Manager (gestion avancée)</option>
                <option value="sender">Sender (envoi SMS)</option>
                <option value="viewer">Viewer (consultation uniquement)</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Statut</label>
              <select
                v-model="accountForm.status"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              >
                <option value="active">Actif</option>
                <option value="suspended">Suspendu</option>
                <option value="inactive">Inactif</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Limite de crédits SMS</label>
            <div class="flex gap-2">
              <input
                v-model.number="accountForm.sms_credit_limit"
                type="number"
                min="0"
                :disabled="unlimitedCredits"
                placeholder="1000"
                class="flex h-10 flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
              />
              <label class="inline-flex items-center gap-2 px-4 py-2 border rounded-md cursor-pointer hover:bg-accent">
                <input
                  v-model="unlimitedCredits"
                  type="checkbox"
                  class="rounded border-gray-300"
                />
                <span class="text-sm">Illimité</span>
              </label>
            </div>
            <p class="text-xs text-muted-foreground">Nombre maximum de SMS que ce compte peut envoyer</p>
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
              <span>{{ saving ? 'Enregistrement...' : (editingAccount ? 'Modifier' : 'Créer') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Permissions -->
    <div
      v-if="showPermissionsModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closePermissionsModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-lg">
        <div class="border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Permissions</h2>
            <button @click="closePermissionsModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <p class="text-sm text-muted-foreground mt-1">{{ editingAccount?.name }}</p>
        </div>

        <div class="p-6 space-y-4">
          <div v-for="perm in availablePermissions" :key="perm.value" class="flex items-start gap-3">
            <input
              v-model="selectedPermissions"
              :value="perm.value"
              type="checkbox"
              :id="'perm-' + perm.value"
              class="mt-1 rounded border-gray-300"
            />
            <label :for="'perm-' + perm.value" class="flex-1 cursor-pointer">
              <div class="font-medium">{{ perm.label }}</div>
              <div class="text-sm text-muted-foreground">{{ perm.description }}</div>
            </label>
          </div>
        </div>

        <div class="border-t p-6 flex gap-3">
          <button
            type="button"
            @click="closePermissionsModal"
            class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            Annuler
          </button>
          <button
            @click="savePermissions"
            :disabled="savingPermissions"
            class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
          >
            <div v-if="savingPermissions" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>{{ savingPermissions ? 'Enregistrement...' : 'Enregistrer' }}</span>
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  PlusIcon, PencilIcon, TrashIcon, XMarkIcon, UserGroupIcon,
  KeyIcon, LockClosedIcon, LockOpenIcon
} from '@heroicons/vue/24/outline'
import { subAccountService } from '@/services/subAccountService'
import api from '@/services/api'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

interface SubAccount {
  id: number
  name: string
  email: string
  role: 'admin' | 'manager' | 'sender' | 'viewer'
  status: 'active' | 'suspended' | 'inactive'
  sms_credit_limit: number | null
  sms_used: number
  remaining_credits: number | null
  permissions: string[]
  last_connection: string | null
  created_at: string
}

const accounts = ref<SubAccount[]>([])
const loading = ref(false)
const saving = ref(false)
const savingPermissions = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showPermissionsModal = ref(false)
const editingAccount = ref<SubAccount | null>(null)
const unlimitedCredits = ref(false)
const selectedPermissions = ref<string[]>([])

const accountForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'sender' as 'admin' | 'manager' | 'sender' | 'viewer',
  status: 'active' as 'active' | 'suspended' | 'inactive',
  sms_credit_limit: null as number | null
})

const roleLabels: Record<string, string> = {
  admin: 'Administrateur',
  manager: 'Manager',
  sender: 'Expéditeur',
  viewer: 'Observateur'
}

const statusLabels: Record<string, string> = {
  active: 'Actif',
  suspended: 'Suspendu',
  inactive: 'Inactif'
}

const availablePermissions = [
  { value: 'send_sms', label: 'Envoyer SMS', description: 'Permet d\'envoyer des SMS individuels et en masse' },
  { value: 'view_history', label: 'Voir l\'historique', description: 'Consulter l\'historique des SMS envoyés' },
  { value: 'manage_contacts', label: 'Gérer les contacts', description: 'Ajouter, modifier et supprimer des contacts' },
  { value: 'manage_groups', label: 'Gérer les groupes', description: 'Créer et gérer des groupes de contacts' },
  { value: 'create_campaigns', label: 'Créer des campagnes', description: 'Créer et lancer des campagnes SMS' },
  { value: 'view_analytics', label: 'Voir les statistiques', description: 'Accéder aux rapports et statistiques' },
  { value: 'manage_templates', label: 'Gérer les modèles', description: 'Créer et modifier des modèles de messages' },
  { value: 'export_data', label: 'Exporter les données', description: 'Exporter les données en CSV/Excel' }
]

watch(unlimitedCredits, (value) => {
  if (value) {
    accountForm.value.sms_credit_limit = null
  }
})

async function loadAccounts() {
  loading.value = true
  try {
    const data = await subAccountService.getAll()
    accounts.value = data as any[] // Cast to match the extended interface with permissions
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement')
    console.error('Error loading accounts:', err)
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingAccount.value = null
  accountForm.value = {
    name: '',
    email: '',
    password: '',
    role: 'sender',
    status: 'active',
    sms_credit_limit: null
  }
  unlimitedCredits.value = true
  showAddModal.value = true
}

function openEditModal(account: SubAccount) {
  editingAccount.value = account
  accountForm.value = {
    name: account.name,
    email: account.email,
    password: '',
    role: account.role,
    status: account.status,
    sms_credit_limit: account.sms_credit_limit
  }
  unlimitedCredits.value = account.sms_credit_limit === null
  showEditModal.value = true
}

function openPermissionsModal(account: SubAccount) {
  editingAccount.value = account
  selectedPermissions.value = [...(account.permissions || [])]
  showPermissionsModal.value = true
}

function closeModal() {
  showAddModal.value = false
  showEditModal.value = false
  editingAccount.value = null
}

function closePermissionsModal() {
  showPermissionsModal.value = false
  editingAccount.value = null
  selectedPermissions.value = []
}

async function saveAccount() {
  saving.value = true
  try {
    const data: any = { ...accountForm.value }
    if (unlimitedCredits.value) {
      data.sms_credit_limit = null
    }
    if (editingAccount.value && !data.password) {
      delete data.password
    }

    if (editingAccount.value) {
      await subAccountService.update(editingAccount.value.id, data)
      showSuccess('Sous-compte modifié avec succès')
    } else {
      await subAccountService.create(data)
      showSuccess('Sous-compte créé avec succès')
    }
    closeModal()
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'enregistrement')
    console.error('Error saving account:', err)
  } finally {
    saving.value = false
  }
}

async function savePermissions() {
  if (!editingAccount.value) return

  savingPermissions.value = true
  try {
    await api.post(`/sub-accounts/${editingAccount.value.id}/permissions`, {
      permissions: selectedPermissions.value
    })
    showSuccess('Permissions mises à jour avec succès')
    closePermissionsModal()
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la mise à jour')
  } finally {
    savingPermissions.value = false
  }
}

async function suspendAccount(account: SubAccount) {
  const confirmed = await showConfirm(
    'Suspendre le compte ?',
    `Voulez-vous vraiment suspendre "${account.name}" ? Il ne pourra plus se connecter.`
  )

  if (confirmed) {
    try {
      await api.post(`/sub-accounts/${account.id}/suspend`)
      showSuccess('Compte suspendu avec succès')
      await loadAccounts()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur lors de la suspension')
    }
  }
}

async function activateAccount(account: SubAccount) {
  try {
    await api.post(`/sub-accounts/${account.id}/activate`)
    showSuccess('Compte activé avec succès')
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'activation')
  }
}

async function deleteAccount(account: SubAccount) {
  const confirmed = await showConfirm(
    'Supprimer le compte ?',
    `Êtes-vous sûr de vouloir supprimer "${account.name}" ? Cette action est irréversible.`
  )

  if (confirmed) {
    try {
      await subAccountService.delete(account.id)
      showSuccess('Compte supprimé avec succès')
      await loadAccounts()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur lors de la suppression')
      console.error('Error deleting account:', err)
    }
  }
}

function getUsagePercentage(account: SubAccount): number {
  if (account.sms_credit_limit === null) return 0
  return Math.min(100, (account.sms_used / account.sms_credit_limit) * 100)
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

onMounted(() => {
  loadAccounts()
})
</script>
