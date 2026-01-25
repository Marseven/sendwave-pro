<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
              <CogIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
              Transactionnel
            </h1>
            <p class="text-sm text-muted-foreground mt-1">Gérez vos Sender IDs, modèles, brouillons et routes</p>
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
      <div v-if="activeTab === 'senderid'" class="space-y-4 sm:space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <h2 class="text-base sm:text-lg font-semibold">Sender IDs</h2>
          <button
            @click="showAddSenderModal = true"
            class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 h-9 sm:h-10"
          >
            <PlusIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Nouveau Sender ID</span>
            <span class="sm:hidden">Nouveau</span>
          </button>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-lg border bg-card">
          <table class="w-full">
            <thead>
              <tr class="border-b bg-muted/50">
                <th class="text-left p-4 font-medium text-sm">Sender ID</th>
                <th class="text-left p-4 font-medium text-sm">Type</th>
                <th class="text-left p-4 font-medium text-sm">Statut</th>
                <th class="text-left p-4 font-medium text-sm">Date création</th>
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
                  <p>Aucun Sender ID configuré</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
          <div
            v-for="sender in senderIds"
            :key="sender.id"
            class="rounded-lg border bg-card p-4"
          >
            <div class="flex items-start justify-between mb-3">
              <div>
                <span class="font-medium">{{ sender.name }}</span>
                <div class="flex flex-wrap gap-2 mt-2">
                  <span class="px-2 py-1 text-xs rounded-full" :class="sender.type === 'transactional' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                    {{ sender.type === 'transactional' ? 'Transactionnel' : 'Marketing' }}
                  </span>
                  <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(sender.status)">
                    {{ getStatusLabel(sender.status) }}
                  </span>
                </div>
              </div>
              <div class="flex gap-1">
                <button @click="editSender(sender)" class="p-2 hover:bg-accent rounded-lg">
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button @click="deleteSender(sender)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <div class="text-xs text-muted-foreground">
              Créé le {{ formatDate(sender.created_at) }}
            </div>
          </div>
          <div v-if="senderIds.length === 0" class="p-8 text-center text-muted-foreground rounded-lg border bg-card">
            <IdentificationIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>Aucun Sender ID configuré</p>
          </div>
        </div>
      </div>

      <!-- Templates Tab -->
      <div v-if="activeTab === 'templates'" class="space-y-4 sm:space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <h2 class="text-base sm:text-lg font-semibold">Modèles de messages</h2>
          <button
            @click="showAddTemplateModal = true"
            class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 h-9 sm:h-10"
          >
            <PlusIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Nouveau modèle</span>
            <span class="sm:hidden">Nouveau</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
          <div
            v-for="template in templates"
            :key="template.id"
            class="rounded-lg border bg-card p-3 sm:p-4 hover:shadow-md transition-shadow"
          >
            <div class="flex items-start justify-between mb-2 sm:mb-3">
              <div class="min-w-0 flex-1">
                <h3 class="font-semibold text-sm sm:text-base truncate">{{ template.name }}</h3>
                <span class="text-xs text-muted-foreground">{{ template.category || 'Général' }}</span>
              </div>
              <div class="flex gap-1 ml-2">
                <button @click="editTemplate(template)" class="p-1.5 hover:bg-accent rounded">
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button @click="deleteTemplate(template)" class="p-1.5 hover:bg-destructive/10 text-destructive rounded">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <p class="text-xs sm:text-sm text-muted-foreground line-clamp-3 mb-2 sm:mb-3">{{ template.content }}</p>
            <div class="flex items-center justify-between text-xs">
              <span class="text-muted-foreground">{{ template.content.length }} caractères</span>
              <button
                @click="useTemplate(template)"
                class="text-primary hover:underline font-medium"
              >
                Utiliser
              </button>
            </div>
          </div>
          <div v-if="templates.length === 0" class="col-span-full p-6 sm:p-8 text-center text-muted-foreground border rounded-lg">
            <DocumentTextIcon class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 opacity-50" />
            <p class="text-sm">Aucun modèle disponible</p>
          </div>
        </div>
      </div>

      <!-- Drafts Tab -->
      <div v-if="activeTab === 'drafts'" class="space-y-4 sm:space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <h2 class="text-base sm:text-lg font-semibold">Brouillons</h2>
          <button
            v-if="drafts.length > 0"
            @click="clearAllDrafts"
            class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 border rounded-lg text-sm font-medium hover:bg-accent h-9 sm:h-10"
          >
            <TrashIcon class="w-4 h-4" />
            Tout supprimer
          </button>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-lg border bg-card">
          <table class="w-full">
            <thead>
              <tr class="border-b bg-muted/50">
                <th class="text-left p-4 font-medium text-sm">Nom</th>
                <th class="text-left p-4 font-medium text-sm">Aperçu</th>
                <th class="text-left p-4 font-medium text-sm">Destinataires</th>
                <th class="text-left p-4 font-medium text-sm">Sauvegardé le</th>
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
                  <p>Aucun brouillon sauvegardé</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
          <div
            v-for="draft in drafts"
            :key="draft.id"
            class="rounded-lg border bg-card p-4"
          >
            <div class="flex items-start justify-between mb-2">
              <span class="font-medium text-sm">{{ draft.name }}</span>
              <button @click="deleteDraft(draft)" class="p-1.5 hover:bg-destructive/10 text-destructive rounded">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
            <p class="text-xs text-muted-foreground line-clamp-2 mb-3">{{ draft.content }}</p>
            <div class="flex items-center justify-between text-xs text-muted-foreground mb-3">
              <span>{{ draft.recipients_count || 0 }} contact(s)</span>
              <span>{{ formatDate(draft.created_at) }}</span>
            </div>
            <button
              @click="loadDraft(draft)"
              class="w-full px-3 py-2 text-sm bg-primary text-primary-foreground rounded hover:bg-primary/90"
            >
              Charger
            </button>
          </div>
          <div v-if="drafts.length === 0" class="p-8 text-center text-muted-foreground rounded-lg border bg-card">
            <BookmarkIcon class="w-10 h-10 mx-auto mb-2 opacity-50" />
            <p class="text-sm">Aucun brouillon sauvegardé</p>
          </div>
        </div>
      </div>

      <!-- Routes Tab -->
      <div v-if="activeTab === 'routes'" class="space-y-4 sm:space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3">
          <h2 class="text-base sm:text-lg font-semibold">Routes SMS</h2>
          <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
            <InformationCircleIcon class="w-4 h-4 flex-shrink-0" />
            <span>Configuration des passerelles</span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
          <!-- Airtel Route -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2 sm:gap-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                  <SignalIcon class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-sm sm:text-base">Airtel Gabon</h3>
                  <p class="text-xs sm:text-sm text-muted-foreground">API HTTP directe</p>
                </div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="routes.airtel.enabled" @change="toggleRoute('airtel')" class="sr-only peer">
                <div class="w-11 h-6 bg-muted peer-focus:ring-2 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
            <div class="space-y-2 sm:space-y-3 text-xs sm:text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Préfixes</span>
                <span class="font-medium">77, 74, 76</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Coût/SMS</span>
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
              class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium hover:bg-accent h-9 sm:h-10"
            >
              <Cog6ToothIcon class="w-4 h-4" />
              Configurer
            </router-link>
          </div>

          <!-- Moov Route -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2 sm:gap-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                  <SignalIcon class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-sm sm:text-base">Moov Gabon</h3>
                  <p class="text-xs sm:text-sm text-muted-foreground">Protocole SMPP</p>
                </div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="routes.moov.enabled" @change="toggleRoute('moov')" class="sr-only peer">
                <div class="w-11 h-6 bg-muted peer-focus:ring-2 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
            <div class="space-y-2 sm:space-y-3 text-xs sm:text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Préfixes</span>
                <span class="font-medium">60, 62, 65, 66</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Coût/SMS</span>
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
              class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium hover:bg-accent h-9 sm:h-10"
            >
              <Cog6ToothIcon class="w-4 h-4" />
              Configurer
            </router-link>
          </div>
        </div>

        <!-- Fallback Configuration -->
        <div class="rounded-lg border bg-card p-4 sm:p-6">
          <div class="flex items-center gap-2 sm:gap-3 mb-3 sm:mb-4">
            <ArrowPathIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
            <h3 class="font-semibold text-sm sm:text-base">Configuration de secours</h3>
          </div>
          <p class="text-xs sm:text-sm text-muted-foreground mb-3 sm:mb-4">
            Lorsque le fallback est activé, si l'opérateur principal échoue, le système bascule automatiquement vers l'opérateur de secours.
          </p>
          <div class="flex items-center gap-3 sm:gap-4">
            <label class="flex items-center gap-2 sm:gap-3 cursor-pointer">
              <input type="checkbox" v-model="fallbackEnabled" @change="toggleFallback" class="w-4 h-4 rounded border-input">
              <span class="text-xs sm:text-sm font-medium">Activer le fallback automatique</span>
            </label>
          </div>
          <div v-if="fallbackEnabled" class="mt-3 sm:mt-4 p-2 sm:p-3 bg-muted/50 rounded-lg text-xs sm:text-sm">
            <p class="flex items-center gap-2">
              <CheckCircleIcon class="w-4 h-4 text-success flex-shrink-0" />
              Airtel → Moov (si Airtel échoue)
            </p>
            <p class="flex items-center gap-2 mt-1">
              <CheckCircleIcon class="w-4 h-4 text-success flex-shrink-0" />
              Moov → Airtel (si Moov échoue)
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Sender ID Modal -->
    <div v-if="showAddSenderModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
      <div class="absolute inset-0 bg-black/50" @click="showAddSenderModal = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-sm sm:max-w-md p-4 sm:p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-base sm:text-lg font-semibold mb-4">{{ editingSender ? 'Modifier' : 'Nouveau' }} Sender ID</h3>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom du Sender ID</label>
            <input
              v-model="senderForm.name"
              type="text"
              placeholder="Ex: JOBSSMS"
              maxlength="11"
              class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
            <p class="text-xs text-muted-foreground">Maximum 11 caractères alphanumériques</p>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium">Type</label>
            <select v-model="senderForm.type" class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="transactional">Transactionnel</option>
              <option value="marketing">Marketing</option>
            </select>
          </div>
        </div>
        <div class="flex gap-2 sm:gap-3 mt-6">
          <button
            @click="saveSender"
            :disabled="!senderForm.name"
            class="flex-1 h-9 sm:h-10 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            {{ editingSender ? 'Modifier' : 'Créer' }}
          </button>
          <button
            @click="showAddSenderModal = false; editingSender = null"
            class="px-4 h-9 sm:h-10 border rounded-lg text-sm hover:bg-accent"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>

    <!-- Add Template Modal -->
    <div v-if="showAddTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
      <div class="absolute inset-0 bg-black/50" @click="showAddTemplateModal = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-sm sm:max-w-lg p-4 sm:p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-base sm:text-lg font-semibold mb-4">{{ editingTemplate ? 'Modifier' : 'Nouveau' }} modèle</h3>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom du modèle</label>
            <input
              v-model="templateForm.name"
              type="text"
              placeholder="Ex: Confirmation commande"
              class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium">Catégorie</label>
            <select v-model="templateForm.category" class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="">-- Sélectionner --</option>
              <option value="notification">Notification</option>
              <option value="marketing">Marketing</option>
              <option value="verification">Vérification</option>
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
              rows="4"
              maxlength="480"
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
            ></textarea>
          </div>
        </div>
        <div class="flex gap-2 sm:gap-3 mt-6">
          <button
            @click="saveTemplate"
            :disabled="!templateForm.name || !templateForm.content"
            class="flex-1 h-9 sm:h-10 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            {{ editingTemplate ? 'Modifier' : 'Créer' }}
          </button>
          <button
            @click="showAddTemplateModal = false; editingTemplate = null"
            class="px-4 h-9 sm:h-10 border rounded-lg text-sm hover:bg-accent"
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

