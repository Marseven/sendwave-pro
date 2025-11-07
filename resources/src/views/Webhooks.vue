<template>
  <MainLayout>
    <div class="p-8">
      <!-- Header -->
      <div class="mb-8 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-foreground">Webhooks</h1>
          <p class="text-muted-foreground mt-2">Intégrez SendWave Pro avec vos applications tierces</p>
        </div>
        <button
          @click="openCreateDialog"
          class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Nouveau Webhook
        </button>
      </div>

      <!-- Info Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-muted-foreground">Webhooks Actifs</h3>
            <component :is="webhookIcon" class="w-8 h-8 text-primary" />
          </div>
          <p class="text-3xl font-bold">{{ activeWebhooksCount }}</p>
          <p class="text-xs text-muted-foreground mt-2">sur {{ webhooks.length }} total</p>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-muted-foreground">Livraisons Réussies</h3>
            <CheckCircleIcon class="w-8 h-8 text-success" />
          </div>
          <p class="text-3xl font-bold text-success">{{ totalSuccessCount }}</p>
          <p class="text-xs text-muted-foreground mt-2">au total</p>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-muted-foreground">Échecs</h3>
            <ExclamationTriangleIcon class="w-8 h-8 text-destructive" />
          </div>
          <p class="text-3xl font-bold text-destructive">{{ totalFailureCount }}</p>
          <p class="text-xs text-muted-foreground mt-2">au total</p>
        </div>
      </div>

      <!-- Events Info -->
      <div class="rounded-lg border bg-card shadow-sm mb-8">
        <div class="p-6 border-b border-border">
          <h2 class="text-xl font-semibold">Événements Disponibles</h2>
          <p class="text-sm text-muted-foreground mt-1">12 types d'événements pour déclencher vos webhooks</p>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <div
              v-for="event in availableEvents"
              :key="event"
              class="px-3 py-2 bg-secondary rounded-lg text-sm font-medium text-center"
            >
              {{ event }}
            </div>
          </div>
        </div>
      </div>

      <!-- Webhooks Table -->
      <div class="rounded-lg border bg-card shadow-sm">
        <div class="p-6 border-b border-border">
          <h2 class="text-xl font-semibold">Vos Webhooks</h2>
          <p class="text-sm text-muted-foreground mt-1">Gérez vos endpoints de webhooks et surveillez leur statut</p>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-muted-foreground">Chargement des webhooks...</p>
          </div>
          <div v-else-if="webhooks.length === 0" class="text-center py-12">
            <component :is="webhookIcon" class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-medium mb-2">Aucun webhook configuré</h3>
            <p class="text-muted-foreground mb-4">Commencez par créer votre premier webhook pour recevoir des événements</p>
            <button
              @click="openCreateDialog"
              class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors inline-flex items-center gap-2"
            >
              <PlusIcon class="w-5 h-5" />
              Créer un Webhook
            </button>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-border">
                  <th class="text-left p-4 font-medium text-muted-foreground">Nom</th>
                  <th class="text-left p-4 font-medium text-muted-foreground">URL</th>
                  <th class="text-left p-4 font-medium text-muted-foreground">Événements</th>
                  <th class="text-left p-4 font-medium text-muted-foreground">Statut</th>
                  <th class="text-left p-4 font-medium text-muted-foreground">Succès</th>
                  <th class="text-left p-4 font-medium text-muted-foreground">Échecs</th>
                  <th class="text-left p-4 font-medium text-muted-foreground">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="webhook in webhooks" :key="webhook.id" class="border-b border-border hover:bg-muted/50">
                  <td class="p-4">{{ webhook.name }}</td>
                  <td class="p-4">
                    <span class="text-sm font-mono text-muted-foreground truncate block max-w-xs">
                      {{ webhook.url }}
                    </span>
                  </td>
                  <td class="p-4">{{ webhook.events.length }} événement(s)</td>
                  <td class="p-4">
                    <span
                      class="px-2 py-1 rounded-full text-xs font-medium"
                      :class="webhook.is_active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'"
                    >
                      {{ webhook.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="p-4 text-success font-medium">{{ webhook.success_count }}</td>
                  <td class="p-4 text-destructive font-medium">{{ webhook.failure_count }}</td>
                  <td class="p-4">
                    <div class="flex gap-2">
                      <button
                        @click="testWebhook(webhook)"
                        class="px-3 py-1 text-sm border border-border rounded hover:bg-muted transition-colors"
                        title="Tester"
                      >
                        Tester
                      </button>
                      <button
                        @click="toggleWebhook(webhook)"
                        class="px-3 py-1 text-sm border border-border rounded hover:bg-muted transition-colors"
                        :title="webhook.is_active ? 'Désactiver' : 'Activer'"
                      >
                        {{ webhook.is_active ? 'Désactiver' : 'Activer' }}
                      </button>
                      <button
                        @click="editWebhook(webhook)"
                        class="px-3 py-1 text-sm border border-border rounded hover:bg-muted transition-colors"
                        title="Modifier"
                      >
                        Modifier
                      </button>
                      <button
                        @click="deleteWebhook(webhook)"
                        class="px-3 py-1 text-sm border border-destructive text-destructive rounded hover:bg-destructive/10 transition-colors"
                        title="Supprimer"
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Documentation -->
      <div class="rounded-lg border bg-card shadow-sm mt-8">
        <div class="p-6 border-b border-border">
          <h2 class="text-xl font-semibold">Documentation</h2>
        </div>
        <div class="p-6 space-y-4 text-sm">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <ShieldCheckIcon class="w-5 h-5 text-primary" />
              <h4 class="font-semibold">Sécurité</h4>
            </div>
            <p class="text-muted-foreground">
              Tous les webhooks incluent une signature HMAC-SHA256 dans le header <code class="px-1 py-0.5 bg-secondary rounded">X-Webhook-Signature</code>
            </p>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-2">
              <ArrowPathIcon class="w-5 h-5 text-primary" />
              <h4 class="font-semibold">Retry Logic</h4>
            </div>
            <p class="text-muted-foreground">
              Les webhooks échoués sont automatiquement retry jusqu'à 3 fois avec un délai exponentiel (1s, 2s, 4s)
            </p>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-2">
              <ClockIcon class="w-5 h-5 text-primary" />
              <h4 class="font-semibold">Timeout</h4>
            </div>
            <p class="text-muted-foreground">
              Le timeout par défaut est de 30 secondes, configurable entre 5 et 120 secondes
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <div v-if="showDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeDialog">
      <div class="bg-card rounded-lg shadow-xl max-w-2xl w-full m-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-border">
          <h2 class="text-xl font-semibold">{{ editingWebhook ? 'Modifier le webhook' : 'Créer un nouveau webhook' }}</h2>
        </div>
        <form @submit.prevent="saveWebhook" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Nom du webhook</label>
            <input
              v-model="formData.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Ex: Notification Slack"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">URL du webhook</label>
            <input
              v-model="formData.url"
              type="url"
              required
              class="w-full px-3 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="https://example.com/webhook"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Événements à écouter</label>
            <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto p-2 border border-border rounded-lg">
              <label
                v-for="event in availableEvents"
                :key="event"
                class="flex items-center gap-2 p-2 hover:bg-muted rounded cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="event"
                  v-model="formData.events"
                  class="w-4 h-4"
                />
                <span class="text-sm">{{ event }}</span>
              </label>
            </div>
          </div>
          <div class="flex gap-3 pt-4">
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50"
            >
              {{ saving ? 'Enregistrement...' : (editingWebhook ? 'Modifier' : 'Créer') }}
            </button>
            <button
              type="button"
              @click="closeDialog"
              class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors"
            >
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, shallowRef } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  PlusIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  ArrowPathIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'
import { webhookService } from '@/services/webhookService'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

// Use shallowRef for icon to avoid reactivity warnings
const webhookIcon = shallowRef((await import('@heroicons/vue/24/outline')).LinkIcon)

interface Webhook {
  id: number
  name: string
  url: string
  events: string[]
  is_active: boolean
  success_count: number
  failure_count: number
  last_triggered_at: string | null
}

const webhooks = ref<Webhook[]>([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const editingWebhook = ref<Webhook | null>(null)

const formData = ref({
  name: '',
  url: '',
  events: [] as string[]
})

const availableEvents = [
  'message.sent',
  'message.delivered',
  'message.failed',
  'campaign.started',
  'campaign.completed',
  'campaign.failed',
  'contact.created',
  'contact.updated',
  'contact.deleted',
  'sub_account.created',
  'sub_account.suspended',
  'blacklist.added'
]

const activeWebhooksCount = computed(() => webhooks.value.filter(w => w.is_active).length)
const totalSuccessCount = computed(() => webhooks.value.reduce((sum, w) => sum + w.success_count, 0))
const totalFailureCount = computed(() => webhooks.value.reduce((sum, w) => sum + w.failure_count, 0))

async function loadWebhooks() {
  loading.value = true
  try {
    webhooks.value = await webhookService.getAll()
  } catch (err) {
    console.error('Error loading webhooks:', err)
    showError('Impossible de charger les webhooks')
  } finally {
    loading.value = false
  }
}

function openCreateDialog() {
  editingWebhook.value = null
  formData.value = {
    name: '',
    url: '',
    events: []
  }
  showDialog.value = true
}

function editWebhook(webhook: Webhook) {
  editingWebhook.value = webhook
  formData.value = {
    name: webhook.name,
    url: webhook.url,
    events: [...webhook.events]
  }
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  editingWebhook.value = null
}

async function saveWebhook() {
  if (formData.value.events.length === 0) {
    showError('Veuillez sélectionner au moins un événement')
    return
  }

  saving.value = true
  try {
    if (editingWebhook.value) {
      await webhookService.update(editingWebhook.value.id, formData.value)
      showSuccess('Webhook modifié avec succès')
    } else {
      await webhookService.create(formData.value)
      showSuccess('Webhook créé avec succès')
    }
    await loadWebhooks()
    closeDialog()
  } catch (err) {
    console.error('Error saving webhook:', err)
    showError('Impossible de sauvegarder le webhook')
  } finally {
    saving.value = false
  }
}

async function testWebhook(webhook: Webhook) {
  try {
    const result = await webhookService.test(webhook.id)
    if (result.success) {
      showSuccess(`Test réussi (Code: ${result.response_code})`)
    } else {
      showError(`Test échoué (Code: ${result.response_code})`)
    }
  } catch (err) {
    console.error('Error testing webhook:', err)
    showError('Impossible de tester le webhook')
  }
}

async function toggleWebhook(webhook: Webhook) {
  try {
    await webhookService.toggle(webhook.id)
    showSuccess(`Webhook ${webhook.is_active ? 'désactivé' : 'activé'} avec succès`)
    await loadWebhooks()
  } catch (err) {
    console.error('Error toggling webhook:', err)
    showError('Impossible de modifier le statut du webhook')
  }
}

async function deleteWebhook(webhook: Webhook) {
  const confirmed = await showConfirm(
    'Êtes-vous sûr de vouloir supprimer ce webhook ?',
    `Le webhook "${webhook.name}" sera définitivement supprimé.`
  )
  if (!confirmed) return

  try {
    await webhookService.delete(webhook.id)
    showSuccess('Webhook supprimé avec succès')
    await loadWebhooks()
  } catch (err) {
    console.error('Error deleting webhook:', err)
    showError('Impossible de supprimer le webhook')
  }
}

onMounted(() => {
  loadWebhooks()
})
</script>
