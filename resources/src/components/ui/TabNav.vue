<template>
  <div class="border-b border-border">
    <nav class="flex -mb-px space-x-8" :class="containerClass">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('update:modelValue', tab.id)"
        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors"
        :class="[
          modelValue === tab.id
            ? 'border-primary text-primary'
            : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'
        ]"
      >
        <span class="flex items-center gap-2">
          <component v-if="tab.icon" :is="tab.icon" class="w-5 h-5" />
          {{ tab.label }}
          <span
            v-if="tab.badge !== undefined"
            class="ml-1.5 px-2 py-0.5 text-xs rounded-full"
            :class="modelValue === tab.id ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
          >
            {{ tab.badge }}
          </span>
        </span>
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'

interface Tab {
  id: string
  label: string
  icon?: any
  badge?: number | string
}

defineProps<{
  tabs: Tab[]
  modelValue: string
  containerClass?: string
}>()

defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()
</script>
