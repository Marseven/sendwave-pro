<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Intégrations API</h1>
          <p class="text-muted-foreground mt-2">Configurez vos clés API et webhooks</p>
        </div>
        <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
          <KeyIcon class="w-4 h-4" />
          <span>Nouvelle clé API</span>
        </button>
      </div>

      <div class="max-w-3xl space-y-6">
        <div class="rounded-lg border bg-card p-6">
          <h3 class="font-semibold mb-4">Clés API actives</h3>
          <div class="space-y-3">
            <div
              v-for="apiKey in apiKeys"
              :key="apiKey.id"
              class="flex items-center justify-between p-4 bg-muted/50 rounded-lg"
            >
              <div>
                <div class="font-medium">{{ apiKey.name }}</div>
                <div class="text-sm text-muted-foreground font-mono">{{ apiKey.key }}</div>
                <div class="text-xs text-muted-foreground mt-1">
                  {{ apiKey.type === 'production' ? 'Production' : 'Test' }}
                  {{ apiKey.status === 'revoked' ? '(Révoquée)' : '' }}
                </div>
              </div>
              <div class="flex gap-2">
                <button
                  @click="copyKey(apiKey.key)"
                  class="text-sm px-3 py-1 rounded border hover:bg-accent"
                  :disabled="apiKey.status === 'revoked'"
                >
                  Copier
                </button>
                <button
                  @click="revokeKey(apiKey.id)"
                  class="text-sm px-3 py-1 rounded border hover:bg-destructive/10 text-destructive"
                  :disabled="apiKey.status === 'revoked'"
                >
                  Révoquer
                </button>
              </div>
            </div>
            <div v-if="apiKeys.length === 0" class="text-center text-muted-foreground py-8">
              Aucune clé API active
            </div>
          </div>
        </div>

        <div class="rounded-lg border bg-card p-6">
          <h3 class="font-semibold mb-4">Documentation</h3>
          <p class="text-sm text-muted-foreground mb-4">Consultez notre documentation pour intégrer l'API JOBS SMS dans vos applications.</p>
          <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
            <BookOpenIcon class="w-4 h-4" />
            <span>Voir la documentation</span>
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import { KeyIcon, BookOpenIcon } from '@heroicons/vue/24/outline'
import { apiKeyService, type ApiKey } from '@/services/apiKeyService'
import { showSuccess, showConfirm } from '@/utils/notifications'

const apiKeys = ref<ApiKey[]>([])
const loading = ref(false)

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

async function revokeKey(id: number) {
  const confirmed = await showConfirm(
    'Révoquer la clé API ?',
    'Êtes-vous sûr de vouloir révoquer cette clé API ?'
  )

  if (confirmed) {
    try {
      await apiKeyService.revoke(id)
      showSuccess('Clé API révoquée avec succès')
      await loadApiKeys()
    } catch (err) {
      console.error('Error revoking API key:', err)
    }
  }
}

function copyKey(key: string) {
  navigator.clipboard.writeText(key)
  showSuccess('Clé copiée dans le presse-papier')
}

onMounted(() => {
  loadApiKeys()
})
</script>
