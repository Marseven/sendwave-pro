<template>
  <div class="rounded-lg border bg-card">
    <!-- Category Header -->
    <button
      @click="open = !open"
      class="w-full flex items-center justify-between p-4 hover:bg-muted/30 transition-colors text-left"
    >
      <div class="flex items-center gap-3">
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary">
          <span class="text-sm font-bold">{{ category.endpoints.length }}</span>
        </div>
        <div>
          <h3 class="font-semibold text-sm">{{ category.name }}</h3>
          <p class="text-xs text-muted-foreground">{{ category.description }}</p>
        </div>
      </div>
      <ChevronDownIcon
        class="w-5 h-5 text-muted-foreground transition-transform shrink-0"
        :class="{ 'rotate-180': open }"
      />
    </button>

    <!-- Endpoints List -->
    <div v-if="open" class="border-t p-4 space-y-2">
      <ApiEndpoint
        v-for="(endpoint, idx) in filteredEndpoints"
        :key="idx"
        :endpoint="endpoint"
        :base-url="baseUrl"
      />
      <p
        v-if="filteredEndpoints.length === 0"
        class="text-sm text-muted-foreground text-center py-4"
      >
        Aucun endpoint ne correspond aux filtres.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import ApiEndpoint from './ApiEndpoint.vue'
import type { ApiCategory } from '@/data/apiDocumentation'

const props = defineProps<{
  category: ApiCategory
  baseUrl: string
  searchQuery?: string
  methodFilter?: string
  defaultOpen?: boolean
}>()

const open = ref(props.defaultOpen ?? false)

const filteredEndpoints = computed(() => {
  let endpoints = props.category.endpoints

  if (props.methodFilter) {
    endpoints = endpoints.filter(e => e.method === props.methodFilter)
  }

  if (props.searchQuery) {
    const q = props.searchQuery.toLowerCase()
    endpoints = endpoints.filter(e =>
      e.path.toLowerCase().includes(q) ||
      e.summary.toLowerCase().includes(q) ||
      (e.description?.toLowerCase().includes(q))
    )
  }

  return endpoints
})
</script>
