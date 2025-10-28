<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Comptes SMS</h1>
          <p class="text-muted-foreground mt-2">Gérez vos comptes d'envoi SMS</p>
        </div>
        <button
          @click="openAddModal"
          class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Ajouter un compte</span>
        </button>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <div v-else-if="accounts.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
        <BanknotesIcon class="w-16 h-16 text-muted-foreground mb-4" />
        <p class="text-lg font-medium">Aucun compte trouvé</p>
        <p class="text-sm text-muted-foreground mt-1">Ajoutez votre premier compte pour commencer</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="account in accounts"
          :key="account.id"
          class="rounded-lg border bg-card shadow-sm p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">{{ account.name }}</h3>
            <span
              class="text-xs px-2 py-1 rounded-full"
              :class="account.status === 'active' ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'"
            >
              {{ account.status === 'active' ? 'Actif' : 'Inactif' }}
            </span>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Crédits restants</span>
              <span class="font-semibold">{{ (account.credits_remaining || 0).toLocaleString('fr-FR') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Utilisés ce mois</span>
              <span>{{ (account.credits_used_this_month || 0).toLocaleString('fr-FR') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Taux de livraison</span>
              <span class="text-success">{{ account.delivery_rate || 0 }}%</span>
            </div>
          </div>
          <div class="flex gap-2 mt-4">
            <button
              @click="openEditModal(account)"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9"
            >
              <PencilIcon class="w-4 h-4" />
              <span>Modifier</span>
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
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="closeModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">{{ editingAccount ? 'Modifier le compte' : 'Nouveau compte' }}</h2>
          <button @click="closeModal" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveAccount" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom du compte *</label>
            <input
              v-model="accountForm.name"
              type="text"
              required
              placeholder="Compte principal"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Crédits</label>
              <input
                v-model.number="accountForm.credits_remaining"
                type="number"
                min="0"
                placeholder="1000"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Taux livraison (%)</label>
              <input
                v-model.number="accountForm.delivery_rate"
                type="number"
                min="0"
                max="100"
                step="0.1"
                placeholder="98.5"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Statut</label>
            <select
              v-model="accountForm.status"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <option value="active">Actif</option>
              <option value="inactive">Inactif</option>
            </select>
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
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon, BanknotesIcon } from '@heroicons/vue/24/outline'
import { subAccountService, type SubAccount } from '@/services/subAccountService'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

const accounts = ref<SubAccount[]>([])
const loading = ref(false)
const saving = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const editingAccount = ref<SubAccount | null>(null)

const accountForm = ref({
  name: '',
  credits_remaining: 0,
  delivery_rate: 98.5,
  status: 'active' as 'active' | 'inactive'
})

async function loadAccounts() {
  loading.value = true
  try {
    accounts.value = await subAccountService.getAll()
  } catch (err) {
    console.error('Error loading accounts:', err)
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingAccount.value = null
  accountForm.value = {
    name: '',
    credits_remaining: 0,
    delivery_rate: 98.5,
    status: 'active'
  }
  showAddModal.value = true
}

function openEditModal(account: SubAccount) {
  editingAccount.value = account
  accountForm.value = {
    name: account.name,
    credits_remaining: account.credits_remaining || 0,
    delivery_rate: account.delivery_rate || 98.5,
    status: account.status
  }
  showEditModal.value = true
}

function closeModal() {
  showAddModal.value = false
  showEditModal.value = false
  editingAccount.value = null
}

async function saveAccount() {
  saving.value = true
  try {
    if (editingAccount.value) {
      await subAccountService.update(editingAccount.value.id, accountForm.value)
      showSuccess('Compte modifié avec succès')
    } else {
      await subAccountService.create(accountForm.value)
      showSuccess('Compte créé avec succès')
    }
    closeModal()
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || err.message || 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

async function deleteAccount(account: SubAccount) {
  const confirmed = await showConfirm(
    'Supprimer le compte ?',
    `Êtes-vous sûr de vouloir supprimer le compte "${account.name}" ?`
  )

  if (confirmed) {
    try {
      await subAccountService.delete(account.id)
      showSuccess('Compte supprimé avec succès')
      await loadAccounts()
    } catch (err: any) {
      showError(err.message || 'Erreur lors de la suppression')
    }
  }
}

onMounted(() => {
  loadAccounts()
})
</script>