// Onglets
const tabs = [
  { id: 'senderid', label: 'Sender ID' },
  { id: 'templates', label: 'Modèles' },
  { id: 'drafts', label: 'Brouillons' },
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
      showSuccess('Sender ID modifié')
    } else {
      senderIds.value.push({
        id: Date.now(),
        ...senderForm.value,
        status: 'pending',
        created_at: new Date().toISOString()
      })
      showSuccess('Sender ID créé')
    }

    showAddSenderModal.value = false
    editingSender.value = null
    senderForm.value = { name: '', type: 'transactional' }
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function deleteSender(sender: SenderId) {
  const confirmed = await showConfirm('Supprimer ce Sender ID ?', `${sender.name} sera supprimé définitivement.`)
  if (!confirmed) return

  senderIds.value = senderIds.value.filter(s => s.id !== sender.id)
  showSuccess('Sender ID supprimé')
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
      showSuccess('Modèle modifié')
    } else {
      await apiClient.post('/templates', templateForm.value)
      showSuccess('Modèle créé')
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
  const confirmed = await showConfirm('Supprimer ce modèle ?', `"${template.name}" sera supprimé définitivement.`)
  if (!confirmed) return

  try {
    await apiClient.delete(`/templates/${template.id}`)
    showSuccess('Modèle supprimé')
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
  const confirmed = await showConfirm('Supprimer ce brouillon ?', 'Cette action est irréversible.')
  if (!confirmed) return

  try {
    await apiClient.delete(`/templates/${draft.id}`)
    showSuccess('Brouillon supprimé')
    loadDrafts()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function clearAllDrafts() {
  const confirmed = await showConfirm('Supprimer tous les brouillons ?', 'Cette action est irréversible.')
  if (!confirmed) return

  try {
    for (const draft of drafts.value) {
      await apiClient.delete(`/templates/${draft.id}`)
    }
    showSuccess('Brouillons supprimés')
    loadDrafts()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function toggleRoute(provider: 'airtel' | 'moov') {
  try {
    await apiClient.post(`/sms-configs/${provider}/toggle`)
    showSuccess(`Route ${provider} ${routes.value[provider].enabled ? 'activée' : 'désactivée'}`)
  } catch (error: any) {
    // Revert
    routes.value[provider].enabled = !routes.value[provider].enabled
    showError(error.response?.data?.message || 'Erreur')
  }
}

async function toggleFallback() {
  showSuccess(`Fallback ${fallbackEnabled.value ? 'activé' : 'désactivé'}`)
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
    case 'approved': return 'Approuvé'
    case 'pending': return 'En attente'
    case 'rejected': return 'Rejeté'
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
