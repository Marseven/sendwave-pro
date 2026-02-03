<template>
  <div class="border rounded-lg overflow-hidden">
    <!-- Header (always visible) -->
    <button
      @click="expanded = !expanded"
      class="w-full flex items-center gap-3 p-3 hover:bg-muted/50 transition-colors text-left"
    >
      <span
        class="px-2 py-0.5 text-xs font-semibold rounded min-w-[52px] text-center"
        :class="methodBadgeClass"
      >
        {{ endpoint.method }}
      </span>
      <code class="text-sm font-mono flex-1 truncate">{{ endpoint.path }}</code>
      <span class="text-xs text-muted-foreground hidden sm:block max-w-[200px] truncate">{{ endpoint.summary }}</span>
      <ChevronDownIcon
        class="w-4 h-4 text-muted-foreground transition-transform shrink-0"
        :class="{ 'rotate-180': expanded }"
      />
    </button>

    <!-- Expanded details -->
    <div v-if="expanded" class="border-t bg-muted/20 p-4 space-y-4">
      <!-- Summary & Description -->
      <div>
        <h4 class="font-medium text-sm">{{ endpoint.summary }}</h4>
        <p v-if="endpoint.description" class="text-sm text-muted-foreground mt-1">{{ endpoint.description }}</p>
      </div>

      <!-- Auth & Permissions -->
      <div class="flex flex-wrap gap-2">
        <span
          v-if="endpoint.auth"
          class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300"
        >
          <LockClosedIcon class="w-3 h-3" />
          Authentification requise
        </span>
        <span
          v-else
          class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
        >
          <LockOpenIcon class="w-3 h-3" />
          Public
        </span>
        <span
          v-for="perm in endpoint.permissions"
          :key="perm"
          class="px-2 py-0.5 text-xs rounded-full bg-primary/10 text-primary"
        >
          {{ perm }}
        </span>
      </div>

      <!-- Query Parameters -->
      <div v-if="endpoint.parameters?.length">
        <h5 class="text-xs font-semibold uppercase text-muted-foreground mb-2">Paramètres</h5>
        <div class="rounded border overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-muted/50">
              <tr>
                <th class="text-left px-3 py-1.5 text-xs font-medium text-muted-foreground">Nom</th>
                <th class="text-left px-3 py-1.5 text-xs font-medium text-muted-foreground">Type</th>
                <th class="text-left px-3 py-1.5 text-xs font-medium text-muted-foreground hidden sm:table-cell">Description</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="param in endpoint.parameters" :key="param.name">
                <td class="px-3 py-1.5 font-mono text-xs">{{ param.name }}</td>
                <td class="px-3 py-1.5 text-xs text-muted-foreground">{{ param.type }}</td>
                <td class="px-3 py-1.5 text-xs text-muted-foreground hidden sm:table-cell">
                  {{ param.description }}
                  <code v-if="param.example" class="ml-1 text-xs bg-muted px-1 rounded">{{ param.example }}</code>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Request Body -->
      <div v-if="endpoint.body">
        <h5 class="text-xs font-semibold uppercase text-muted-foreground mb-2">Corps de la requête</h5>
        <div class="relative">
          <pre class="p-3 bg-muted rounded-lg text-xs font-mono overflow-x-auto max-h-[300px]">{{ formatJson(endpoint.body) }}</pre>
          <button
            @click="copyToClipboard(formatJson(endpoint.body))"
            class="absolute top-2 right-2 p-1 rounded hover:bg-background/80 text-muted-foreground hover:text-foreground"
            title="Copier"
          >
            <ClipboardIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Response -->
      <div v-if="endpoint.response">
        <h5 class="text-xs font-semibold uppercase text-muted-foreground mb-2">Réponse</h5>
        <div class="relative">
          <pre class="p-3 bg-muted rounded-lg text-xs font-mono overflow-x-auto max-h-[300px] text-green-700 dark:text-green-400">{{ formatJson(endpoint.response) }}</pre>
          <button
            @click="copyToClipboard(formatJson(endpoint.response))"
            class="absolute top-2 right-2 p-1 rounded hover:bg-background/80 text-muted-foreground hover:text-foreground"
            title="Copier"
          >
            <ClipboardIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- cURL Example -->
      <div v-if="curlExample">
        <h5 class="text-xs font-semibold uppercase text-muted-foreground mb-2">Exemple cURL</h5>
        <div class="relative">
          <pre class="p-3 bg-gray-900 text-gray-100 rounded-lg text-xs font-mono overflow-x-auto">{{ curlExample }}</pre>
          <button
            @click="copyToClipboard(curlExample)"
            class="absolute top-2 right-2 p-1 rounded hover:bg-gray-700 text-gray-400 hover:text-gray-200"
            title="Copier"
          >
            <ClipboardIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronDownIcon, LockClosedIcon, LockOpenIcon, ClipboardIcon } from '@heroicons/vue/24/outline'
import { getMethodBadgeClass, type ApiEndpointData } from '@/data/apiDocumentation'

const props = defineProps<{
  endpoint: ApiEndpointData
  baseUrl: string
}>()

const expanded = ref(false)

const methodBadgeClass = computed(() => getMethodBadgeClass(props.endpoint.method))

const curlExample = computed(() => {
  if (props.endpoint.curl) {
    return props.endpoint.curl.replace('{baseUrl}', props.baseUrl)
  }

  const url = `${props.baseUrl}${props.endpoint.path}`
  let cmd = `curl -X ${props.endpoint.method} ${url}`

  if (props.endpoint.auth) {
    cmd += ` \\\n  -H 'Authorization: Bearer YOUR_TOKEN'`
  }

  if (props.endpoint.body) {
    const bodyWithoutComments = { ...props.endpoint.body }
    delete bodyWithoutComments._comment
    if (Object.keys(bodyWithoutComments).length > 0) {
      cmd += ` \\\n  -H 'Content-Type: application/json'`
      cmd += ` \\\n  -d '${JSON.stringify(bodyWithoutComments)}'`
    }
  }

  return cmd
})

function formatJson(obj: any): string {
  return JSON.stringify(obj, null, 2)
}

function copyToClipboard(text: string) {
  navigator.clipboard.writeText(text)
}
</script>
